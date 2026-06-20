<?php

namespace App\Services;

use App\Models\AccountHead;
use App\Models\CashTransaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CashTransactionService
{
    public function recordBankInflow(User $bank, int $accountHeadId, float $amount, string $transactionDate, string $transactionId, ?string $narration, int $entryBy): CashTransaction
    {
        $head = $this->resolveUserHead($accountHeadId, AccountHead::TYPE_CREDIT);

        return $this->insertSingle(
            user: $bank,
            head: $head,
            type: CashTransaction::TYPE_CREDIT,
            mode: CashTransaction::MODE_BANK,
            amount: $amount,
            transactionDate: $transactionDate,
            narration: $narration,
            transactionId: $transactionId,
            entryBy: $entryBy,
        );
    }

    public function recordBankOutflow(User $bank, int $accountHeadId, float $amount, string $transactionDate, string $transactionId, ?string $narration, int $entryBy): CashTransaction
    {
        $head = $this->resolveUserHead($accountHeadId, AccountHead::TYPE_DEBIT);
        $this->assertSufficientBalance($bank, $amount);

        return $this->insertSingle(
            user: $bank,
            head: $head,
            type: CashTransaction::TYPE_DEBIT,
            mode: CashTransaction::MODE_BANK,
            amount: $amount,
            transactionDate: $transactionDate,
            narration: $narration,
            transactionId: $transactionId,
            entryBy: $entryBy,
        );
    }

    public function recordInflow(User $user, int $accountHeadId, float $amount, string $transactionDate, ?string $narration): CashTransaction
    {
        $head = $this->resolveUserHead($accountHeadId, AccountHead::TYPE_CREDIT);

        return $this->insertSingle(
            user: $user,
            head: $head,
            type: CashTransaction::TYPE_CREDIT,
            mode: CashTransaction::MODE_CASH,
            amount: $amount,
            transactionDate: $transactionDate,
            narration: $narration,
        );
    }

    public function recordOutflow(User $user, int $accountHeadId, float $amount, string $transactionDate, ?string $narration): CashTransaction
    {
        $head = $this->resolveUserHead($accountHeadId, AccountHead::TYPE_DEBIT);
        $this->assertSufficientBalance($user, $amount);

        return $this->insertSingle(
            user: $user,
            head: $head,
            type: CashTransaction::TYPE_DEBIT,
            mode: CashTransaction::MODE_CASH,
            amount: $amount,
            transactionDate: $transactionDate,
            narration: $narration,
        );
    }

    public function transferAdminToExecutive(User $admin, User $executive, float $amount, string $transactionDate, ?string $narration): void
    {
        $this->assertActiveExecutive($executive);
        $this->assertSufficientBalance($admin, $amount);

        $this->insertPair(
            debitUser: $admin,
            debitHead: AccountHead::findSystem(AccountHead::SYSTEM_ADMIN_TO_EXECUTIVE),
            creditUser: $executive,
            creditHead: AccountHead::findSystem(AccountHead::SYSTEM_RECEIVE_FROM_ADMIN),
            debitMode: CashTransaction::MODE_CASH,
            creditMode: CashTransaction::MODE_CASH,
            amount: $amount,
            transactionDate: $transactionDate,
            narration: $narration,
        );
    }

    public function transferExecutiveToExecutive(User $sender, User $receiver, float $amount, string $transactionDate, ?string $narration): void
    {
        $this->assertActiveExecutive($sender);
        $this->assertActiveExecutive($receiver);

        if ($sender->id === $receiver->id) {
            throw ValidationException::withMessages([
                'executive_id' => 'You cannot transfer to yourself.',
            ]);
        }

        $this->assertSufficientBalance($sender, $amount);

        $head = AccountHead::findSystem(AccountHead::SYSTEM_EXECUTIVE_TO_EXECUTIVE);

        $this->insertPair(
            debitUser: $sender,
            debitHead: $head,
            creditUser: $receiver,
            creditHead: $head,
            debitMode: CashTransaction::MODE_CASH,
            creditMode: CashTransaction::MODE_CASH,
            amount: $amount,
            transactionDate: $transactionDate,
            narration: $narration,
        );
    }

    public function transferBankToCash(User $bank, User $doer, float $amount, string $transactionDate, string $transactionId, ?string $narration, int $entryBy): void
    {
        $this->assertSufficientBalance($bank, $amount);

        $debitHead = $doer->isAdmin()
            ? AccountHead::findSystem(AccountHead::SYSTEM_BANK_TO_ADMIN)
            : AccountHead::findSystem(AccountHead::SYSTEM_BANK_TO_EXECUTIVE);

        $creditHead = AccountHead::findSystem(AccountHead::SYSTEM_WITHDRAWN_FROM_BANK);

        $this->insertPair(
            debitUser: $bank,
            debitHead: $debitHead,
            creditUser: $doer,
            creditHead: $creditHead,
            debitMode: CashTransaction::MODE_BANK,
            creditMode: CashTransaction::MODE_CASH,
            amount: $amount,
            transactionDate: $transactionDate,
            narration: $narration,
            debitEntryBy: $entryBy,
            debitTransactionId: $transactionId,
        );
    }

    public function transferExecutiveToAdmin(User $executive, User $admin, float $amount, string $transactionDate, ?string $narration): void
    {
        $this->assertActiveExecutive($executive);

        if (! $admin->isAdmin()) {
            throw ValidationException::withMessages([
                'admin' => 'Invalid admin account.',
            ]);
        }

        $this->assertSufficientBalance($executive, $amount);

        $this->insertPair(
            debitUser: $executive,
            debitHead: AccountHead::findSystem(AccountHead::SYSTEM_EXECUTIVE_TO_ADMIN),
            creditUser: $admin,
            creditHead: AccountHead::findSystem(AccountHead::SYSTEM_EXECUTIVE_TO_ADMIN),
            debitMode: CashTransaction::MODE_CASH,
            creditMode: CashTransaction::MODE_CASH,
            amount: $amount,
            transactionDate: $transactionDate,
            narration: $narration,
        );
    }

    public function transferCashToBank(User $doer, User $bank, float $amount, string $transactionDate, ?string $narration, int $entryBy): void
    {
        $this->assertSufficientBalance($doer, $amount);

        $debitHead = $doer->isAdmin()
            ? AccountHead::findSystem(AccountHead::SYSTEM_ADMIN_TO_BANK)
            : AccountHead::findSystem(AccountHead::SYSTEM_EXECUTIVE_TO_BANK);

        $creditHead = $doer->isAdmin()
            ? AccountHead::findSystem(AccountHead::SYSTEM_DEPOSIT_BY_ADMIN)
            : AccountHead::findSystem(AccountHead::SYSTEM_DEPOSIT_BY_EXECUTIVE);

        $this->insertPair(
            debitUser: $doer,
            debitHead: $debitHead,
            creditUser: $bank,
            creditHead: $creditHead,
            debitMode: CashTransaction::MODE_CASH,
            creditMode: CashTransaction::MODE_BANK,
            amount: $amount,
            transactionDate: $transactionDate,
            narration: $narration,
            creditEntryBy: $entryBy,
        );
    }

    private function insertSingle(
        User $user,
        AccountHead $head,
        string $type,
        string $mode,
        float $amount,
        string $transactionDate,
        ?string $narration,
        ?string $transactionId = null,
        int $entryBy = 0,
    ): CashTransaction {
        return DB::transaction(function () use ($user, $head, $type, $mode, $amount, $transactionDate, $narration, $transactionId, $entryBy) {
            $transaction = CashTransaction::create([
                'user_id' => $user->id,
                'account_head_id' => $head->id,
                'type' => $type,
                'mode' => $mode,
                'amount' => $amount,
                'transaction_date' => $transactionDate,
                'narration' => $narration,
                'transaction_id' => $transactionId,
                'entry_by' => $entryBy,
            ]);

            return $transaction->fresh(['accountHead', 'user']);
        });
    }

    private function insertPair(
        User $debitUser,
        AccountHead $debitHead,
        User $creditUser,
        AccountHead $creditHead,
        string $debitMode,
        string $creditMode,
        float $amount,
        string $transactionDate,
        ?string $narration,
        int $debitEntryBy = 0,
        int $creditEntryBy = 0,
        ?string $debitTransactionId = null,
    ): void {
        DB::transaction(function () use ($debitUser, $debitHead, $creditUser, $creditHead, $debitMode, $creditMode, $amount, $transactionDate, $narration, $debitEntryBy, $creditEntryBy, $debitTransactionId) {
            $groupId = (string) Str::uuid();

            CashTransaction::create([
                'user_id' => $debitUser->id,
                'account_head_id' => $debitHead->id,
                'type' => CashTransaction::TYPE_DEBIT,
                'mode' => $debitMode,
                'amount' => $amount,
                'transaction_date' => $transactionDate,
                'narration' => $narration,
                'transfer_group_id' => $groupId,
                'entry_by' => $debitEntryBy,
                'transaction_id' => $debitTransactionId,
            ]);

            CashTransaction::create([
                'user_id' => $creditUser->id,
                'account_head_id' => $creditHead->id,
                'type' => CashTransaction::TYPE_CREDIT,
                'mode' => $creditMode,
                'amount' => $amount,
                'transaction_date' => $transactionDate,
                'narration' => $narration,
                'transfer_group_id' => $groupId,
                'entry_by' => $creditEntryBy,
            ]);
        });
    }

    private function resolveUserHead(int $accountHeadId, string $expectedType): AccountHead
    {
        $head = AccountHead::query()->userManaged()->find($accountHeadId);

        if (! $head || $head->type !== $expectedType) {
            throw ValidationException::withMessages([
                'account_head_id' => 'Invalid accounts head selected.',
            ]);
        }

        return $head;
    }

    private function assertSufficientBalance(User $user, float $amount): void
    {
        $user->refresh();

        if ((float) $user->balance < $amount) {
            throw ValidationException::withMessages([
                'amount' => 'Amount exceeds available balance of '.number_format((float) $user->balance, 2).'.',
            ]);
        }
    }

    private function assertActiveExecutive(User $user): void
    {
        if (! $user->isExecutive() || ! $user->is_active) {
            throw ValidationException::withMessages([
                'executive_id' => 'Selected executive is not active.',
            ]);
        }
    }
}
