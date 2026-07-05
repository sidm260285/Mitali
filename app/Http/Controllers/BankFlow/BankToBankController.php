<?php

namespace App\Http\Controllers\BankFlow;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBankToBankRequest;
use App\Models\User;
use App\Services\CashTransactionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class BankToBankController extends Controller
{
    public function __construct(
        private CashTransactionService $cashTransactions,
    ) {}

    public function create(): View
    {
        $selectedFromBankId = old('from_bank_id') ?? session('last_from_bank_id');
        $selectedFromBank = $selectedFromBankId ? User::banks()->active()->find($selectedFromBankId) : null;

        return view('bank-flow.bank-to-bank', [
            'banks' => User::banks()->active()->orderBy('name')->get(),
            'allowPastDates' => config('cashflow.allow_past_dates'),
            'selectedFromBankId' => $selectedFromBankId,
            'selectedFromBankBalance' => $selectedFromBank ? $selectedFromBank->freshBalance() : null,
        ]);
    }

    public function store(StoreBankToBankRequest $request): RedirectResponse
    {
        $fromBank = User::banks()->active()->findOrFail($request->integer('from_bank_id'));
        $toBank = User::banks()->active()->findOrFail($request->integer('to_bank_id'));

        $this->cashTransactions->transferBankToBank(
            fromBank: $fromBank,
            toBank: $toBank,
            amount: (float) $request->input('amount'),
            transactionDate: $request->transactionDate(),
            transactionId: $request->input('transaction_id'),
            narration: $request->input('narration'),
            entryBy: $request->user()->id,
        );

        return redirect()
            ->route('admin.bank-flow.bank-to-bank.create')
            ->with('success', 'Bank to bank transfer recorded successfully.')
            ->with('last_from_bank_id', $request->integer('from_bank_id'));
    }
}
