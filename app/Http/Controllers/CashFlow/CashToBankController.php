<?php

namespace App\Http\Controllers\CashFlow;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCashToBankRequest;
use App\Models\User;
use App\Services\CashTransactionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CashToBankController extends Controller
{
    public function __construct(
        private CashTransactionService $cashTransactions,
    ) {}

    public function create(): View
    {
        return view('cash-flow.cash-to-bank', [
            'banks' => User::banks()->active()->orderBy('name')->get(),
            'balance' => auth()->user()->freshBalance(),
            'allowPastDates' => config('cashflow.allow_past_dates'),
        ]);
    }

    public function store(StoreCashToBankRequest $request): RedirectResponse
    {
        $bank = User::banks()->active()->findOrFail($request->integer('bank_id'));

        $this->cashTransactions->transferCashToBank(
            doer: $request->user(),
            bank: $bank,
            amount: (float) $request->input('amount'),
            transactionDate: $request->transactionDate(),
            narration: $request->input('narration'),
            entryBy: $request->user()->id,
        );

        return redirect()
            ->to($this->cashToBankRoute())
            ->with('success', 'Cash to bank transfer recorded successfully.');
    }

    private function cashToBankRoute(): string
    {
        return auth()->user()->isAdmin()
            ? route('admin.cash-flow.cash-to-bank.create')
            : route('executive.cash-flow.cash-to-bank.create');
    }
}
