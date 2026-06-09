@extends('layouts.app')

@section('title', 'Inflow - Mitali SP')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0">Inflow</h4>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <form method="POST" action="{{ auth()->user()->isAdmin() ? route('admin.cash-flow.inflow.store') : route('executive.cash-flow.inflow.store') }}">
                @csrf
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label for="account_head_id" class="form-label">Accounts Head <span class="text-danger">*</span></label>
                        <select name="account_head_id" id="account_head_id" class="form-select @error('account_head_id') is-invalid @enderror" required>
                            <option value="">Select accounts head</option>
                            @foreach($accountHeads as $head)
                                <option value="{{ $head->id }}" @selected(old('account_head_id') == $head->id)>{{ $head->name }}</option>
                            @endforeach
                        </select>
                        @error('account_head_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                @include('cash-flow.partials.entry-fields', ['balance' => $balance, 'allowPastDates' => $allowPastDates])

                <div class="form-actions mt-4">
                    <button type="submit" class="btn btn-form btn-form-primary">
                        <i class="bi bi-plus-circle"></i> Record Inflow
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
