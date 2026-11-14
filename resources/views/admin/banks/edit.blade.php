@extends('layouts.app')

@section('title', 'Edit Bank - Mitali SP')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0">Edit Bank</h4>
        <a href="{{ route('admin.banks.index') }}" class="btn btn-outline-secondary">Back to List</a>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.banks.update', $bank) }}">
                @csrf
                @method('PUT')
                @include('partials.bank-form', ['bank' => $bank, 'defaultPassword' => null, 'accountTypeOptions' => $accountTypeOptions])
                <div class="mt-3">
                    <button type="submit" class="btn btn-primary">Update Bank</button>
                </div>
            </form>
        </div>
    </div>
@endsection
