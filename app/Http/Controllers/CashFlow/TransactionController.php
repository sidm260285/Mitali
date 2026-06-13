<?php

namespace App\Http\Controllers\CashFlow;

use App\Http\Controllers\Controller;
use App\Models\CashTransaction;
use App\Models\User;
use App\Support\CashTransactionListQuery;
use App\Support\MoneyHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TransactionController extends Controller
{
    public function index(Request $request): View
    {
        $transactions = CashTransactionListQuery::build($request, $request->user()->id);

        return view('cash-flow.transactions.index', [
            'transactions' => $transactions,
            'accountHeads' => CashTransactionListQuery::filterAccountHeads(),
            'title' => 'Show Transaction',
            'showExecutiveFilter' => false,
        ]);
    }

    public function executiveIndex(Request $request): View
    {
        $executives = User::executives()->orderBy('name')->get();
        $executiveId = $request->integer('executive_id');

        $transactions = $executiveId
            ? CashTransactionListQuery::build($request, $executiveId)
            : null;

        return view('cash-flow.transactions.executive-index', [
            'transactions' => $transactions,
            'accountHeads' => CashTransactionListQuery::filterAccountHeads(),
            'executives' => $executives,
            'selectedExecutiveId' => $executiveId ?: null,
        ]);
    }

    public function show(CashTransaction $cashTransaction): JsonResponse
    {
        $user = auth()->user();

        if ($user->isExecutive() && $cashTransaction->user_id !== $user->id) {
            abort(403);
        }

        $cashTransaction->load(['accountHead', 'user']);
        $linked = $cashTransaction->linkedTransactions();

        return response()->json([
            'transaction' => [
                'id' => $cashTransaction->id,
                'transaction_date' => $cashTransaction->transaction_date->format('d M Y'),
                'type' => $cashTransaction->typeLabel(),
                'account_head' => $cashTransaction->accountHead->name,
                'amount' => MoneyHelper::format($cashTransaction->amount),
                'narration' => $cashTransaction->narration ?? '—',
                'current_balance' => MoneyHelper::format($cashTransaction->current_balance),
                'created_by' => $cashTransaction->user->name,
                'recorded_at' => $cashTransaction->created_at->format('d M Y, h:i A'),
                'is_transfer' => $cashTransaction->transfer_group_id !== null,
            ],
            'linked' => $linked->map(fn (CashTransaction $row) => [
                'id' => $row->id,
                'user' => $row->user->name,
                'type' => $row->typeLabel(),
                'account_head' => $row->accountHead->name,
                'amount' => MoneyHelper::format($row->amount),
                'current_balance' => $this->visibleLinkedBalance($user, $row),
            ])->values(),
        ]);
    }

    private function visibleLinkedBalance(User $viewer, CashTransaction $row): ?string
    {
        if ($viewer->isAdmin() || $row->user_id === $viewer->id) {
            return MoneyHelper::format($row->current_balance);
        }

        return null;
    }
}
