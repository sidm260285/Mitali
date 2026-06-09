@extends('layouts.app')

@section('title', 'Show Transaction - Mitali SP')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0">Show Transaction</h4>
    </div>

    @include('cash-flow.transactions.partials.filters', [
        'filterAction' => auth()->user()->isAdmin()
            ? route('admin.cash-flow.transactions.index')
            : route('executive.cash-flow.transactions.index'),
        'showExecutiveFilter' => false,
    ])

    @include('cash-flow.transactions.partials.list', ['transactions' => $transactions])
@endsection
