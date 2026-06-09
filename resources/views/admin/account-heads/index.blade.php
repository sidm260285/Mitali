@extends('layouts.app')

@section('title', 'Accounts Head - Mitali SP')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0">Accounts Head</h4>
        <a href="{{ route('admin.account-heads.create') }}" class="btn btn-primary">Add Accounts Head</a>
    </div>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.account-heads.index') }}" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label for="name" class="form-label">Search by Name</label>
                    <input type="text" name="name" id="name" class="form-control" value="{{ request('name') }}">
                </div>
                <div class="col-md-4">
                    <label for="type" class="form-label">Type</label>
                    <select name="type" id="type" class="form-select">
                        <option value="">All</option>
                        <option value="credit" @selected(request('type') === 'credit')>Credit</option>
                        <option value="debit" @selected(request('type') === 'debit')>Debit</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary me-2">Search</button>
                    <a href="{{ route('admin.account-heads.index') }}" class="btn btn-outline-secondary">Reset</a>
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
                        <th>Type</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($accountHeads as $accountHead)
                        <tr>
                            <td>{{ $accountHead->name }}</td>
                            <td>{{ $accountHead->typeLabel() }}</td>
                            <td class="text-end">
                                <div class="table-actions">
                                    @if(! $accountHead->isInUse())
                                        <a href="{{ route('admin.account-heads.edit', $accountHead) }}"
                                           class="btn btn-action btn-action-neutral">
                                            <i class="bi bi-pencil-square"></i> Edit
                                        </a>
                                        <form method="POST" action="{{ route('admin.account-heads.destroy', $accountHead) }}"
                                              onsubmit="return confirm('Delete this accounts head?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-action btn-action-danger">
                                                <i class="bi bi-trash"></i> Delete
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-muted small">In use</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center text-muted py-4">No accounts heads found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($accountHeads->hasPages())
            <div class="card-footer">{{ $accountHeads->links() }}</div>
        @endif
    </div>
@endsection
