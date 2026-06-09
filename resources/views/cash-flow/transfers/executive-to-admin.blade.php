@extends('layouts.app')

@section('title', 'Transfer to Admin - Mitali SP')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0">Transfer to Admin</h4>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <form method="POST" action="{{ route('executive.cash-flow.transfer-to-admin.store') }}">
                @csrf
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label for="admin_name" class="form-label">Admin</label>
                        <input type="text" id="admin_name" class="form-control" value="{{ $admin->name }}" readonly>
                    </div>
                </div>

                @include('cash-flow.partials.entry-fields', ['balance' => $balance, 'allowPastDates' => $allowPastDates])

                <div class="form-actions mt-4">
                    <button type="submit" class="btn btn-form btn-form-primary">
                        <i class="bi bi-arrow-left-right"></i> Transfer to Admin
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
