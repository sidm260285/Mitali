@php
    use App\Models\CashTransaction;
    use App\Support\MoneyHelper;

    $showReference = $showReference ?? false;
    $colspan = $showReference ? 8 : 7;
@endphp

<div class="card shadow-sm border-0">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Date</th>
                    <th class="text-end">Credit</th>
                    <th class="text-end">Debit</th>
                    <th>Head</th>
                    @if($showReference)
                        <th>Ref Transaction ID</th>
                    @endif
                    <th>Narration</th>
                    <th class="text-end">Current Balance</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transactions as $transaction)
                    <tr>
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
                        @if($showReference)
                            <td>{{ $transaction->transaction_id ?? '—' }}</td>
                        @endif
                        <td>{{ $transaction->narrationPreview() }}</td>
                        <td class="text-end">{{ MoneyHelper::format($transaction->current_balance) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $colspan }}" class="text-center text-muted py-4">No transactions found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($hasMoreTransactions)
        <div class="card-footer text-end">
            <a href="{{ $showMoreUrl }}" class="btn btn-outline-primary">Show more</a>
        </div>
    @endif
</div>
