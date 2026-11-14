@extends('layouts.app')

@section('title', 'Bank Transactions - Mitali SP')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0">Bank Transactions</h4>
    </div>

    @include('bank-flow.transactions.partials.filters', [
        'banks' => $banks,
        'selectedBankId' => $selectedBankId,
        'accountHeads' => $accountHeads,
    ])

    @if($transactions)
        @include('bank-flow.transactions.partials.list', ['transactions' => $transactions])
    @else
        <div class="card shadow-sm border-0">
            <div class="card-body text-center text-muted py-4">
                Select a bank to view transactions.
            </div>
        </div>
    @endif
@endsection
