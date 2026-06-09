@extends('layouts.app')

@section('title', 'Trainers - Mitali SP')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0">Trainers</h4>
        <a href="{{ route('admin.trainers.create') }}" class="btn btn-primary">Add Trainer</a>
    </div>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.trainers.index') }}" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label for="name" class="form-label">Search by Name</label>
                    <input type="text" name="name" id="name" class="form-control" value="{{ request('name') }}">
                </div>
                <div class="col-md-3">
                    <label for="phone" class="form-label">Search by Mobile</label>
                    <input type="text" name="phone" id="phone" class="form-control" maxlength="10"
                           value="{{ request('phone') }}">
                </div>
                <div class="col-md-3">
                    <label for="status" class="form-label">Status</label>
                    <select name="status" id="status" class="form-select">
                        <option value="active" @selected($status === 'active')>Active</option>
                        <option value="inactive" @selected($status === 'inactive')>Inactive</option>
                        <option value="all" @selected($status === 'all')>All</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary me-2">Search</button>
                    <a href="{{ route('admin.trainers.index') }}" class="btn btn-outline-secondary">Reset</a>
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
                        <th>Gender</th>
                        <th>Age</th>
                        <th>Phone</th>
                        <th>Joining Date</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($trainers as $trainer)
                        <tr>
                            <td>{{ $trainer->name }}</td>
                            <td>{{ $trainer->genderLabel() }}</td>
                            <td>{{ $trainer->age() }}</td>
                            <td>{{ $trainer->mobile }}</td>
                            <td>{{ $trainer->joining_date->format('d M Y') }}</td>
                            <td>
                                @if($trainer->is_active)
                                    <span class="badge bg-success">Active</span>
                                @else
                                    <span class="badge bg-secondary">Inactive</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="table-actions">
                                    <a href="{{ route('admin.trainers.show', $trainer) }}"
                                       class="btn btn-action btn-action-info" title="View">
                                        <i class="bi bi-eye"></i> View
                                    </a>
                                    @if($trainer->is_active)
                                        <a href="{{ route('admin.trainers.edit', $trainer) }}"
                                           class="btn btn-action btn-action-neutral" title="Edit">
                                            <i class="bi bi-pencil-square"></i> Edit
                                        </a>
                                        <a href="{{ route('admin.trainers.documents.index', $trainer) }}"
                                           class="btn btn-action btn-action-neutral" title="Documents">
                                            <i class="bi bi-folder2-open"></i> Docs
                                        </a>
                                        <form method="POST"
                                              action="{{ route('admin.trainers.deactivate', $trainer) }}"
                                              onsubmit="return confirm('Deactivate this trainer?');">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-action btn-action-danger" title="Deactivate">
                                                <i class="bi bi-person-x"></i> Deactivate
                                            </button>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ route('admin.trainers.activate', $trainer) }}">
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
                            <td colspan="7" class="text-center text-muted py-4">No trainers found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($trainers->hasPages())
            <div class="card-footer">
                {{ $trainers->links() }}
            </div>
        @endif
    </div>
@endsection
