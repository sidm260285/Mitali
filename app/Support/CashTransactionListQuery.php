<?php

namespace App\Support;

use App\Models\AccountHead;
use App\Models\CashTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class CashTransactionListQuery
{
    public static function build(Request $request, int $userId, ?string $mode = null)
    {
        $sort = $request->string('sort', 'id')->toString();
        $direction = $request->string('direction', 'desc')->toString();

        if (! in_array($sort, ['id', 'transaction_date', 'amount'], true)) {
            $sort = 'id';
        }

        if (! in_array($direction, ['asc', 'desc'], true)) {
            $direction = 'desc';
        }

        $query = CashTransaction::query()
            ->with('accountHead')
            ->forUser($userId)
            ->filterDateRange($request->input('from_date'), $request->input('to_date'));

        if ($mode !== null) {
            $query->where('mode', $mode);
        }

        if ($request->filled('account_head_id')) {
            $query->where('account_head_id', $request->integer('account_head_id'));
        }

        if ($request->filled('transaction_type')) {
            $query->where('type', $request->string('transaction_type')->toString());
        }

        if ($request->filled('transaction_id')) {
            $query->where('transaction_id', 'like', '%'.$request->string('transaction_id')->toString().'%');
        }

        return $query
            ->orderBy($sort, $direction)
            ->paginate(25)
            ->withQueryString();
    }

    public static function filterAccountHeads()
    {
        return AccountHead::query()->orderBy('name')->get();
    }

    /**
     * @return array{transactions: Collection<int, CashTransaction>, has_more: bool}
     */
    public static function recentForAccount(int $userId, int $limit = 10, ?string $mode = null): array
    {
        $query = CashTransaction::query()
            ->with('accountHead')
            ->forUser($userId);

        if ($mode !== null) {
            $query->where('mode', $mode);
        }

        $total = (clone $query)->count();

        return [
            'transactions' => (clone $query)->orderByDesc('id')->limit($limit)->get(),
            'has_more' => $total > $limit,
        ];
    }
}
