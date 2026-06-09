@extends('layouts.app')

@section('title', 'Show Executive Transaction - Mitali SP')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0">Show Executive Transaction</h4>
    </div>

    @include('cash-flow.transactions.partials.filters', [
        'filterAction' => route('admin.cash-flow.executive-transactions.index'),
        'showExecutiveFilter' => true,
        'executives' => $executives,
        'selectedExecutiveId' => $selectedExecutiveId,
    ])

    @if($transactions)
        @include('cash-flow.transactions.partials.list', ['transactions' => $transactions])
    @else
        <div class="card shadow-sm border-0">
            <div class="card-body text-center text-muted py-4">
                Select an executive to view transactions.
            </div>
        </div>
    @endif
@endsection
