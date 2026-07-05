<?php

namespace Tests\Feature;

use App\Models\AccountHead;
use App\Models\CashTransaction;
use App\Models\User;
use Database\Seeders\SystemAccountHeadSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BankToBankTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $executive;
    private User $fromBank;
    private User $toBank;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SystemAccountHeadSeeder::class);
        $this->admin = User::factory()->admin()->create(['balance' => 0]);
        $this->executive = User::factory()->executive()->create(['balance' => 0]);
        $this->fromBank = User::factory()->bank()->create(['balance' => 0]);
        $this->toBank = User::factory()->bank()->create(['balance' => 0]);
    }

    // ── Page access ──────────────────────────────────────────────────────────

    public function test_admin_can_view_bank_to_bank_page(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.bank-flow.bank-to-bank.create'))
            ->assertOk()
            ->assertSee('Bank to Bank')
            ->assertSee($this->fromBank->name);
    }

    public function test_executive_cannot_access_bank_to_bank_page(): void
    {
        $this->actingAs($this->executive)
            ->get(route('admin.bank-flow.bank-to-bank.create'))
            ->assertForbidden();
    }

    public function test_bank_dropdowns_show_only_active_banks(): void
    {
        $inactiveBank = User::factory()->bank()->inactive()->create();

        $this->actingAs($this->admin)
            ->get(route('admin.bank-flow.bank-to-bank.create'))
            ->assertOk()
            ->assertSee($this->fromBank->name)
            ->assertDontSee($inactiveBank->name);
    }

    public function test_bank_dropdown_shows_last_5_digits_of_account_no(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.bank-flow.bank-to-bank.create'))
            ->assertOk()
            ->assertSee(substr($this->fromBank->account_no, -5));
    }

    // ── Store ─────────────────────────────────────────────────────────────────

    public function test_admin_can_record_bank_to_bank_transfer(): void
    {
        $this->fromBank->update(['balance' => 5000]);

        $this->actingAs($this->admin)
            ->post(route('admin.bank-flow.bank-to-bank.store'), [
                'from_bank_id' => $this->fromBank->id,
                'to_bank_id' => $this->toBank->id,
                'amount' => '2000.00',
                'transaction_date' => now()->toDateString(),
                'transaction_id' => 'TXN123',
                'narration' => 'Transfer test',
            ])
            ->assertRedirect(route('admin.bank-flow.bank-to-bank.create'))
            ->assertSessionHas('success');

        // Debit row: from bank
        $debitRow = CashTransaction::where('user_id', $this->fromBank->id)->first();
        $this->assertNotNull($debitRow);
        $this->assertEquals(CashTransaction::TYPE_DEBIT, $debitRow->type);
        $this->assertEquals(CashTransaction::MODE_BANK, $debitRow->mode);
        $this->assertEquals('2000.00', $debitRow->amount);
        $this->assertEquals('TXN123', $debitRow->transaction_id);
        $this->assertEquals($this->admin->id, $debitRow->entry_by);

        // Credit row: to bank
        $creditRow = CashTransaction::where('user_id', $this->toBank->id)->first();
        $this->assertNotNull($creditRow);
        $this->assertEquals(CashTransaction::TYPE_CREDIT, $creditRow->type);
        $this->assertEquals(CashTransaction::MODE_BANK, $creditRow->mode);
        $this->assertEquals('2000.00', $creditRow->amount);
        $this->assertEquals('Ref-TXN123', $creditRow->transaction_id);
        $this->assertEquals($this->admin->id, $creditRow->entry_by);

        // Linked by same transfer_group_id
        $this->assertNotNull($debitRow->transfer_group_id);
        $this->assertEquals($debitRow->transfer_group_id, $creditRow->transfer_group_id);
    }

    public function test_both_rows_use_bank_to_bank_head(): void
    {
        $this->fromBank->update(['balance' => 5000]);

        $this->actingAs($this->admin)
            ->post(route('admin.bank-flow.bank-to-bank.store'), [
                'from_bank_id' => $this->fromBank->id,
                'to_bank_id' => $this->toBank->id,
                'amount' => '1000.00',
                'transaction_date' => now()->toDateString(),
                'transaction_id' => 'TXN456',
            ])
            ->assertRedirect();

        $expectedHead = AccountHead::findSystem(AccountHead::SYSTEM_BANK_TO_BANK);

        $debitRow = CashTransaction::where('user_id', $this->fromBank->id)->first();
        $creditRow = CashTransaction::where('user_id', $this->toBank->id)->first();

        $this->assertEquals($expectedHead->id, $debitRow->account_head_id);
        $this->assertEquals($expectedHead->id, $creditRow->account_head_id);
    }

    // ── Balance ───────────────────────────────────────────────────────────────

    public function test_balances_updated_after_transfer(): void
    {
        $this->fromBank->update(['balance' => 5000]);

        $this->actingAs($this->admin)
            ->post(route('admin.bank-flow.bank-to-bank.store'), [
                'from_bank_id' => $this->fromBank->id,
                'to_bank_id' => $this->toBank->id,
                'amount' => '3000.00',
                'transaction_date' => now()->toDateString(),
                'transaction_id' => 'TXN789',
            ])
            ->assertRedirect();

        $this->assertEquals('2000.00', $this->fromBank->fresh()->balance);
        $this->assertEquals('3000.00', $this->toBank->fresh()->balance);
    }

    public function test_insufficient_from_bank_balance_is_rejected(): void
    {
        $this->fromBank->update(['balance' => 500]);

        $this->actingAs($this->admin)
            ->post(route('admin.bank-flow.bank-to-bank.store'), [
                'from_bank_id' => $this->fromBank->id,
                'to_bank_id' => $this->toBank->id,
                'amount' => '1000.00',
                'transaction_date' => now()->toDateString(),
                'transaction_id' => 'TXN001',
            ])
            ->assertSessionHasErrors('amount');

        $this->assertDatabaseCount('cash_transactions', 0);
    }

    // ── Validation ────────────────────────────────────────────────────────────

    public function test_same_from_and_to_bank_is_rejected(): void
    {
        $this->fromBank->update(['balance' => 5000]);

        $this->actingAs($this->admin)
            ->post(route('admin.bank-flow.bank-to-bank.store'), [
                'from_bank_id' => $this->fromBank->id,
                'to_bank_id' => $this->fromBank->id,
                'amount' => '1000.00',
                'transaction_date' => now()->toDateString(),
                'transaction_id' => 'TXN001',
            ])
            ->assertSessionHasErrors('to_bank_id');
    }

    public function test_inactive_from_bank_is_rejected(): void
    {
        $inactiveBank = User::factory()->bank()->inactive()->create(['balance' => 5000]);

        $this->actingAs($this->admin)
            ->post(route('admin.bank-flow.bank-to-bank.store'), [
                'from_bank_id' => $inactiveBank->id,
                'to_bank_id' => $this->toBank->id,
                'amount' => '1000.00',
                'transaction_date' => now()->toDateString(),
                'transaction_id' => 'TXN001',
            ])
            ->assertSessionHasErrors('from_bank_id');
    }

    public function test_inactive_to_bank_is_rejected(): void
    {
        $this->fromBank->update(['balance' => 5000]);
        $inactiveBank = User::factory()->bank()->inactive()->create();

        $this->actingAs($this->admin)
            ->post(route('admin.bank-flow.bank-to-bank.store'), [
                'from_bank_id' => $this->fromBank->id,
                'to_bank_id' => $inactiveBank->id,
                'amount' => '1000.00',
                'transaction_date' => now()->toDateString(),
                'transaction_id' => 'TXN001',
            ])
            ->assertSessionHasErrors('to_bank_id');
    }

    public function test_from_bank_id_is_required(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.bank-flow.bank-to-bank.store'), [
                'to_bank_id' => $this->toBank->id,
                'amount' => '1000.00',
                'transaction_date' => now()->toDateString(),
                'transaction_id' => 'TXN001',
            ])
            ->assertSessionHasErrors('from_bank_id');
    }

    public function test_to_bank_id_is_required(): void
    {
        $this->fromBank->update(['balance' => 5000]);

        $this->actingAs($this->admin)
            ->post(route('admin.bank-flow.bank-to-bank.store'), [
                'from_bank_id' => $this->fromBank->id,
                'amount' => '1000.00',
                'transaction_date' => now()->toDateString(),
                'transaction_id' => 'TXN001',
            ])
            ->assertSessionHasErrors('to_bank_id');
    }

    public function test_transaction_id_is_required(): void
    {
        $this->fromBank->update(['balance' => 5000]);

        $this->actingAs($this->admin)
            ->post(route('admin.bank-flow.bank-to-bank.store'), [
                'from_bank_id' => $this->fromBank->id,
                'to_bank_id' => $this->toBank->id,
                'amount' => '1000.00',
                'transaction_date' => now()->toDateString(),
            ])
            ->assertSessionHasErrors('transaction_id');
    }

    public function test_duplicate_transaction_id_for_same_from_bank_is_rejected(): void
    {
        $this->fromBank->update(['balance' => 10000]);

        $payload = [
            'from_bank_id' => $this->fromBank->id,
            'to_bank_id' => $this->toBank->id,
            'amount' => '1000.00',
            'transaction_date' => now()->toDateString(),
            'transaction_id' => 'TXN-DUPE',
        ];

        $this->actingAs($this->admin)->post(route('admin.bank-flow.bank-to-bank.store'), $payload)->assertRedirect();
        $this->actingAs($this->admin)->post(route('admin.bank-flow.bank-to-bank.store'), $payload)->assertSessionHasErrors('transaction_id');
    }

    public function test_same_transaction_id_allowed_for_different_from_banks(): void
    {
        $anotherFromBank = User::factory()->bank()->create(['balance' => 5000]);
        $anotherToBank = User::factory()->bank()->create(['balance' => 0]);
        $this->fromBank->update(['balance' => 5000]);

        $this->actingAs($this->admin)
            ->post(route('admin.bank-flow.bank-to-bank.store'), [
                'from_bank_id' => $this->fromBank->id,
                'to_bank_id' => $this->toBank->id,
                'amount' => '1000.00',
                'transaction_date' => now()->toDateString(),
                'transaction_id' => 'TXN-SHARED',
            ])
            ->assertRedirect();

        $this->actingAs($this->admin)
            ->post(route('admin.bank-flow.bank-to-bank.store'), [
                'from_bank_id' => $anotherFromBank->id,
                'to_bank_id' => $anotherToBank->id,
                'amount' => '500.00',
                'transaction_date' => now()->toDateString(),
                'transaction_id' => 'TXN-SHARED',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();
    }

    public function test_narration_is_optional(): void
    {
        $this->fromBank->update(['balance' => 5000]);

        $this->actingAs($this->admin)
            ->post(route('admin.bank-flow.bank-to-bank.store'), [
                'from_bank_id' => $this->fromBank->id,
                'to_bank_id' => $this->toBank->id,
                'amount' => '1000.00',
                'transaction_date' => now()->toDateString(),
                'transaction_id' => 'TXN-NONAR',
            ])
            ->assertRedirect(route('admin.bank-flow.bank-to-bank.create'));

        $this->assertNull(CashTransaction::where('user_id', $this->fromBank->id)->value('narration'));
    }

    public function test_last_from_bank_id_flashed_in_session(): void
    {
        $this->fromBank->update(['balance' => 5000]);

        $this->actingAs($this->admin)
            ->post(route('admin.bank-flow.bank-to-bank.store'), [
                'from_bank_id' => $this->fromBank->id,
                'to_bank_id' => $this->toBank->id,
                'amount' => '1000.00',
                'transaction_date' => now()->toDateString(),
                'transaction_id' => 'TXN-FLASH',
            ])
            ->assertSessionHas('last_from_bank_id', $this->fromBank->id);
    }

    public function test_from_bank_preselected_after_submit(): void
    {
        $this->fromBank->update(['balance' => 5000]);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.bank-flow.bank-to-bank.store'), [
                'from_bank_id' => $this->fromBank->id,
                'to_bank_id' => $this->toBank->id,
                'amount' => '1000.00',
                'transaction_date' => now()->toDateString(),
                'transaction_id' => 'TXN-PRESEL',
            ]);

        $response->assertRedirect();

        $this->actingAs($this->admin)
            ->get(route('admin.bank-flow.bank-to-bank.create'))
            ->assertOk()
            ->assertSee($this->fromBank->name);
    }
}
