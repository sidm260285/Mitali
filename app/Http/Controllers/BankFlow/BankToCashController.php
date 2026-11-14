<?php

namespace App\Http\Controllers\BankFlow;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBankToCashRequest;
use App\Models\User;
use App\Services\CashTransactionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class BankToCashController extends Controller
{
    public function __construct(
        private CashTransactionService $cashTransactions,
    ) {}

    public function create(): View
    {
        $selectedBankId = old('bank_id') ?? session('last_bank_id');
        $selectedBank = $selectedBankId ? User::banks()->active()->find($selectedBankId) : null;

        return view('bank-flow.bank-to-cash', [
            'banks' => User::banks()->active()->orderBy('name')->get(),
            'allowPastDates' => config('cashflow.allow_past_dates'),
            'selectedBankId' => $selectedBankId,
            'selectedBankBalance' => $selectedBank ? $selectedBank->freshBalance() : null,
        ]);
    }

    public function store(StoreBankToCashRequest $request): RedirectResponse
    {
        $bank = User::banks()->active()->findOrFail($request->integer('bank_id'));

        $this->cashTransactions->transferBankToCash(
            bank: $bank,
            doer: $request->user(),
            amount: (float) $request->input('amount'),
            transactionDate: $request->transactionDate(),
            transactionId: $request->input('transaction_id'),
            narration: $request->input('narration'),
            entryBy: $request->user()->id,
        );

        return redirect()
            ->to($this->bankToCashRoute())
            ->with('success', 'Bank to cash withdrawal recorded successfully.')
            ->with('last_bank_id', $request->integer('bank_id'));
    }

    private function bankToCashRoute(): string
    {
        return auth()->user()->isAdmin()
            ? route('admin.bank-flow.bank-to-cash.create')
            : route('executive.bank-flow.bank-to-cash.create');
    }
}
