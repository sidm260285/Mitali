<?php

namespace App\Http\Controllers\BankFlow;

use App\Http\Controllers\Controller;
use App\Models\CashTransaction;
use App\Models\User;
use App\Support\CashTransactionListQuery;
use App\Support\MoneyHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BankTransactionController extends Controller
{
    public function index(Request $request): View
    {
        $banks = User::banks()->orderBy('name')->get();
        $bankId = $request->integer('bank_id');

        $transactions = $bankId
            ? CashTransactionListQuery::build($request, $bankId, CashTransaction::MODE_BANK)
            : null;

        return view('bank-flow.transactions.index', [
            'banks' => $banks,
            'selectedBankId' => $bankId ?: null,
            'transactions' => $transactions,
            'accountHeads' => CashTransactionListQuery::filterAccountHeads(),
        ]);
    }

    public function show(CashTransaction $bankTransaction): JsonResponse
    {
        if ($bankTransaction->mode !== CashTransaction::MODE_BANK) {
            abort(404);
        }

        $bankTransaction->load(['accountHead', 'user']);
        $linked = $bankTransaction->linkedTransactions();

        $entryByName = $bankTransaction->entry_by
            ? (User::find($bankTransaction->entry_by)?->name ?? '—')
            : '—';

        return response()->json([
            'transaction' => [
                'id' => $bankTransaction->id,
                'transaction_date' => $bankTransaction->transaction_date->format('d M Y'),
                'type' => $bankTransaction->typeLabel(),
                'account_head' => $bankTransaction->accountHead->name,
                'amount' => MoneyHelper::format($bankTransaction->amount),
                'transaction_id' => $bankTransaction->transaction_id ?? '—',
                'narration' => $bankTransaction->narration ?? '—',
                'current_balance' => MoneyHelper::format($bankTransaction->current_balance),
                'entry_by' => $entryByName,
                'recorded_at' => $bankTransaction->created_at->format('d M Y, h:i A'),
                'is_transfer' => $bankTransaction->transfer_group_id !== null,
            ],
            'salary' => $bankTransaction->salaryDetail(),
            'linked' => $linked->map(fn (CashTransaction $row) => [
                'id' => $row->id,
                'user' => $row->user->name,
                'type' => $row->typeLabel(),
                'account_head' => $row->accountHead->name,
                'amount' => MoneyHelper::format($row->amount),
                'current_balance' => MoneyHelper::format($row->current_balance),
            ])->values(),
        ]);
    }
}
