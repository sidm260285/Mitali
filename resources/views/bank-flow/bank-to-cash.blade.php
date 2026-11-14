@extends('layouts.app')

@section('title', 'Bank to Cash - Mitali SP')

@section('content')
    @php
        $isAdmin = auth()->user()->isAdmin();
        $balanceBaseUrl = $isAdmin
            ? url('admin/bank-flow/banks')
            : url('executive/bank-flow/banks');
    @endphp

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0">Bank to Cash</h4>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <form method="POST" action="{{ $isAdmin ? route('admin.bank-flow.bank-to-cash.store') : route('executive.bank-flow.bank-to-cash.store') }}">
                @csrf

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label for="bank_id" class="form-label">Bank <span class="text-danger">*</span></label>
                        <select name="bank_id" id="bank_id"
                                class="form-select @error('bank_id') is-invalid @enderror"
                                data-balance-base="{{ $balanceBaseUrl }}"
                                required>
                            <option value="">Select bank</option>
                            @foreach($banks as $bank)
                                <option value="{{ $bank->id }}"
                                    @selected(old('bank_id', $selectedBankId) == $bank->id)>
                                    {{ $bank->name }} - {{ substr($bank->account_no, -5) }}
                                </option>
                            @endforeach
                        </select>
                        @error('bank_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label for="amount" class="form-label">Amount <span class="text-danger">*</span></label>
                        <input type="number" name="amount" id="amount" step="0.01" min="0.01"
                               class="form-control @error('amount') is-invalid @enderror"
                               value="{{ old('amount') }}" required>
                        @error('amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text" id="bank-balance-display">
                            @if($selectedBankBalance !== null)
                                Current balance: <strong>{{ \App\Support\MoneyHelper::format($selectedBankBalance) }}</strong>
                            @endif
                        </div>
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
                        <label for="transaction_id" class="form-label">Ref Transaction ID <span class="text-danger">*</span></label>
                        <input type="text" name="transaction_id" id="transaction_id" maxlength="255"
                               class="form-control @error('transaction_id') is-invalid @enderror"
                               value="{{ old('transaction_id') }}" required>
                        @error('transaction_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label for="narration" class="form-label">Narration</label>
                        <input type="text" name="narration" id="narration" maxlength="400"
                               class="form-control @error('narration') is-invalid @enderror"
                               value="{{ old('narration') }}">
                        @error('narration')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="form-actions mt-4">
                    <button type="submit" class="btn btn-form btn-form-primary">
                        <i class="bi bi-cash-stack"></i> Record Bank to Cash
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    (function () {
        const bankSelect = document.getElementById('bank_id');
        const balanceDisplay = document.getElementById('bank-balance-display');
        const baseUrl = bankSelect.dataset.balanceBase;

        function fetchBalance(bankId) {
            if (! bankId) {
                balanceDisplay.innerHTML = '';
                return;
            }

            fetch(baseUrl + '/' + bankId + '/balance', {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                balanceDisplay.innerHTML = 'Current balance: <strong>' + data.balance + '</strong>';
            })
            .catch(function () {
                balanceDisplay.innerHTML = '';
            });
        }

        bankSelect.addEventListener('change', function () {
            fetchBalance(this.value);
        });
    })();
</script>
@endpush
