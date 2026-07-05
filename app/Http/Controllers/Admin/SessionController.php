<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSessionRequest;
use App\Http\Requests\UpdateSessionRequest;
use App\Models\Session;
use App\Services\SessionPersistenceService;
use App\Services\SessionStatusValidator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SessionController extends Controller
{
    public function __construct(
        private SessionPersistenceService $persistence,
        private SessionStatusValidator $statusValidator,
    ) {}

    public function index(Request $request): View
    {
        $tab = $request->string('tab', Session::STATUS_CURRENT)->toString();

        if (! in_array($tab, [Session::STATUS_UPCOMING, Session::STATUS_CURRENT, Session::STATUS_OVER], true)) {
            $tab = Session::STATUS_CURRENT;
        }

        $query = Session::query()
            ->with(['ages', 'batches', 'memberships'])
            ->where('status', $tab)
            ->orderByDesc('created_at');

        if ($request->filled('name')) {
            $query->where('name', 'like', '%'.$request->string('name').'%');
        }

        $sessions = $query->paginate(25)->withQueryString();

        $counts = Session::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $tabCounts = [
            Session::STATUS_UPCOMING => $counts[Session::STATUS_UPCOMING] ?? 0,
            Session::STATUS_CURRENT => $counts[Session::STATUS_CURRENT] ?? 0,
            Session::STATUS_OVER => $counts[Session::STATUS_OVER] ?? 0,
        ];

        return view('admin.sessions.index', compact('sessions', 'tab', 'tabCounts'));
    }

    public function create(): View
    {
        return view('admin.sessions.create', [
            'statusOptions' => $this->statusValidator->allowedStatusesForCreate(),
        ]);
    }

    public function store(StoreSessionRequest $request): RedirectResponse
    {
        $this->persistence->create($request->validated());

        return redirect()
            ->route('admin.sessions.index', ['tab' => Session::STATUS_UPCOMING])
            ->with('success', 'Session created successfully.');
    }

    public function edit(Session $session): View
    {
        $session->load(['ages', 'batches', 'memberships']);

        return view('admin.sessions.edit', [
            'session' => $session,
            'statusOptions' => $this->statusValidator->allowedNextStatuses($session),
        ]);
    }

    public function update(UpdateSessionRequest $request, Session $session): RedirectResponse
    {
        $updated = $this->persistence->update($session, $request->validated());

        return redirect()
            ->route('admin.sessions.index', ['tab' => $updated->status])
            ->with('success', 'Session updated successfully.');
    }

    public function destroy(Session $session): RedirectResponse
    {
        $tab = $session->status;

        $this->persistence->delete($session);

        return redirect()
            ->route('admin.sessions.index', ['tab' => $tab])
            ->with('success', 'Session deleted successfully.');
    }
}
