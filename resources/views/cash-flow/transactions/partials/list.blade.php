@php
    use App\Models\CashTransaction;
    use App\Support\MoneyHelper;

    $showRoute = auth()->user()->isAdmin()
        ? 'admin.cash-flow.transactions.show'
        : 'executive.cash-flow.transactions.show';

    $sort = request('sort', 'id');
    $direction = request('direction', 'desc');

    $sortLink = function (string $column) use ($sort, $direction) {
        $nextDirection = ($sort === $column && $direction === 'asc') ? 'desc' : 'asc';

        return request()->fullUrlWithQuery(['sort' => $column, 'direction' => $nextDirection]);
    };
@endphp

<div class="card shadow-sm border-0">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th><a href="{{ $sortLink('id') }}" class="text-decoration-none text-dark">ID</a></th>
                    <th><a href="{{ $sortLink('transaction_date') }}" class="text-decoration-none text-dark">Date</a></th>
                    <th class="text-end">Credit</th>
                    <th class="text-end">Debit</th>
                    <th>Head</th>
                    <th>Narration</th>
                    <th class="text-end">Current Balance</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transactions as $transaction)
                    <tr class="transaction-row" role="button" style="cursor: pointer;"
                        data-url="{{ route($showRoute, $transaction) }}">
                        <td>{{ $transaction->id }}</td>
                        <td>{{ $transaction->transaction_date->format('d M Y') }}</td>
                        <td class="text-end">
                            @if($transaction->type === CashTransaction::TYPE_CREDIT)
                                {{ MoneyHelper::format($transaction->amount) }}
                            @else
                                —
                            @endif
                        </td>
                        <td class="text-end">
                            @if($transaction->type === CashTransaction::TYPE_DEBIT)
                                {{ MoneyHelper::format($transaction->amount) }}
                            @else
                                —
                            @endif
                        </td>
                        <td>{{ $transaction->accountHead->name }}</td>
                        <td>{{ $transaction->narrationPreview() }}</td>
                        <td class="text-end">{{ MoneyHelper::format($transaction->current_balance) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">No transactions found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($transactions->hasPages())
        <div class="card-footer">
            {{ $transactions->links() }}
        </div>
    @endif
</div>

@include('cash-flow.transactions.partials.modal')

@push('scripts')
<script>
    document.querySelectorAll('.transaction-row').forEach(function (row) {
        row.addEventListener('click', function () {
            const url = row.dataset.url;
            const modal = new bootstrap.Modal(document.getElementById('transactionDetailModal'));
            const body = document.getElementById('transactionDetailBody');
            body.innerHTML = '<div class="text-center text-muted py-4">Loading...</div>';
            modal.show();

            fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                .then(response => response.json())
                .then(data => {
                    let linkedHtml = '';
                    if (data.linked.length > 1) {
                        linkedHtml = '<hr><h6 class="mb-3">Linked Transfer Entries</h6><ul class="list-group list-group-flush">';
                        data.linked.forEach(function (row) {
                            linkedHtml += '<li class="list-group-item px-0">' +
                                '<strong>#' + row.id + '</strong> — ' + row.user + ' — ' + row.type +
                                ' — ' + row.account_head + ' — ' + row.amount +
                                ' (Balance: ' + row.current_balance + ')</li>';
                        });
                        linkedHtml += '</ul>';
                    }

                    body.innerHTML =
                        '<dl class="row mb-0">' +
                        '<dt class="col-sm-4">ID</dt><dd class="col-sm-8">#' + data.transaction.id + '</dd>' +
                        '<dt class="col-sm-4">Transaction Date</dt><dd class="col-sm-8">' + data.transaction.transaction_date + '</dd>' +
                        '<dt class="col-sm-4">Type</dt><dd class="col-sm-8">' + data.transaction.type + '</dd>' +
                        '<dt class="col-sm-4">Accounts Head</dt><dd class="col-sm-8">' + data.transaction.account_head + '</dd>' +
                        '<dt class="col-sm-4">Amount</dt><dd class="col-sm-8">' + data.transaction.amount + '</dd>' +
                        '<dt class="col-sm-4">Narration</dt><dd class="col-sm-8">' + data.transaction.narration + '</dd>' +
                        '<dt class="col-sm-4">Current Balance</dt><dd class="col-sm-8">' + data.transaction.current_balance + '</dd>' +
                        '<dt class="col-sm-4">Created By</dt><dd class="col-sm-8">' + data.transaction.created_by + '</dd>' +
                        '<dt class="col-sm-4">Recorded At</dt><dd class="col-sm-8">' + data.transaction.recorded_at + '</dd>' +
                        '</dl>' + linkedHtml;
                })
                .catch(function () {
                    body.innerHTML = '<div class="text-danger">Unable to load transaction details.</div>';
                });
        });
    });
</script>
@endpush
