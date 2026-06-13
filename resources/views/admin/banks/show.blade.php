@extends('layouts.app')

@section('title', 'View Bank - Mitali SP')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0">Bank Details</h4>
        <div>
            <a href="{{ route('admin.banks.edit', $bank) }}" class="btn btn-primary">Edit</a>
            <a href="{{ route('admin.banks.index') }}" class="btn btn-outline-secondary">Back to List</a>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-3">Bank Name</dt>
                <dd class="col-sm-9">{{ $bank->name }}</dd>

                <dt class="col-sm-3">Username</dt>
                <dd class="col-sm-9">{{ $bank->username }}</dd>

                <dt class="col-sm-3">Account Number</dt>
                <dd class="col-sm-9">{{ $bank->account_no }}</dd>

                <dt class="col-sm-3">Account Type</dt>
                <dd class="col-sm-9">{{ $bank->account_type }}</dd>

                <dt class="col-sm-3">Branch Name</dt>
                <dd class="col-sm-9">{{ $bank->branch_name }}</dd>

                <dt class="col-sm-3">Phone</dt>
                <dd class="col-sm-9">{{ $bank->phone ?? '—' }}</dd>

                <dt class="col-sm-3">Email</dt>
                <dd class="col-sm-9">{{ $bank->email ?? '—' }}</dd>

                <dt class="col-sm-3">Status</dt>
                <dd class="col-sm-9">
                    @if($bank->is_active)
                        <span class="badge bg-success">Active</span>
                    @else
                        <span class="badge bg-secondary">Inactive</span>
                    @endif
                </dd>
            </dl>
        </div>
    </div>
@endsection
