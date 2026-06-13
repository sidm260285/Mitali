@extends('layouts.app')

@section('title', 'Banks - Mitali SP')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0">Banks</h4>
        <a href="{{ route('admin.banks.create') }}" class="btn btn-primary">Add Bank</a>
    </div>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.banks.index') }}" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label for="name" class="form-label">Search by Name</label>
                    <input type="text" name="name" id="name" class="form-control"
                           value="{{ request('name') }}">
                </div>
                <div class="col-md-4">
                    <label for="account_no" class="form-label">Search by Account No</label>
                    <input type="text" name="account_no" id="account_no" class="form-control"
                           value="{{ request('account_no') }}">
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary me-2">Search</button>
                    <a href="{{ route('admin.banks.index') }}" class="btn btn-outline-secondary">Reset Search</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Bank Name</th>
                        <th>Username</th>
                        <th>Account No</th>
                        <th>Account Type</th>
                        <th>Branch Name</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($banks as $bank)
                        <tr>
                            <td>{{ $bank->name }}</td>
                            <td>{{ $bank->username }}</td>
                            <td>{{ $bank->account_no }}</td>
                            <td>{{ $bank->account_type }}</td>
                            <td>{{ $bank->branch_name }}</td>
                            <td>
                                @if($bank->is_active)
                                    <span class="badge bg-success">Active</span>
                                @else
                                    <span class="badge bg-secondary">Inactive</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="table-actions">
                                    <a href="{{ route('admin.banks.show', $bank) }}"
                                       class="btn btn-action btn-action-info" title="View">
                                        <i class="bi bi-eye"></i> View
                                    </a>
                                    <a href="{{ route('admin.banks.edit', $bank) }}"
                                       class="btn btn-action btn-action-neutral" title="Edit">
                                        <i class="bi bi-pencil-square"></i> Edit
                                    </a>
                                    <form method="POST"
                                          action="{{ route('admin.banks.reset-password', $bank) }}"
                                          onsubmit="return confirm('Reset password for this bank?');">
                                        @csrf
                                        <button type="submit" class="btn btn-action btn-action-warning" title="Reset Password">
                                            <i class="bi bi-key"></i> Reset
                                        </button>
                                    </form>
                                    @if($bank->is_active)
                                        <form method="POST"
                                              action="{{ route('admin.banks.deactivate', $bank) }}"
                                              onsubmit="return confirm('Deactivate this bank?');">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-action btn-action-danger" title="Deactivate">
                                                <i class="bi bi-bank"></i> Deactivate
                                            </button>
                                        </form>
                                    @else
                                        <form method="POST"
                                              action="{{ route('admin.banks.activate', $bank) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-action btn-action-success" title="Activate">
                                                <i class="bi bi-bank"></i> Activate
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">No banks found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($banks->hasPages())
            <div class="card-footer">
                {{ $banks->links() }}
            </div>
        @endif
    </div>
@endsection
