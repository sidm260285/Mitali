@extends('layouts.app')

@section('title', 'Cash to Bank - Mitali SP')

@section('content')
    @php $isAdmin = auth()->user()->isAdmin(); @endphp

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0">Cash to Bank</h4>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <form method="POST" action="{{ $isAdmin ? route('admin.cash-flow.cash-to-bank.store') : route('executive.cash-flow.cash-to-bank.store') }}">
                @csrf

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label for="bank_id" class="form-label">Bank <span class="text-danger">*</span></label>
                        <select name="bank_id" id="bank_id"
                                class="form-select @error('bank_id') is-invalid @enderror"
                                required>
                            <option value="">Select bank</option>
                            @foreach($banks as $bank)
                                <option value="{{ $bank->id }}" @selected(old('bank_id') == $bank->id)>
                                    {{ $bank->name }} - {{ substr($bank->account_no, -5) }}
                                </option>
                            @endforeach
                        </select>
                        @error('bank_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                @include('cash-flow.partials.entry-fields', ['balance' => $balance, 'allowPastDates' => $allowPastDates])

                <div class="form-actions mt-4">
                    <button type="submit" class="btn btn-form btn-form-primary">
                        <i class="bi bi-bank"></i> Record Cash to Bank
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
