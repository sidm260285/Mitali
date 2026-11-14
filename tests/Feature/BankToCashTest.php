<?php

namespace Tests\Feature;

use App\Models\AccountHead;
use App\Models\CashTransaction;
use App\Models\User;
use Database\Seeders\SystemAccountHeadSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BankToCashTest extends TestCase
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
        $this->bank = User::factory()->bank()->create(['balance' => 5000]);
    }

    // ── Page access ──────────────────────────────────────────────────────────

    public function test_admin_can_view_bank_to_cash_page(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.bank-flow.bank-to-cash.create'))
            ->assertOk()
            ->assertSee('Bank to Cash')
            ->assertSee($this->bank->name);
    }

    public function test_executive_can_view_bank_to_cash_page(): void
    {
        $this->actingAs($this->executive)
            ->get(route('executive.bank-flow.bank-to-cash.create'))
            ->assertOk()
            ->assertSee('Bank to Cash')
            ->assertSee($this->bank->name);
    }

    public function test_bank_dropdown_shows_only_active_banks(): void
    {
        $inactiveBank = User::factory()->bank()->inactive()->create();

        $this->actingAs($this->admin)
            ->get(route('admin.bank-flow.bank-to-cash.create'))
            ->assertOk()
            ->assertSee($this->bank->name)
            ->assertDontSee($inactiveBank->name);
    }

    public function test_bank_dropdown_shows_last_5_digits_of_account_no(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.bank-flow.bank-to-cash.create'))
            ->assertOk()
            ->assertSee(substr($this->bank->account_no, -5));
    }

    // ── Admin store ──────────────────────────────────────────────────────────

    public function test_admin_can_record_bank_to_cash(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.bank-flow.bank-to-cash.store'), [
                'bank_id' => $this->bank->id,
                'amount' => '2000.00',
                'transaction_date' => now()->toDateString(),
                'transaction_id' => 'TXN001',
                'narration' => 'Withdrawal test',
            ])
            ->assertRedirect(route('admin.bank-flow.bank-to-cash.create'))
            ->assertSessionHas('success');

        // Debit row: bank side
        $debitRow = CashTransaction::where('user_id', $this->bank->id)->first();
        $this->assertNotNull($debitRow);
        $this->assertEquals(CashTransaction::TYPE_DEBIT, $debitRow->type);
        $this->assertEquals(CashTransaction::MODE_BANK, $debitRow->mode);
        $this->assertEquals('2000.00', $debitRow->amount);
        $this->assertEquals('TXN001', $debitRow->transaction_id);
        $this->assertEquals($this->admin->id, $debitRow->entry_by);

        // Credit row: doer side
        $creditRow = CashTransaction::where('user_id', $this->admin->id)->first();
        $this->assertNotNull($creditRow);
        $this->assertEquals(CashTransaction::TYPE_CREDIT, $creditRow->type);
        $this->assertEquals(CashTransaction::MODE_CASH, $creditRow->mode);
        $this->assertEquals('2000.00', $creditRow->amount);
        $this->assertNull($creditRow->transaction_id);
        $this->assertEquals(0, $creditRow->entry_by);

        // Linked by same transfer_group_id
        $this->assertEquals($debitRow->transfer_group_id, $creditRow->transfer_group_id);
        $this->assertNotNull($debitRow->transfer_group_id);
    }

    public function test_admin_debit_row_uses_bank_to_admin_head(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.bank-flow.bank-to-cash.store'), [
                'bank_id' => $this->bank->id,
                'amount' => '1000.00',
                'transaction_date' => now()->toDateString(),
                'transaction_id' => 'TXN002',
            ])
            ->assertRedirect();

        $debitRow = CashTransaction::where('user_id', $this->bank->id)->first();
        $expectedHead = AccountHead::findSystem(AccountHead::SYSTEM_BANK_TO_ADMIN);
        $this->assertEquals($expectedHead->id, $debitRow->account_head_id);
    }

    public function test_admin_credit_row_uses_withdrawn_from_bank_head(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.bank-flow.bank-to-cash.store'), [
                'bank_id' => $this->bank->id,
                'amount' => '1000.00',
                'transaction_date' => now()->toDateString(),
                'transaction_id' => 'TXN003',
            ])
            ->assertRedirect();

        $creditRow = CashTransaction::where('user_id', $this->admin->id)->first();
        $expectedHead = AccountHead::findSystem(AccountHead::SYSTEM_WITHDRAWN_FROM_BANK);
        $this->assertEquals($expectedHead->id, $creditRow->account_head_id);
    }

    // ── Executive store ───────────────────────────────────────────────────────

    public function test_executive_can_record_bank_to_cash(): void
    {
        $this->actingAs($this->executive)
            ->post(route('executive.bank-flow.bank-to-cash.store'), [
                'bank_id' => $this->bank->id,
                'amount' => '1500.00',
                'transaction_date' => now()->toDateString(),
                'transaction_id' => 'TXN004',
            ])
            ->assertRedirect(route('executive.bank-flow.bank-to-cash.create'))
            ->assertSessionHas('success');

        $debitRow = CashTransaction::where('user_id', $this->bank->id)->first();
        $this->assertEquals(CashTransaction::MODE_BANK, $debitRow->mode);
        $this->assertEquals('TXN004', $debitRow->transaction_id);
        $this->assertEquals($this->executive->id, $debitRow->entry_by);

        $creditRow = CashTransaction::where('user_id', $this->executive->id)->first();
        $this->assertEquals(CashTransaction::MODE_CASH, $creditRow->mode);
        $this->assertEquals(0, $creditRow->entry_by);
    }

    public function test_executive_debit_row_uses_bank_to_executive_head(): void
    {
        $this->actingAs($this->executive)
            ->post(route('executive.bank-flow.bank-to-cash.store'), [
                'bank_id' => $this->bank->id,
                'amount' => '500.00',
                'transaction_date' => now()->toDateString(),
                'transaction_id' => 'TXN005',
            ])
            ->assertRedirect();

        $debitRow = CashTransaction::where('user_id', $this->bank->id)->first();
        $expectedHead = AccountHead::findSystem(AccountHead::SYSTEM_BANK_TO_EXECUTIVE);
        $this->assertEquals($expectedHead->id, $debitRow->account_head_id);
    }

    public function test_executive_credit_row_uses_withdrawn_from_bank_head(): void
    {
        $this->actingAs($this->executive)
            ->post(route('executive.bank-flow.bank-to-cash.store'), [
                'bank_id' => $this->bank->id,
                'amount' => '500.00',
                'transaction_date' => now()->toDateString(),
                'transaction_id' => 'TXN006',
            ])
            ->assertRedirect();

        $creditRow = CashTransaction::where('user_id', $this->executive->id)->first();
        $expectedHead = AccountHead::findSystem(AccountHead::SYSTEM_WITHDRAWN_FROM_BANK);
        $this->assertEquals($expectedHead->id, $creditRow->account_head_id);
    }

    // ── Balance checks ────────────────────────────────────────────────────────

    public function test_balances_are_updated_after_withdrawal(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.bank-flow.bank-to-cash.store'), [
                'bank_id' => $this->bank->id,
                'amount' => '2000.00',
                'transaction_date' => now()->toDateString(),
                'transaction_id' => 'TXN007',
            ])
            ->assertRedirect();

        $this->assertEquals('3000.00', $this->bank->fresh()->balance);
        $this->assertEquals('2000.00', $this->admin->fresh()->balance);
    }

    public function test_insufficient_bank_balance_is_rejected(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.bank-flow.bank-to-cash.store'), [
                'bank_id' => $this->bank->id,
                'amount' => '9999.00',
                'transaction_date' => now()->toDateString(),
                'transaction_id' => 'TXN008',
            ])
            ->assertSessionHasErrors('amount');

        $this->assertDatabaseCount('cash_transactions', 0);
    }

    // ── Validation ────────────────────────────────────────────────────────────

    public function test_bank_id_is_required(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.bank-flow.bank-to-cash.store'), [
                'amount' => '1000.00',
                'transaction_date' => now()->toDateString(),
                'transaction_id' => 'TXN009',
            ])
            ->assertSessionHasErrors('bank_id');
    }

    public function test_amount_is_required(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.bank-flow.bank-to-cash.store'), [
                'bank_id' => $this->bank->id,
                'transaction_date' => now()->toDateString(),
                'transaction_id' => 'TXN010',
            ])
            ->assertSessionHasErrors('amount');
    }

    public function test_transaction_id_is_required(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.bank-flow.bank-to-cash.store'), [
                'bank_id' => $this->bank->id,
                'amount' => '1000.00',
                'transaction_date' => now()->toDateString(),
            ])
            ->assertSessionHasErrors('transaction_id');
    }

    public function test_inactive_bank_is_rejected(): void
    {
        $inactiveBank = User::factory()->bank()->inactive()->create(['balance' => 5000]);

        $this->actingAs($this->admin)
            ->post(route('admin.bank-flow.bank-to-cash.store'), [
                'bank_id' => $inactiveBank->id,
                'amount' => '1000.00',
                'transaction_date' => now()->toDateString(),
                'transaction_id' => 'TXN011',
            ])
            ->assertSessionHasErrors('bank_id');
    }

    public function test_duplicate_transaction_id_for_same_bank_is_rejected(): void
    {
        $payload = [
            'bank_id' => $this->bank->id,
            'amount' => '500.00',
            'transaction_date' => now()->toDateString(),
            'transaction_id' => 'DUPID',
        ];

        $this->actingAs($this->admin)
            ->post(route('admin.bank-flow.bank-to-cash.store'), $payload)
            ->assertRedirect();

        $this->actingAs($this->admin)
            ->post(route('admin.bank-flow.bank-to-cash.store'), $payload)
            ->assertSessionHasErrors('transaction_id');
    }

    public function test_same_transaction_id_allowed_for_different_banks(): void
    {
        $bank2 = User::factory()->bank()->create(['balance' => 5000]);

        $this->actingAs($this->admin)
            ->post(route('admin.bank-flow.bank-to-cash.store'), [
                'bank_id' => $this->bank->id,
                'amount' => '500.00',
                'transaction_date' => now()->toDateString(),
                'transaction_id' => 'SHARED',
            ])
            ->assertRedirect();

        $this->actingAs($this->admin)
            ->post(route('admin.bank-flow.bank-to-cash.store'), [
                'bank_id' => $bank2->id,
                'amount' => '500.00',
                'transaction_date' => now()->toDateString(),
                'transaction_id' => 'SHARED',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();
    }

    public function test_narration_is_optional(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.bank-flow.bank-to-cash.store'), [
                'bank_id' => $this->bank->id,
                'amount' => '1000.00',
                'transaction_date' => now()->toDateString(),
                'transaction_id' => 'TXN012',
            ])
            ->assertRedirect(route('admin.bank-flow.bank-to-cash.create'));

        $this->assertNull(CashTransaction::where('user_id', $this->bank->id)->value('narration'));
    }

    // ── Session / UX ─────────────────────────────────────────────────────────

    public function test_last_bank_id_is_flashed_in_session_after_store(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.bank-flow.bank-to-cash.store'), [
                'bank_id' => $this->bank->id,
                'amount' => '1000.00',
                'transaction_date' => now()->toDateString(),
                'transaction_id' => 'TXN013',
            ])
            ->assertSessionHas('last_bank_id', $this->bank->id);
    }

    public function test_page_preselects_bank_and_shows_balance_after_submit(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.bank-flow.bank-to-cash.store'), [
                'bank_id' => $this->bank->id,
                'amount' => '1000.00',
                'transaction_date' => now()->toDateString(),
                'transaction_id' => 'TXN014',
            ])
            ->assertRedirect(route('admin.bank-flow.bank-to-cash.create'));

        $this->actingAs($this->admin)
            ->get(route('admin.bank-flow.bank-to-cash.create'))
            ->assertOk()
            ->assertSee('4,000.00');
    }
}
