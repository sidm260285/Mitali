@extends('layouts.app')

@section('title', 'Executives - Mitali SP')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0">Executives</h4>
        <a href="{{ route('admin.executives.create') }}" class="btn btn-primary">Add Executive</a>
    </div>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.executives.index') }}" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label for="name" class="form-label">Search by Name</label>
                    <input type="text" name="name" id="name" class="form-control"
                           value="{{ request('name') }}">
                </div>
                <div class="col-md-4">
                    <label for="phone" class="form-label">Search by Phone</label>
                    <input type="text" name="phone" id="phone" class="form-control" maxlength="10"
                           value="{{ request('phone') }}">
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary me-2">Search</button>
                    <a href="{{ route('admin.executives.index') }}" class="btn btn-outline-secondary">Reset Search</a>
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
                        <th>Username</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th class="text-end">Balance</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($executives as $executive)
                        <tr>
                            <td>{{ $executive->name }}</td>
                            <td>{{ $executive->username }}</td>
                            <td>{{ $executive->phone }}</td>
                            <td>{{ $executive->email ?? '—' }}</td>
                            <td>
                                @if($executive->is_active)
                                    <span class="badge bg-success">Active</span>
                                @else
                                    <span class="badge bg-secondary">Inactive</span>
                                @endif
                            </td>
                            <td class="text-end">{{ \App\Support\MoneyHelper::format($executive->balance) }}</td>
                            <td class="text-end">
                                <div class="table-actions">
                                    <a href="{{ route('admin.executives.show', $executive) }}"
                                       class="btn btn-action btn-action-info" title="View">
                                        <i class="bi bi-eye"></i> View
                                    </a>
                                    <a href="{{ route('admin.executives.edit', $executive) }}"
                                       class="btn btn-action btn-action-neutral" title="Edit">
                                        <i class="bi bi-pencil-square"></i> Edit
                                    </a>
                                    <form method="POST"
                                          action="{{ route('admin.executives.reset-password', $executive) }}"
                                          onsubmit="return confirm('Reset password for this executive?');">
                                        @csrf
                                        <button type="submit" class="btn btn-action btn-action-warning" title="Reset Password">
                                            <i class="bi bi-key"></i> Reset
                                        </button>
                                    </form>
                                    @if($executive->is_active)
                                        <form method="POST"
                                              action="{{ route('admin.executives.deactivate', $executive) }}"
                                              onsubmit="return confirm('Deactivate this executive?');">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-action btn-action-danger" title="Deactivate">
                                                <i class="bi bi-person-x"></i> Deactivate
                                            </button>
                                        </form>
                                    @else
                                        <form method="POST"
                                              action="{{ route('admin.executives.activate', $executive) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-action btn-action-success" title="Activate">
                                                <i class="bi bi-person-check"></i> Activate
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">No executives found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($executives->hasPages())
            <div class="card-footer">
                {{ $executives->links() }}
            </div>
        @endif
    </div>
@endsection
