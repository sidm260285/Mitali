<?php

namespace App\Http\Controllers\CashFlow;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAdminTransferRequest;
use App\Http\Requests\StoreExecutiveTransferRequest;
use App\Http\Requests\StoreExecutiveToAdminRequest;
use App\Models\User;
use App\Services\CashTransactionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TransferController extends Controller
{
    public function __construct(
        private CashTransactionService $cashTransactions,
    ) {}

    public function createAdminTransfer(): View
    {
        return view('cash-flow.transfers.admin-to-executive', [
            'executives' => User::executives()->active()->orderBy('name')->get(),
            'balance' => auth()->user()->freshBalance(),
            'allowPastDates' => config('cashflow.allow_past_dates'),
        ]);
    }

    public function storeAdminTransfer(StoreAdminTransferRequest $request): RedirectResponse
    {
        $executive = User::executives()->active()->findOrFail($request->integer('executive_id'));

        $this->cashTransactions->transferAdminToExecutive(
            admin: $request->user(),
            executive: $executive,
            amount: (float) $request->input('amount'),
            transactionDate: $request->transactionDate(),
            narration: $request->input('narration'),
        );

        return redirect()
            ->route('admin.cash-flow.transfer-to-executive.create')
            ->with('success', 'Transfer to executive recorded successfully.');
    }

    public function createExecutiveTransfer(): View
    {
        return view('cash-flow.transfers.executive-to-executive', [
            'executives' => User::executives()->active()
                ->where('id', '!=', auth()->id())
                ->orderBy('name')
                ->get(),
            'balance' => auth()->user()->freshBalance(),
            'allowPastDates' => config('cashflow.allow_past_dates'),
        ]);
    }

    public function storeExecutiveTransfer(StoreExecutiveTransferRequest $request): RedirectResponse
    {
        $receiver = User::executives()->active()->findOrFail($request->integer('executive_id'));

        $this->cashTransactions->transferExecutiveToExecutive(
            sender: $request->user(),
            receiver: $receiver,
            amount: (float) $request->input('amount'),
            transactionDate: $request->transactionDate(),
            narration: $request->input('narration'),
        );

        return redirect()
            ->route('executive.cash-flow.transfer-to-executive.create')
            ->with('success', 'Transfer to executive recorded successfully.');
    }

    public function createExecutiveToAdmin(): View
    {
        $admin = User::query()->where('role', User::ROLE_ADMIN)->firstOrFail();

        return view('cash-flow.transfers.executive-to-admin', [
            'admin' => $admin,
            'balance' => auth()->user()->freshBalance(),
            'allowPastDates' => config('cashflow.allow_past_dates'),
        ]);
    }

    public function storeExecutiveToAdmin(StoreExecutiveToAdminRequest $request): RedirectResponse
    {
        $admin = User::query()->where('role', User::ROLE_ADMIN)->firstOrFail();

        $this->cashTransactions->transferExecutiveToAdmin(
            executive: $request->user(),
            admin: $admin,
            amount: (float) $request->input('amount'),
            transactionDate: $request->transactionDate(),
            narration: $request->input('narration'),
        );

        return redirect()
            ->route('executive.cash-flow.transfer-to-admin.create')
            ->with('success', 'Transfer to admin recorded successfully.');
    }
}
