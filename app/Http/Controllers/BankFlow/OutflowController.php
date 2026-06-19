<?php

namespace App\Http\Controllers\BankFlow;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBankOutflowRequest;
use App\Models\AccountHead;
use App\Models\User;
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
        $selectedBankId = old('bank_id') ?? session('last_bank_id');
        $selectedBank = $selectedBankId ? User::banks()->active()->find($selectedBankId) : null;

        return view('bank-flow.outflow', [
            'banks' => User::banks()->active()->orderBy('name')->get(),
            'accountHeads' => AccountHead::forDropdown(AccountHead::TYPE_DEBIT)->get(),
            'allowPastDates' => config('cashflow.allow_past_dates'),
            'selectedBankId' => $selectedBankId,
            'selectedBankBalance' => $selectedBank ? $selectedBank->freshBalance() : null,
        ]);
    }

    public function store(StoreBankOutflowRequest $request): RedirectResponse
    {
        $bank = User::banks()->active()->findOrFail($request->integer('bank_id'));

        $this->cashTransactions->recordBankOutflow(
            bank: $bank,
            accountHeadId: $request->integer('account_head_id'),
            amount: (float) $request->input('amount'),
            transactionDate: $request->transactionDate(),
            transactionId: $request->input('transaction_id'),
            narration: $request->input('narration'),
            entryBy: $request->user()->id,
        );

        return redirect()
            ->to($this->outflowRoute())
            ->with('success', 'Bank outflow recorded successfully.')
            ->with('last_bank_id', $request->integer('bank_id'));
    }

    private function outflowRoute(): string
    {
        return auth()->user()->isAdmin()
            ? route('admin.bank-flow.outflow.create')
            : route('executive.bank-flow.outflow.create');
    }
}
