<?php

namespace Tests\Feature;

use App\Models\AccountHead;
use App\Models\CashTransaction;
use App\Models\User;
use Database\Seeders\SystemAccountHeadSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BankFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $executive;
    private User $bank;
    private AccountHead $creditHead;
    private AccountHead $debitHead;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SystemAccountHeadSeeder::class);
        $this->admin = User::factory()->admin()->create(['balance' => 0]);
        $this->executive = User::factory()->executive()->create(['balance' => 0]);
        $this->bank = User::factory()->bank()->create(['balance' => 0]);
        $this->creditHead = AccountHead::factory()->credit()->create(['name' => 'Bank Deposit']);
        $this->debitHead = AccountHead::factory()->debit()->create(['name' => 'Bank Withdrawal']);
    }

    // ── Inflow page ──────────────────────────────────────────────────────────

    public function test_admin_can_view_bank_inflow_page(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.bank-flow.inflow.create'))
            ->assertOk()
            ->assertSee('Bank Inflow')
            ->assertSee($this->bank->name);
    }

    public function test_executive_can_view_bank_inflow_page(): void
    {
        $this->actingAs($this->executive)
            ->get(route('executive.bank-flow.inflow.create'))
            ->assertOk()
            ->assertSee('Bank Inflow')
            ->assertSee($this->bank->name);
    }

    public function test_bank_dropdown_shows_only_active_banks(): void
    {
        $inactiveBank = User::factory()->bank()->inactive()->create();

        $this->actingAs($this->admin)
            ->get(route('admin.bank-flow.inflow.create'))
            ->assertOk()
            ->assertSee($this->bank->name)
            ->assertDontSee($inactiveBank->name);
    }

    public function test_bank_dropdown_shows_last_5_digits_of_account_no(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.bank-flow.inflow.create'))
            ->assertOk()
            ->assertSee(substr($this->bank->account_no, -5));
    }

    // ── Inflow store ─────────────────────────────────────────────────────────

    public function test_admin_can_record_bank_inflow(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.bank-flow.inflow.store'), [
                'bank_id' => $this->bank->id,
                'account_head_id' => $this->creditHead->id,
                'amount' => '2000.00',
                'transaction_date' => now()->toDateString(),
                'transaction_id' => 'TXN-BANK-001',
            ])
            ->assertRedirect(route('admin.bank-flow.inflow.create'))
            ->assertSessionHas('success');

        $this->assertEquals(2000.00, (float) $this->bank->fresh()->balance);

        $transaction = CashTransaction::first();
        $this->assertEquals(2000.00, (float) $transaction->current_balance);
        $this->assertSame(CashTransaction::TYPE_CREDIT, $transaction->type);
        $this->assertSame(CashTransaction::MODE_BANK, $transaction->mode);
        $this->assertSame('TXN-BANK-001', $transaction->transaction_id);
        $this->assertEquals($this->bank->id, $transaction->user_id);
        $this->assertEquals($this->admin->id, $transaction->entry_by);
    }

    public function test_executive_can_record_bank_inflow(): void
    {
        $this->actingAs($this->executive)
            ->post(route('executive.bank-flow.inflow.store'), [
                'bank_id' => $this->bank->id,
                'account_head_id' => $this->creditHead->id,
                'amount' => '500.00',
                'transaction_date' => now()->toDateString(),
                'transaction_id' => 'TXN-EXEC-001',
            ])
            ->assertRedirect(route('executive.bank-flow.inflow.create'))
            ->assertSessionHas('success');

        $this->assertEquals(500.00, (float) $this->bank->fresh()->balance);
    }

    public function test_inflow_redirects_back_with_last_bank_id_in_session(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.bank-flow.inflow.store'), [
                'bank_id' => $this->bank->id,
                'account_head_id' => $this->creditHead->id,
                'amount' => '1000.00',
                'transaction_date' => now()->toDateString(),
                'transaction_id' => 'TXN-001',
            ])
            ->assertSessionHas('last_bank_id', $this->bank->id);
    }

    public function test_inflow_page_preselects_bank_and_shows_balance_after_submit(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.bank-flow.inflow.store'), [
                'bank_id' => $this->bank->id,
                'account_head_id' => $this->creditHead->id,
                'amount' => '1000.00',
                'transaction_date' => now()->toDateString(),
                'transaction_id' => 'TXN-001',
            ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.bank-flow.inflow.create'));

        $response->assertOk()
            ->assertSee('₹1,000.00');
    }

    public function test_inflow_blocked_for_inactive_bank(): void
    {
        $inactiveBank = User::factory()->bank()->inactive()->create();

        $this->actingAs($this->admin)
            ->post(route('admin.bank-flow.inflow.store'), [
                'bank_id' => $inactiveBank->id,
                'account_head_id' => $this->creditHead->id,
                'amount' => '100.00',
                'transaction_date' => now()->toDateString(),
                'transaction_id' => 'TXN-001',
            ])
            ->assertSessionHasErrors('bank_id');
    }

    public function test_inflow_requires_all_mandatory_fields(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.bank-flow.inflow.store'), [])
            ->assertSessionHasErrors(['bank_id', 'account_head_id', 'amount', 'transaction_id']);
    }

    public function test_inflow_narration_is_optional(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.bank-flow.inflow.store'), [
                'bank_id' => $this->bank->id,
                'account_head_id' => $this->creditHead->id,
                'amount' => '100.00',
                'transaction_date' => now()->toDateString(),
                'transaction_id' => 'TXN-NO-NARRATION',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertNull(CashTransaction::first()->narration);
    }

    public function test_inflow_blocked_with_duplicate_transaction_id_for_same_bank(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.bank-flow.inflow.store'), [
                'bank_id' => $this->bank->id,
                'account_head_id' => $this->creditHead->id,
                'amount' => '100.00',
                'transaction_date' => now()->toDateString(),
                'transaction_id' => 'TXN-DUP',
            ]);

        $this->actingAs($this->admin)
            ->post(route('admin.bank-flow.inflow.store'), [
                'bank_id' => $this->bank->id,
                'account_head_id' => $this->creditHead->id,
                'amount' => '200.00',
                'transaction_date' => now()->toDateString(),
                'transaction_id' => 'TXN-DUP',
            ])
            ->assertSessionHasErrors('transaction_id');
    }

    public function test_inflow_allows_same_transaction_id_for_different_banks(): void
    {
        $anotherBank = User::factory()->bank()->create(['balance' => 0]);

        $this->actingAs($this->admin)
            ->post(route('admin.bank-flow.inflow.store'), [
                'bank_id' => $this->bank->id,
                'account_head_id' => $this->creditHead->id,
                'amount' => '100.00',
                'transaction_date' => now()->toDateString(),
                'transaction_id' => 'TXN-SHARED',
            ]);

        $this->actingAs($this->admin)
            ->post(route('admin.bank-flow.inflow.store'), [
                'bank_id' => $anotherBank->id,
                'account_head_id' => $this->creditHead->id,
                'amount' => '200.00',
                'transaction_date' => now()->toDateString(),
                'transaction_id' => 'TXN-SHARED',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertEquals(2, CashTransaction::where('transaction_id', 'TXN-SHARED')->count());
    }

    // ── Outflow page ─────────────────────────────────────────────────────────

    public function test_admin_can_view_bank_outflow_page(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.bank-flow.outflow.create'))
            ->assertOk()
            ->assertSee('Bank Outflow')
            ->assertSee($this->bank->name);
    }

    public function test_executive_can_view_bank_outflow_page(): void
    {
        $this->actingAs($this->executive)
            ->get(route('executive.bank-flow.outflow.create'))
            ->assertOk()
            ->assertSee('Bank Outflow');
    }

    // ── Outflow store ─────────────────────────────────────────────────────────

    public function test_admin_can_record_bank_outflow(): void
    {
        $this->bank->update(['balance' => 3000]);

        $this->actingAs($this->admin)
            ->post(route('admin.bank-flow.outflow.store'), [
                'bank_id' => $this->bank->id,
                'account_head_id' => $this->debitHead->id,
                'amount' => '1200.00',
                'transaction_date' => now()->toDateString(),
                'transaction_id' => 'TXN-OUT-001',
            ])
            ->assertRedirect(route('admin.bank-flow.outflow.create'))
            ->assertSessionHas('success');

        $this->assertEquals(1800.00, (float) $this->bank->fresh()->balance);

        $transaction = CashTransaction::first();
        $this->assertSame(CashTransaction::TYPE_DEBIT, $transaction->type);
        $this->assertSame(CashTransaction::MODE_BANK, $transaction->mode);
        $this->assertSame('TXN-OUT-001', $transaction->transaction_id);
        $this->assertEquals($this->admin->id, $transaction->entry_by);
    }

    public function test_executive_can_record_bank_outflow(): void
    {
        $this->bank->update(['balance' => 1000]);

        $this->actingAs($this->executive)
            ->post(route('executive.bank-flow.outflow.store'), [
                'bank_id' => $this->bank->id,
                'account_head_id' => $this->debitHead->id,
                'amount' => '400.00',
                'transaction_date' => now()->toDateString(),
                'transaction_id' => 'TXN-EXEC-OUT-001',
            ])
            ->assertRedirect(route('executive.bank-flow.outflow.create'))
            ->assertSessionHas('success');

        $this->assertEquals(600.00, (float) $this->bank->fresh()->balance);
    }

    public function test_outflow_blocked_when_amount_exceeds_bank_balance(): void
    {
        $this->bank->update(['balance' => 500]);

        $this->actingAs($this->admin)
            ->post(route('admin.bank-flow.outflow.store'), [
                'bank_id' => $this->bank->id,
                'account_head_id' => $this->debitHead->id,
                'amount' => '1000.00',
                'transaction_date' => now()->toDateString(),
                'transaction_id' => 'TXN-BLOCKED',
            ])
            ->assertSessionHasErrors('amount');

        $this->assertEquals(500.00, (float) $this->bank->fresh()->balance);
    }

    public function test_outflow_blocked_for_inactive_bank(): void
    {
        $inactiveBank = User::factory()->bank()->inactive()->create(['balance' => 5000]);

        $this->actingAs($this->admin)
            ->post(route('admin.bank-flow.outflow.store'), [
                'bank_id' => $inactiveBank->id,
                'account_head_id' => $this->debitHead->id,
                'amount' => '100.00',
                'transaction_date' => now()->toDateString(),
                'transaction_id' => 'TXN-001',
            ])
            ->assertSessionHasErrors('bank_id');
    }

    public function test_outflow_blocked_with_duplicate_transaction_id_for_same_bank(): void
    {
        $this->bank->update(['balance' => 2000]);

        $this->actingAs($this->admin)
            ->post(route('admin.bank-flow.outflow.store'), [
                'bank_id' => $this->bank->id,
                'account_head_id' => $this->debitHead->id,
                'amount' => '100.00',
                'transaction_date' => now()->toDateString(),
                'transaction_id' => 'TXN-DUP-OUT',
            ]);

        $this->actingAs($this->admin)
            ->post(route('admin.bank-flow.outflow.store'), [
                'bank_id' => $this->bank->id,
                'account_head_id' => $this->debitHead->id,
                'amount' => '100.00',
                'transaction_date' => now()->toDateString(),
                'transaction_id' => 'TXN-DUP-OUT',
            ])
            ->assertSessionHasErrors('transaction_id');
    }

    public function test_outflow_redirects_back_with_last_bank_id_in_session(): void
    {
        $this->bank->update(['balance' => 1000]);

        $this->actingAs($this->admin)
            ->post(route('admin.bank-flow.outflow.store'), [
                'bank_id' => $this->bank->id,
                'account_head_id' => $this->debitHead->id,
                'amount' => '100.00',
                'transaction_date' => now()->toDateString(),
                'transaction_id' => 'TXN-001',
            ])
            ->assertSessionHas('last_bank_id', $this->bank->id);
    }

    // ── Balance AJAX endpoint ─────────────────────────────────────────────────

    public function test_bank_balance_endpoint_returns_formatted_balance(): void
    {
        $this->bank->update(['balance' => 4567.89]);

        $this->actingAs($this->admin)
            ->getJson(route('admin.bank-flow.bank-balance', $this->bank))
            ->assertOk()
            ->assertJsonPath('balance', '₹4,567.89');
    }

    public function test_bank_balance_endpoint_returns_404_for_inactive_bank(): void
    {
        $inactiveBank = User::factory()->bank()->inactive()->create(['balance' => 1000]);

        $this->actingAs($this->admin)
            ->getJson(route('admin.bank-flow.bank-balance', $inactiveBank))
            ->assertNotFound();
    }

    public function test_bank_balance_endpoint_accessible_by_executive(): void
    {
        $this->bank->update(['balance' => 1000]);

        $this->actingAs($this->executive)
            ->getJson(route('executive.bank-flow.bank-balance', $this->bank))
            ->assertOk()
            ->assertJsonPath('balance', '₹1,000.00');
    }

    // ── entry_by ──────────────────────────────────────────────────────────────

    public function test_entry_by_is_set_to_executive_id_for_bank_inflow(): void
    {
        $this->actingAs($this->executive)
            ->post(route('executive.bank-flow.inflow.store'), [
                'bank_id' => $this->bank->id,
                'account_head_id' => $this->creditHead->id,
                'amount' => '300.00',
                'transaction_date' => now()->toDateString(),
                'transaction_id' => 'TXN-EXEC-ENTRY',
            ]);

        $transaction = CashTransaction::first();
        $this->assertEquals($this->executive->id, $transaction->entry_by);
    }

    public function test_entry_by_is_set_to_executive_id_for_bank_outflow(): void
    {
        $this->bank->update(['balance' => 1000]);

        $this->actingAs($this->executive)
            ->post(route('executive.bank-flow.outflow.store'), [
                'bank_id' => $this->bank->id,
                'account_head_id' => $this->debitHead->id,
                'amount' => '100.00',
                'transaction_date' => now()->toDateString(),
                'transaction_id' => 'TXN-EXEC-OUT-ENTRY',
            ]);

        $transaction = CashTransaction::first();
        $this->assertEquals($this->executive->id, $transaction->entry_by);
    }

    public function test_entry_by_is_zero_for_cash_flow_transactions(): void
    {
        $creditHead = AccountHead::factory()->credit()->create(['name' => 'Cash Entry']);

        $this->actingAs($this->admin)
            ->post(route('admin.cash-flow.inflow.store'), [
                'account_head_id' => $creditHead->id,
                'amount' => '500.00',
                'transaction_date' => now()->toDateString(),
            ]);

        $transaction = CashTransaction::first();
        $this->assertEquals(0, $transaction->entry_by);
    }
}
