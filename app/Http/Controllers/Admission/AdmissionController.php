<?php

namespace App\Http\Controllers\Admission;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAdmissionRequest;
use App\Http\Requests\UpgradeAdmissionRequest;
use App\Models\Admission;
use App\Models\Session;
use App\Models\User;
use App\Services\AdmissionService;
use App\Support\TimeHelper;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
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
        $admission->load(['session', 'membership', 'sessionAge', 'slots.batch', 'documents', 'cashTransaction.user', 'admittedByUser', 'feeBreakdown']);

        return view('admission.show', compact('admission'));
    }

    public function edit(Admission $admission): View
    {
        $admission->load(['session', 'membership', 'sessionAge', 'slots.batch', 'cashTransaction.user', 'feeBreakdown']);
        $session = $admission->session;
        $session->load(['batches', 'memberships']);

        return view('admission.edit', [
            'admission' => $admission,
            'session' => $session,
            'isAdmin' => auth()->user()->isAdmin(),
            'upgradeMemberships' => $this->admissionService->upgradeMembershipChoices($admission),
            'banks' => User::banks()->active()->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Admission $admission): RedirectResponse
    {
        $admission->load(['session', 'membership', 'slots']);
        $isAdmin = $request->user()->isAdmin();

        $rules = [
            'physically_disabled' => ['required', 'boolean'],
            'gender' => ['required', Rule::in(Admission::genders())],
            'mobile_no' => ['required', 'string', 'digits:10'],
            'guardian_name' => ['required', 'string', 'max:255'],
            'relation' => ['required', Rule::in(Admission::relations())],
            'emergency_contact_no' => ['required', 'string', 'digits:10'],
            'address' => ['required', 'string', 'max:1000'],
            'police_station' => ['required', 'string', 'max:255'],
            'pin_code' => ['required', 'string', 'max:10'],
            'rfid_code' => ['required', 'string', 'max:100', Rule::unique('admissions')->where('session_id', $admission->session_id)->ignore($admission->id)],
            'batch_ids' => ['nullable', 'array'],
            'batch_ids.*' => ['integer'],
        ];

        if ($isAdmin) {
            $rules['full_name'] = ['required', 'string', 'max:255'];
        }

        $validated = $request->validate($rules, [
            'physically_disabled.required' => 'Please select whether the member is physically disabled.',
            'physically_disabled.boolean' => 'Please select whether the member is physically disabled.',
        ]);

        DB::transaction(function () use ($admission, $validated, $isAdmin) {
            $updateData = [
                'physically_disabled' => Admission::asBoolean($validated['physically_disabled']),
                'gender' => $validated['gender'],
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

            $admission->update($updateData);
            $this->admissionService->replaceBatches($admission, $validated['batch_ids'] ?? []);
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

    public function calculateFee(Request $request): JsonResponse
    {
        $sessionId = $request->integer('session_id');
        $membershipId = $request->integer('session_membership_id');
        $dob = $request->input('date_of_birth');

        if (! $sessionId || ! $membershipId || ! $dob) {
            return response()->json(['amount' => 0]);
        }

        try {
            $summary = $this->admissionService->quoteSummary(
                $sessionId,
                $membershipId,
                (string) $dob,
                $request->boolean('is_extended_membership'),
            );
        } catch (ModelNotFoundException) {
            return response()->json(['amount' => 0]);
        } catch (ValidationException $exception) {
            return response()->json([
                'amount' => 0,
                'error' => collect($exception->errors())->flatten()->first(),
            ]);
        }

        return response()->json($summary);
    }

    public function sessionData(Session $session): JsonResponse
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
                'extended_fee' => (float) $a->extended_fee,
            ]),
            'form_fee' => $session->form_fee,
            'from_date' => $session->from_date?->format('d M Y'),
            'to_date' => $session->to_date?->format('d M Y'),
            'extended_date' => $session->extended_date?->format('d M Y'),
        ]);
    }

    public function upgradeQuote(Request $request, Admission $admission): JsonResponse
    {
        $membershipId = $request->integer('session_membership_id');

        if ($membershipId === 0) {
            $membershipId = (int) $admission->session_membership_id;
        }

        $extend = $request->exists('is_extended_membership')
            ? $request->boolean('is_extended_membership')
            : (bool) $admission->is_extended_membership;

        return response()->json($this->admissionService->upgradeQuote($admission, $membershipId, $extend));
    }

    public function upgrade(UpgradeAdmissionRequest $request, Admission $admission): RedirectResponse
    {
        try {
            $this->admissionService->upgrade(
                $admission,
                $request->validated(),
                $request->user(),
            );
        } catch (ValidationException $exception) {
            return back()
                ->withInput()
                ->withErrors($exception->errors(), 'admissionUpgrade');
        }

        $prefix = $request->user()->isAdmin() ? 'admin' : 'executive';

        return redirect()
            ->route($prefix.'.admission.edit', $admission)
            ->with('success', 'Admission upgraded successfully.');
    }
}
