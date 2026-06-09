<?php

namespace App\Http\Controllers\CashFlow;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOutflowRequest;
use App\Models\AccountHead;
use App\Services\CashTransactionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class OutflowController extends Controller
{
    public function __construct(
        private CashTransactionService $cashTransactions,
    ) {}

    public function create(): View
    {
        $user = auth()->user();

        return view('cash-flow.outflow', [
            'accountHeads' => AccountHead::forDropdown(AccountHead::TYPE_DEBIT)->get(),
            'balance' => $user->freshBalance(),
            'allowPastDates' => config('cashflow.allow_past_dates'),
        ]);
    }

    public function store(StoreOutflowRequest $request): RedirectResponse
    {
        $this->cashTransactions->recordOutflow(
            user: $request->user(),
            accountHeadId: $request->integer('account_head_id'),
            amount: (float) $request->input('amount'),
            transactionDate: $request->transactionDate(),
            narration: $request->input('narration'),
        );

        return redirect()
            ->to($this->outflowRoute())
            ->with('success', 'Outflow recorded successfully.');
    }

    private function outflowRoute(): string
    {
        return auth()->user()->isAdmin()
            ? route('admin.cash-flow.outflow.create')
            : route('executive.cash-flow.outflow.create');
    }
}
