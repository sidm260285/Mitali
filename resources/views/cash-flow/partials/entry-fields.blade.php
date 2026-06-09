@php
    use App\Support\MoneyHelper;
@endphp

<div class="row g-3">
    <div class="col-md-4">
        <label for="amount" class="form-label">Amount <span class="text-danger">*</span></label>
        <input type="number" name="amount" id="amount" step="0.01" min="0.01"
               class="form-control @error('amount') is-invalid @enderror"
               value="{{ old('amount') }}" required>
        @error('amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
        <div class="form-text">Current balance: <strong>{{ MoneyHelper::format($balance) }}</strong></div>
    </div>

    <div class="col-md-4">
        <label for="transaction_date" class="form-label">Transaction Date <span class="text-danger">*</span></label>
        <input type="date" name="transaction_date" id="transaction_date"
               class="form-control @error('transaction_date') is-invalid @enderror"
               value="{{ old('transaction_date', now()->toDateString()) }}"
               max="{{ now()->toDateString() }}"
               @if(! $allowPastDates) readonly @endif
               required>
        @error('transaction_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4">
        <label for="narration" class="form-label">Narration</label>
        <input type="text" name="narration" id="narration" maxlength="400"
               class="form-control @error('narration') is-invalid @enderror"
               value="{{ old('narration') }}">
        @error('narration')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>
