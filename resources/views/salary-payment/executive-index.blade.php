@extends('layouts.app')

@section('title', 'Pay Salary to Executives - Mitali SP')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0">{{ $title }}</h4>
    </div>

    @include('salary-payment.partials.salary-list', [
        'payees' => $payees,
        'payeeType' => $payeeType,
        'selectedMonth' => $selectedMonth,
        'selectedYear' => $selectedYear,
        'monthOptions' => $monthOptions,
        'yearOptions' => $yearOptions,
        'banks' => $banks,
        'routeName' => $routeName,
        'phoneLabel' => 'Phone',
    ])
@endsection
