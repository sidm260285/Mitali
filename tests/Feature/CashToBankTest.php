<?php

namespace Tests\Feature;

use App\Models\AccountHead;
use App\Models\CashTransaction;
use App\Models\User;
use Database\Seeders\SystemAccountHeadSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashToBankTest extends TestCase
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

    public function test_admin_can_view_cash_to_bank_page(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.cash-flow.cash-to-bank.create'))
            ->assertOk()
            ->assertSee('Cash to Bank')
            ->assertSee($this->bank->name);
    }

    public function test_executive_can_view_cash_to_bank_page(): void
    {
        $this->actingAs($this->executive)
            ->get(route('executive.cash-flow.cash-to-bank.create'))
            ->assertOk()
            ->assertSee('Cash to Bank')
            ->assertSee($this->bank->name);
    }

    public function test_bank_dropdown_shows_only_active_banks(): void
    {
        $inactiveBank = User::factory()->bank()->inactive()->create();

        $this->actingAs($this->admin)
            ->get(route('admin.cash-flow.cash-to-bank.create'))
            ->assertOk()
            ->assertSee($this->bank->name)
            ->assertDontSee($inactiveBank->name);
    }

    public function test_bank_dropdown_shows_last_5_digits_of_account_no(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.cash-flow.cash-to-bank.create'))
            ->assertOk()
            ->assertSee(substr($this->bank->account_no, -5));
    }

    public function test_page_shows_doers_own_balance(): void
    {
        $this->admin->update(['balance' => 5000]);

        $this->actingAs($this->admin)
            ->get(route('admin.cash-flow.cash-to-bank.create'))
            ->assertOk()
            ->assertSee('5,000.00');
    }

    // ── Admin store ──────────────────────────────────────────────────────────

    public function test_admin_can_record_cash_to_bank(): void
    {
        $this->admin->update(['balance' => 5000]);

        $this->actingAs($this->admin)
            ->post(route('admin.cash-flow.cash-to-bank.store'), [
                'bank_id' => $this->bank->id,
                'amount' => '2000.00',
                'transaction_date' => now()->toDateString(),
                'narration' => 'Test deposit',
            ])
            ->assertRedirect(route('admin.cash-flow.cash-to-bank.create'))
            ->assertSessionHas('success');

        // Debit row: cash, from admin
        $debitRow = CashTransaction::where('user_id', $this->admin->id)->first();
        $this->assertNotNull($debitRow);
        $this->assertEquals(CashTransaction::TYPE_DEBIT, $debitRow->type);
        $this->assertEquals(CashTransaction::MODE_CASH, $debitRow->mode);
        $this->assertEquals('2000.00', $debitRow->amount);
        $this->assertEquals(0, $debitRow->entry_by);

        // Credit row: bank, to selected bank
        $creditRow = CashTransaction::where('user_id', $this->bank->id)->first();
        $this->assertNotNull($creditRow);
        $this->assertEquals(CashTransaction::TYPE_CREDIT, $creditRow->type);
        $this->assertEquals(CashTransaction::MODE_BANK, $creditRow->mode);
        $this->assertEquals('2000.00', $creditRow->amount);
        $this->assertEquals($this->admin->id, $creditRow->entry_by);

        // Linked by same transfer_group_id
        $this->assertEquals($debitRow->transfer_group_id, $creditRow->transfer_group_id);
        $this->assertNotNull($debitRow->transfer_group_id);
    }

    public function test_admin_debit_row_uses_admin_to_bank_head(): void
    {
        $this->admin->update(['balance' => 5000]);

        $this->actingAs($this->admin)
            ->post(route('admin.cash-flow.cash-to-bank.store'), [
                'bank_id' => $this->bank->id,
                'amount' => '1000.00',
                'transaction_date' => now()->toDateString(),
            ])
            ->assertRedirect();

        $debitRow = CashTransaction::where('user_id', $this->admin->id)->first();
        $expectedHead = AccountHead::findSystem(AccountHead::SYSTEM_ADMIN_TO_BANK);
        $this->assertEquals($expectedHead->id, $debitRow->account_head_id);
    }

    public function test_admin_credit_row_uses_deposit_by_admin_head(): void
    {
        $this->admin->update(['balance' => 5000]);

        $this->actingAs($this->admin)
            ->post(route('admin.cash-flow.cash-to-bank.store'), [
                'bank_id' => $this->bank->id,
                'amount' => '1000.00',
                'transaction_date' => now()->toDateString(),
            ])
            ->assertRedirect();

        $creditRow = CashTransaction::where('user_id', $this->bank->id)->first();
        $expectedHead = AccountHead::findSystem(AccountHead::SYSTEM_DEPOSIT_BY_ADMIN);
        $this->assertEquals($expectedHead->id, $creditRow->account_head_id);
    }

    // ── Executive store ───────────────────────────────────────────────────────

    public function test_executive_can_record_cash_to_bank(): void
    {
        $this->executive->update(['balance' => 3000]);

        $this->actingAs($this->executive)
            ->post(route('executive.cash-flow.cash-to-bank.store'), [
                'bank_id' => $this->bank->id,
                'amount' => '1500.00',
                'transaction_date' => now()->toDateString(),
            ])
            ->assertRedirect(route('executive.cash-flow.cash-to-bank.create'))
            ->assertSessionHas('success');

        $debitRow = CashTransaction::where('user_id', $this->executive->id)->first();
        $this->assertEquals(CashTransaction::TYPE_DEBIT, $debitRow->type);
        $this->assertEquals(CashTransaction::MODE_CASH, $debitRow->mode);
        $this->assertEquals(0, $debitRow->entry_by);

        $creditRow = CashTransaction::where('user_id', $this->bank->id)->first();
        $this->assertEquals(CashTransaction::TYPE_CREDIT, $creditRow->type);
        $this->assertEquals(CashTransaction::MODE_BANK, $creditRow->mode);
        $this->assertEquals($this->executive->id, $creditRow->entry_by);
    }

    public function test_executive_debit_row_uses_executive_to_bank_head(): void
    {
        $this->executive->update(['balance' => 3000]);

        $this->actingAs($this->executive)
            ->post(route('executive.cash-flow.cash-to-bank.store'), [
                'bank_id' => $this->bank->id,
                'amount' => '500.00',
                'transaction_date' => now()->toDateString(),
            ])
            ->assertRedirect();

        $debitRow = CashTransaction::where('user_id', $this->executive->id)->first();
        $expectedHead = AccountHead::findSystem(AccountHead::SYSTEM_EXECUTIVE_TO_BANK);
        $this->assertEquals($expectedHead->id, $debitRow->account_head_id);
    }

    public function test_executive_credit_row_uses_deposit_by_executive_head(): void
    {
        $this->executive->update(['balance' => 3000]);

        $this->actingAs($this->executive)
            ->post(route('executive.cash-flow.cash-to-bank.store'), [
                'bank_id' => $this->bank->id,
                'amount' => '500.00',
                'transaction_date' => now()->toDateString(),
            ])
            ->assertRedirect();

        $creditRow = CashTransaction::where('user_id', $this->bank->id)->first();
        $expectedHead = AccountHead::findSystem(AccountHead::SYSTEM_DEPOSIT_BY_EXECUTIVE);
        $this->assertEquals($expectedHead->id, $creditRow->account_head_id);
    }

    // ── Balance checks ────────────────────────────────────────────────────────

    public function test_balances_are_updated_after_transfer(): void
    {
        $this->admin->update(['balance' => 5000]);

        $this->actingAs($this->admin)
            ->post(route('admin.cash-flow.cash-to-bank.store'), [
                'bank_id' => $this->bank->id,
                'amount' => '2000.00',
                'transaction_date' => now()->toDateString(),
            ])
            ->assertRedirect();

        $this->assertEquals('3000.00', $this->admin->fresh()->balance);
        $this->assertEquals('2000.00', $this->bank->fresh()->balance);
    }

    public function test_insufficient_balance_is_rejected(): void
    {
        $this->admin->update(['balance' => 500]);

        $this->actingAs($this->admin)
            ->post(route('admin.cash-flow.cash-to-bank.store'), [
                'bank_id' => $this->bank->id,
                'amount' => '1000.00',
                'transaction_date' => now()->toDateString(),
            ])
            ->assertSessionHasErrors('amount');

        $this->assertDatabaseCount('cash_transactions', 0);
    }

    // ── Validation ────────────────────────────────────────────────────────────

    public function test_bank_id_is_required(): void
    {
        $this->admin->update(['balance' => 5000]);

        $this->actingAs($this->admin)
            ->post(route('admin.cash-flow.cash-to-bank.store'), [
                'amount' => '1000.00',
                'transaction_date' => now()->toDateString(),
            ])
            ->assertSessionHasErrors('bank_id');
    }

    public function test_amount_is_required(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.cash-flow.cash-to-bank.store'), [
                'bank_id' => $this->bank->id,
                'transaction_date' => now()->toDateString(),
            ])
            ->assertSessionHasErrors('amount');
    }

    public function test_inactive_bank_is_rejected(): void
    {
        $this->admin->update(['balance' => 5000]);
        $inactiveBank = User::factory()->bank()->inactive()->create();

        $this->actingAs($this->admin)
            ->post(route('admin.cash-flow.cash-to-bank.store'), [
                'bank_id' => $inactiveBank->id,
                'amount' => '1000.00',
                'transaction_date' => now()->toDateString(),
            ])
            ->assertSessionHasErrors('bank_id');
    }

    public function test_narration_is_optional(): void
    {
        $this->admin->update(['balance' => 5000]);

        $this->actingAs($this->admin)
            ->post(route('admin.cash-flow.cash-to-bank.store'), [
                'bank_id' => $this->bank->id,
                'amount' => '1000.00',
                'transaction_date' => now()->toDateString(),
            ])
            ->assertRedirect(route('admin.cash-flow.cash-to-bank.create'));

        $this->assertNull(CashTransaction::where('user_id', $this->admin->id)->value('narration'));
    }

    public function test_existing_cash_flow_transfers_are_unaffected(): void
    {
        $this->admin->update(['balance' => 5000]);
        $this->executive->update(['balance' => 0]);

        // Do a cash transfer (admin to executive) — both rows should remain MODE_CASH
        $this->actingAs($this->admin)
            ->post(route('admin.cash-flow.transfer-to-executive.store'), [
                'executive_id' => $this->executive->id,
                'amount' => '1000.00',
                'transaction_date' => now()->toDateString(),
            ])
            ->assertRedirect();

        CashTransaction::all()->each(function (CashTransaction $t) {
            $this->assertEquals(CashTransaction::MODE_CASH, $t->mode);
            $this->assertEquals(0, $t->entry_by);
        });
    }
}
