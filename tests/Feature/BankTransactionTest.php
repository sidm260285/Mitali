<?php

namespace Tests\Feature;

use App\Models\AccountHead;
use App\Models\CashTransaction;
use App\Models\User;
use Database\Seeders\SystemAccountHeadSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BankTransactionTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $executive;
    private User $bank;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SystemAccountHeadSeeder::class);
        $this->admin = User::factory()->admin()->create(['balance' => 0]);
        $this->executive = User::factory()->executive()->create(['balance' => 0]);
        $this->bank = User::factory()->bank()->create(['balance' => 0]);
    }

    // ── Page access ──────────────────────────────────────────────────────────

    public function test_admin_can_view_bank_transaction_page(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.bank-flow.transactions.index'))
            ->assertOk()
            ->assertSee('Bank Transactions')
            ->assertSee('Select a bank to view transactions.');
    }

    public function test_executive_cannot_access_bank_transaction_page(): void
    {
        $this->actingAs($this->executive)
            ->get(route('admin.bank-flow.transactions.index'))
            ->assertForbidden();
    }

    public function test_page_is_empty_without_bank_selection(): void
    {
        $creditHead = AccountHead::factory()->credit()->create();
        CashTransaction::factory()->create([
            'user_id' => $this->bank->id,
            'mode' => CashTransaction::MODE_BANK,
            'account_head_id' => $creditHead->id,
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.bank-flow.transactions.index'))
            ->assertOk()
            ->assertSee('Select a bank to view transactions.');
    }

    // ── Listing ───────────────────────────────────────────────────────────────

    public function test_bank_transactions_shown_when_bank_selected(): void
    {
        $creditHead = AccountHead::factory()->credit()->create(['name' => 'Test Head']);
        CashTransaction::factory()->create([
            'user_id' => $this->bank->id,
            'mode' => CashTransaction::MODE_BANK,
            'account_head_id' => $creditHead->id,
            'transaction_id' => 'TXN-001',
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.bank-flow.transactions.index', ['bank_id' => $this->bank->id]))
            ->assertOk()
            ->assertSee('TXN-001')
            ->assertSee('Test Head');
    }

    public function test_cash_mode_transactions_are_excluded(): void
    {
        $creditHead = AccountHead::factory()->credit()->create();
        CashTransaction::factory()->create([
            'user_id' => $this->bank->id,
            'mode' => CashTransaction::MODE_CASH,
            'account_head_id' => $creditHead->id,
            'narration' => 'cash-only-row',
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.bank-flow.transactions.index', ['bank_id' => $this->bank->id]))
            ->assertOk()
            ->assertDontSee('cash-only-row');
    }

    public function test_only_selected_banks_transactions_shown(): void
    {
        $otherBank = User::factory()->bank()->create(['balance' => 0]);
        $creditHead = AccountHead::factory()->credit()->create();

        CashTransaction::factory()->create([
            'user_id' => $this->bank->id,
            'mode' => CashTransaction::MODE_BANK,
            'account_head_id' => $creditHead->id,
            'transaction_id' => 'MINE-001',
        ]);
        CashTransaction::factory()->create([
            'user_id' => $otherBank->id,
            'mode' => CashTransaction::MODE_BANK,
            'account_head_id' => $creditHead->id,
            'transaction_id' => 'OTHER-001',
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.bank-flow.transactions.index', ['bank_id' => $this->bank->id]))
            ->assertOk()
            ->assertSee('MINE-001')
            ->assertDontSee('OTHER-001');
    }

    public function test_ref_transaction_id_column_visible_in_list(): void
    {
        $creditHead = AccountHead::factory()->credit()->create();
        CashTransaction::factory()->create([
            'user_id' => $this->bank->id,
            'mode' => CashTransaction::MODE_BANK,
            'account_head_id' => $creditHead->id,
            'transaction_id' => 'REF-XYZ-999',
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.bank-flow.transactions.index', ['bank_id' => $this->bank->id]))
            ->assertOk()
            ->assertSee('REF-XYZ-999');
    }

    // ── Filters ───────────────────────────────────────────────────────────────

    public function test_filter_by_ref_transaction_id(): void
    {
        $creditHead = AccountHead::factory()->credit()->create();

        CashTransaction::factory()->create([
            'user_id' => $this->bank->id,
            'mode' => CashTransaction::MODE_BANK,
            'account_head_id' => $creditHead->id,
            'transaction_id' => 'MATCH-123',
            'narration' => 'matching row',
        ]);
        CashTransaction::factory()->create([
            'user_id' => $this->bank->id,
            'mode' => CashTransaction::MODE_BANK,
            'account_head_id' => $creditHead->id,
            'transaction_id' => 'OTHER-456',
            'narration' => 'other row',
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.bank-flow.transactions.index', [
                'bank_id' => $this->bank->id,
                'transaction_id' => 'MATCH',
            ]))
            ->assertOk()
            ->assertSee('matching row')
            ->assertDontSee('other row');
    }

    public function test_filter_by_type(): void
    {
        $creditHead = AccountHead::factory()->credit()->create();
        $debitHead = AccountHead::factory()->debit()->create();

        CashTransaction::factory()->create([
            'user_id' => $this->bank->id,
            'mode' => CashTransaction::MODE_BANK,
            'type' => CashTransaction::TYPE_CREDIT,
            'account_head_id' => $creditHead->id,
            'narration' => 'credit row',
        ]);
        CashTransaction::factory()->create([
            'user_id' => $this->bank->id,
            'mode' => CashTransaction::MODE_BANK,
            'type' => CashTransaction::TYPE_DEBIT,
            'account_head_id' => $debitHead->id,
            'narration' => 'debit row',
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.bank-flow.transactions.index', [
                'bank_id' => $this->bank->id,
                'transaction_type' => 'credit',
            ]))
            ->assertOk()
            ->assertSee('credit row')
            ->assertDontSee('debit row');
    }

    public function test_account_head_filter_includes_system_heads(): void
    {
        $systemHead = AccountHead::findSystem(AccountHead::SYSTEM_BANK_TO_BANK);

        $this->actingAs($this->admin)
            ->get(route('admin.bank-flow.transactions.index', ['bank_id' => $this->bank->id]))
            ->assertOk()
            ->assertSee($systemHead->name);
    }

    // ── Detail endpoint ───────────────────────────────────────────────────────

    public function test_detail_returns_bank_transaction_with_entry_by_name(): void
    {
        $creditHead = AccountHead::factory()->credit()->create();
        $transaction = CashTransaction::factory()->create([
            'user_id' => $this->bank->id,
            'mode' => CashTransaction::MODE_BANK,
            'account_head_id' => $creditHead->id,
            'transaction_id' => 'TXN-DETAIL',
            'entry_by' => $this->admin->id,
        ]);

        $this->actingAs($this->admin)
            ->getJson(route('admin.bank-flow.transactions.show', $transaction))
            ->assertOk()
            ->assertJsonPath('transaction.transaction_id', 'TXN-DETAIL')
            ->assertJsonPath('transaction.entry_by', $this->admin->name);
    }

    public function test_detail_shows_dash_when_entry_by_is_zero(): void
    {
        $creditHead = AccountHead::factory()->credit()->create();
        $transaction = CashTransaction::factory()->create([
            'user_id' => $this->bank->id,
            'mode' => CashTransaction::MODE_BANK,
            'account_head_id' => $creditHead->id,
            'entry_by' => 0,
        ]);

        $this->actingAs($this->admin)
            ->getJson(route('admin.bank-flow.transactions.show', $transaction))
            ->assertOk()
            ->assertJsonPath('transaction.entry_by', '—');
    }

    public function test_detail_returns_404_for_cash_mode_transaction(): void
    {
        $creditHead = AccountHead::factory()->credit()->create();
        $cashTransaction = CashTransaction::factory()->create([
            'user_id' => $this->admin->id,
            'mode' => CashTransaction::MODE_CASH,
            'account_head_id' => $creditHead->id,
        ]);

        $this->actingAs($this->admin)
            ->getJson(route('admin.bank-flow.transactions.show', $cashTransaction))
            ->assertNotFound();
    }

    public function test_detail_includes_linked_transfer_entries(): void
    {
        $head = AccountHead::findSystem(AccountHead::SYSTEM_BANK_TO_BANK);
        $fromBank = User::factory()->bank()->create(['balance' => 5000]);
        $toBank = User::factory()->bank()->create(['balance' => 0]);
        $groupId = (string) \Illuminate\Support\Str::uuid();

        $debit = CashTransaction::factory()->create([
            'user_id' => $fromBank->id,
            'mode' => CashTransaction::MODE_BANK,
            'type' => CashTransaction::TYPE_DEBIT,
            'account_head_id' => $head->id,
            'transfer_group_id' => $groupId,
            'entry_by' => $this->admin->id,
        ]);
        CashTransaction::factory()->create([
            'user_id' => $toBank->id,
            'mode' => CashTransaction::MODE_BANK,
            'type' => CashTransaction::TYPE_CREDIT,
            'account_head_id' => $head->id,
            'transfer_group_id' => $groupId,
            'entry_by' => $this->admin->id,
        ]);

        $this->actingAs($this->admin)
            ->getJson(route('admin.bank-flow.transactions.show', $debit))
            ->assertOk()
            ->assertJsonCount(2, 'linked');
    }
}
