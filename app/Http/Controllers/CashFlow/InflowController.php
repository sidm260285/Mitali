<?php

namespace App\Http\Controllers\CashFlow;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInflowRequest;
use App\Models\AccountHead;
use App\Services\CashTransactionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class InflowController extends Controller
{
    public function __construct(
        private CashTransactionService $cashTransactions,
    ) {}

    public function create(): View
    {
        $user = auth()->user();

        return view('cash-flow.inflow', [
            'accountHeads' => AccountHead::forDropdown(AccountHead::TYPE_CREDIT)->get(),
            'balance' => $user->freshBalance(),
            'allowPastDates' => config('cashflow.allow_past_dates'),
        ]);
    }

    public function store(StoreInflowRequest $request): RedirectResponse
    {
        $this->cashTransactions->recordInflow(
            user: $request->user(),
            accountHeadId: $request->integer('account_head_id'),
            amount: (float) $request->input('amount'),
            transactionDate: $request->transactionDate(),
            narration: $request->input('narration'),
        );

        return redirect()
            ->to($this->inflowRoute())
            ->with('success', 'Inflow recorded successfully.');
    }

    private function inflowRoute(): string
    {
        return auth()->user()->isAdmin()
            ? route('admin.cash-flow.inflow.create')
            : route('executive.cash-flow.inflow.create');
    }
}
