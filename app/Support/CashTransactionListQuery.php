<?php

namespace App\Support;

use App\Models\AccountHead;
use App\Models\CashTransaction;
use Illuminate\Http\Request;

class CashTransactionListQuery
{
    public static function build(Request $request, int $userId)
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

        if ($request->filled('account_head_id')) {
            $query->where('account_head_id', $request->integer('account_head_id'));
        }

        if ($request->filled('transaction_type')) {
            $query->where('type', $request->string('transaction_type')->toString());
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
}
