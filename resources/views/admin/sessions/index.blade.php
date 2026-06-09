@extends('layouts.app')

@section('title', 'Session Master - Mitali SP')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0">Session Master</h4>
        <a href="{{ route('admin.sessions.create') }}" class="btn btn-primary">Add Session</a>
    </div>

    <ul class="nav nav-tabs mb-4">
        @foreach(['upcoming' => 'Upcoming', 'current' => 'Current', 'over' => 'Over'] as $key => $label)
            <li class="nav-item">
                <a class="nav-link {{ $tab === $key ? 'active' : '' }}"
                   href="{{ route('admin.sessions.index', array_merge(request()->except('page'), ['tab' => $key])) }}">
                    {{ $label }} ({{ $tabCounts[$key] }})
                </a>
            </li>
        @endforeach
    </ul>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.sessions.index') }}" class="row g-3 align-items-end">
                <input type="hidden" name="tab" value="{{ $tab }}">
                <div class="col-md-6">
                    <label for="name" class="form-label">Search by Name</label>
                    <input type="text" name="name" id="name" class="form-control" value="{{ request('name') }}">
                </div>
                <div class="col-md-6">
                    <button type="submit" class="btn btn-primary me-2">Search</button>
                    <a href="{{ route('admin.sessions.index', ['tab' => $tab]) }}" class="btn btn-outline-secondary">Reset Search</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Name</th>
                        <th>Status</th>
                        <th>Ages</th>
                        <th>Batches</th>
                        <th>Membership</th>
                        <th>Created</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sessions as $session)
                        <tr>
                            <td class="fw-semibold">{{ $session->name }}</td>
                            <td>
                                <span class="badge bg-{{ $session->status === 'current' ? 'primary' : ($session->status === 'upcoming' ? 'info text-dark' : 'secondary') }}">
                                    {{ $session->statusLabel() }}
                                </span>
                            </td>
                            <td class="small text-muted session-list-cell">{!! nl2br(e($session->agesSummary())) !!}</td>
                            <td class="small text-muted session-list-cell">{!! nl2br(e($session->batchesSummary())) !!}</td>
                            <td class="small text-muted session-list-cell">{!! nl2br(e($session->membershipsSummary())) !!}</td>
                            <td>{{ $session->created_at->format('d M Y') }}</td>
                            <td class="text-end">
                                <div class="table-actions">
                                    <a href="{{ route('admin.sessions.edit', $session) }}"
                                       class="btn btn-action btn-action-neutral" title="Edit">
                                        <i class="bi bi-pencil-square"></i> Edit
                                    </a>
                                    @if($session->isDeletable())
                                        <form method="POST" action="{{ route('admin.sessions.destroy', $session) }}"
                                              onsubmit="return confirm('Delete this session?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-action btn-action-danger" title="Delete">
                                                <i class="bi bi-trash3"></i> Delete
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                No {{ $tab }} sessions found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($sessions->hasPages())
            <div class="card-footer">{{ $sessions->links() }}</div>
        @endif
    </div>
@endsection
