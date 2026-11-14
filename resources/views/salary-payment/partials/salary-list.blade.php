@php
    use App\Support\MoneyHelper;
@endphp

<div class="card shadow-sm border-0 mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route($routeName) }}" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label for="month" class="form-label">Month</label>
                <select name="month" id="month" class="form-select" required>
                    <option value="">Select month</option>
                    @foreach($monthOptions as $num => $label)
                        <option value="{{ $num }}" @selected($selectedMonth == $num)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label for="year" class="form-label">Year</label>
                <select name="year" id="year" class="form-select" required>
                    <option value="">Select year</option>
                    @foreach($yearOptions as $y => $label)
                        <option value="{{ $y }}" @selected($selectedYear == $y)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-form btn-form-primary">
                    <i class="bi bi-search"></i> Show List
                </button>
            </div>
        </form>
    </div>
</div>

@if($selectedMonth && $selectedYear)
    @if($payees && $payees->isNotEmpty())
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white">
                <h6 class="mb-0">{{ $monthOptions[$selectedMonth] }} {{ $selectedYear }}</h6>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Name</th>
                            <th>{{ $phoneLabel }}</th>
                            <th class="text-end">Salary</th>
                            <th class="text-end">Paid</th>
                            <th class="text-end">Remaining</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($payees as $payee)
                            <tr>
                                <td>{{ $payee->name }}</td>
                                <td>{{ $payee->phone ?? '—' }}</td>
                                <td class="text-end">{{ MoneyHelper::format($payee->monthly_salary) }}</td>
                                <td class="text-end">{{ MoneyHelper::format($payee->paid_amount) }}</td>
                                <td class="text-end fw-bold">{{ MoneyHelper::format($payee->remaining) }}</td>
                                <td class="text-center">
                                    @if(! $payee->fully_paid)
                                        <button type="button"
                                                class="btn btn-action btn-action-success"
                                                data-bs-toggle="modal"
                                                data-bs-target="#payModal"
                                                data-payee-type="{{ $payeeType }}"
                                                data-payee-id="{{ $payee->id }}"
                                                data-name="{{ $payee->name }}"
                                                data-salary="{{ $payee->monthly_salary }}"
                                                data-paid="{{ $payee->paid_amount }}"
                                                data-remaining="{{ $payee->remaining }}"
                                                data-month="{{ $selectedMonth }}"
                                                data-year="{{ $selectedYear }}">
                                            <i class="bi bi-wallet2"></i> Pay
                                        </button>
                                    @else
                                        <span class="badge bg-success">Paid</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        @include('salary-payment.partials.pay-modal', ['banks' => $banks])
    @else
        <div class="card shadow-sm border-0">
            <div class="card-body text-center text-muted py-4">
                No active {{ $payeeType === 'executive' ? 'executives' : 'trainers' }} found.
            </div>
        </div>
    @endif
@else
    <div class="card shadow-sm border-0">
        <div class="card-body text-center text-muted py-4">
            Select a month and year to view salary status.
        </div>
    </div>
@endif
