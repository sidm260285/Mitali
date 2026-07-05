<?php

namespace App\Http\Controllers\Admission;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAdmissionRequest;
use App\Models\Admission;
use App\Models\AdmissionSlot;
use App\Models\CashTransaction;
use App\Models\Session;
use App\Models\SessionBatch;
use App\Models\SessionMembership;
use App\Models\User;
use App\Services\AdmissionService;
use App\Support\TimeHelper;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AdmissionController extends Controller
{
    public function __construct(
        private AdmissionService $admissionService,
    ) {}

    public function index(Request $request): View
    {
        $sessions = Session::orderByRaw("FIELD(status, 'current', 'upcoming', 'over')")
            ->orderBy('name')
            ->get();

        $selectedSessionId = $request->integer('session_id');

        if (! $selectedSessionId) {
            $currentSession = $sessions->firstWhere('status', Session::STATUS_CURRENT);
            $selectedSessionId = $currentSession?->id ?? $sessions->first()?->id;
        }

        $admissions = collect();
        if ($selectedSessionId) {
            $query = Admission::where('session_id', $selectedSessionId)
                ->with(['membership', 'slots.batch'])
                ->orderByDesc('created_at');

            if ($request->filled('search')) {
                $search = $request->string('search');
                $query->where(function ($q) use ($search) {
                    $q->where('full_name', 'like', "%{$search}%")
                      ->orWhere('mobile_no', 'like', "%{$search}%")
                      ->orWhere('rfid_code', 'like', "%{$search}%");
                });
            }

            if ($request->filled('status')) {
                $query->where('status', $request->input('status'));
            }

            if ($request->filled('doc_status')) {
                $query->where('is_document_complete', $request->input('doc_status') === 'complete');
            }

            $admissions = $query->paginate(25)->withQueryString();
        }

        return view('admission.index', [
            'sessions' => $sessions,
            'selectedSessionId' => $selectedSessionId,
            'admissions' => $admissions,
        ]);
    }

    public function show(Admission $admission): View
    {
        $admission->load(['session', 'membership', 'sessionAge', 'slots.batch', 'documents', 'cashTransaction', 'admittedByUser']);

        return view('admission.show', compact('admission'));
    }

    public function edit(Admission $admission): View
    {
        $admission->load(['session', 'membership', 'slots.batch']);
        $session = $admission->session;
        $session->load(['batches', 'memberships']);

        return view('admission.edit', [
            'admission' => $admission,
            'session' => $session,
            'isAdmin' => auth()->user()->isAdmin(),
        ]);
    }

    public function update(Request $request, Admission $admission): RedirectResponse
    {
        $admission->load(['session', 'membership', 'slots']);
        $isAdmin = $request->user()->isAdmin();

        $rules = [
            'gender' => ['required', Rule::in(Admission::genders())],
            'date_of_birth' => ['required', 'date', 'before:today'],
            'mobile_no' => ['required', 'string', 'digits:10'],
            'guardian_name' => ['required', 'string', 'max:255'],
            'relation' => ['required', Rule::in(Admission::relations())],
            'emergency_contact_no' => ['required', 'string', 'digits:10'],
            'address' => ['required', 'string', 'max:1000'],
            'police_station' => ['required', 'string', 'max:255'],
            'pin_code' => ['required', 'string', 'max:10'],
            'rfid_code' => ['required', 'string', 'max:100', Rule::unique('admissions')->where('session_id', $admission->session_id)->ignore($admission->id)],
        ];

        if ($isAdmin) {
            $rules['full_name'] = ['required', 'string', 'max:255'];
        }

        if (! $admission->hasAttendance()) {
            $rules['session_membership_id'] = ['required', 'integer', 'exists:session_memberships,id'];
            $rules['batch_ids'] = ['nullable', 'array'];
            $rules['batch_ids.*'] = ['integer', 'exists:session_batches,id'];
        }

        $validated = $request->validate($rules);

        DB::transaction(function () use ($admission, $validated, $isAdmin) {
            $updateData = [
                'gender' => $validated['gender'],
                'date_of_birth' => $validated['date_of_birth'],
                'mobile_no' => $validated['mobile_no'],
                'guardian_name' => $validated['guardian_name'],
                'relation' => $validated['relation'],
                'emergency_contact_no' => $validated['emergency_contact_no'],
                'address' => $validated['address'],
                'police_station' => $validated['police_station'],
                'pin_code' => $validated['pin_code'],
                'rfid_code' => $validated['rfid_code'],
            ];

            if ($isAdmin) {
                $updateData['full_name'] = $validated['full_name'];
            }

            if (! $admission->hasAttendance() && isset($validated['session_membership_id'])) {
                $newMembership = SessionMembership::where('session_id', $admission->session_id)
                    ->findOrFail($validated['session_membership_id']);

                $isWildcard = $newMembership->no_of_slot === -1;
                $allBatchIds = SessionBatch::where('session_id', $admission->session_id)->pluck('id')->toArray();

                if ($isWildcard) {
                    $batchIds = $allBatchIds;
                } else {
                    $batchIds = $validated['batch_ids'] ?? [];
                    if (count($batchIds) !== $newMembership->no_of_slot) {
                        throw ValidationException::withMessages([
                            'batch_ids' => 'You must select exactly '.$newMembership->no_of_slot.' batch(es).',
                        ]);
                    }
                }

                $oldMembership = $admission->membership;
                $oldBatchIds = $admission->slots->pluck('session_batch_id')->toArray();

                $updateData['session_membership_id'] = $newMembership->id;
                $updateData['is_wildcard'] = $isWildcard;

                if ($oldMembership->id !== $newMembership->id) {
                    if ($oldMembership->admission_counter > 0) {
                        $oldMembership->decrement('admission_counter');
                    }
                    $newMembership->increment('admission_counter');
                }

                if (! empty($oldBatchIds)) {
                    SessionBatch::whereIn('id', $oldBatchIds)
                        ->where('admission_counter', '>', 0)
                        ->decrement('admission_counter');
                }
                $admission->slots()->delete();

                foreach ($batchIds as $batchId) {
                    AdmissionSlot::create([
                        'admission_id' => $admission->id,
                        'session_batch_id' => $batchId,
                    ]);
                }
                SessionBatch::whereIn('id', $batchIds)->increment('admission_counter');
            }

            $admission->update($updateData);
        });

        $prefix = auth()->user()->isAdmin() ? 'admin' : 'executive';

        return redirect()
            ->route($prefix.'.admission.show', $admission)
            ->with('success', 'Admission updated successfully.');
    }

    public function block(Admission $admission): RedirectResponse
    {
        $admission->update(['status' => Admission::STATUS_BLOCKED]);

        $prefix = auth()->user()->isAdmin() ? 'admin' : 'executive';

        return redirect()
            ->route($prefix.'.admission.list', ['session_id' => $admission->session_id])
            ->with('success', $admission->full_name.' has been blocked.');
    }

    public function unblock(Admission $admission): RedirectResponse
    {
        $admission->update(['status' => Admission::STATUS_ACTIVE]);

        $prefix = auth()->user()->isAdmin() ? 'admin' : 'executive';

        return redirect()
            ->route($prefix.'.admission.list', ['session_id' => $admission->session_id])
            ->with('success', $admission->full_name.' has been unblocked.');
    }

    public function createCurrent(): View
    {
        $session = Session::where('status', Session::STATUS_CURRENT)->first();

        return view('admission.form', [
            'sessionType' => 'current',
            'sessions' => $session ? collect([$session]) : collect(),
            'selectedSession' => $session,
            'banks' => User::banks()->active()->orderBy('name')->get(),
        ]);
    }

    public function createUpcoming(): View
    {
        $sessions = Session::where('status', Session::STATUS_UPCOMING)->orderBy('name')->get();

        return view('admission.form', [
            'sessionType' => 'upcoming',
            'sessions' => $sessions,
            'selectedSession' => $sessions->first(),
            'banks' => User::banks()->active()->orderBy('name')->get(),
        ]);
    }

    public function store(StoreAdmissionRequest $request): RedirectResponse
    {
        $admission = $this->admissionService->create(
            $request->validated(),
            $request->user(),
        );

        $prefix = $request->user()->isAdmin() ? 'admin' : 'executive';

        return redirect()
            ->route($prefix.'.admission.documents', $admission)
            ->with('success', 'Admission recorded successfully. You may now upload documents.');
    }

    public function calculateFee(): \Illuminate\Http\JsonResponse
    {
        $request = request();
        $sessionId = $request->integer('session_id');
        $membershipId = $request->integer('session_membership_id');
        $dob = $request->input('date_of_birth');

        if (! $sessionId || ! $membershipId || ! $dob) {
            return response()->json(['amount' => 0]);
        }

        $session = Session::find($sessionId);
        $membership = \App\Models\SessionMembership::where('session_id', $sessionId)->find($membershipId);

        if (! $session || ! $membership) {
            return response()->json(['amount' => 0]);
        }

        $age = (int) floor(\Carbon\Carbon::parse($dob)->diffInYears(now()));
        $sessionAge = \App\Models\SessionAge::where('session_id', $sessionId)
            ->where('from_age', '<=', $age)
            ->where('to_age', '>=', $age)
            ->first();

        if (! $sessionAge) {
            return response()->json(['amount' => 0, 'error' => 'No age group found for age '.$age]);
        }

        $amount = (float) $membership->membership_cost + (float) $sessionAge->fee + $session->form_fee;

        return response()->json([
            'amount' => $amount,
            'membership_cost' => (float) $membership->membership_cost,
            'age_fee' => (float) $sessionAge->fee,
            'form_fee' => $session->form_fee,
        ]);
    }

    public function sessionData(Session $session): \Illuminate\Http\JsonResponse
    {
        $session->load(['batches', 'memberships', 'ages']);

        return response()->json([
            'batches' => $session->batches->map(fn ($b) => [
                'id' => $b->id,
                'label' => TimeHelper::format12Hour((string) $b->start_time).' - '.TimeHelper::format12Hour((string) $b->end_time),
                'available' => $b->max_size > 0 ? $b->max_size - $b->admission_counter : null,
            ]),
            'memberships' => $session->memberships->map(fn ($m) => [
                'id' => $m->id,
                'card_name' => $m->card_name,
                'no_of_slot' => $m->no_of_slot,
                'membership_cost' => (float) $m->membership_cost,
            ]),
            'ages' => $session->ages->map(fn ($a) => [
                'from_age' => $a->from_age,
                'to_age' => $a->to_age,
                'fee' => (float) $a->fee,
            ]),
            'form_fee' => $session->form_fee,
        ]);
    }
}
