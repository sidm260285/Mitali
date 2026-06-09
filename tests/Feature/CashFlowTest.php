<?php

namespace Tests\Feature;

use App\Models\AccountHead;
use App\Models\CashTransaction;
use App\Models\User;
use Database\Seeders\SystemAccountHeadSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private AccountHead $creditHead;

    private AccountHead $debitHead;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SystemAccountHeadSeeder::class);
        $this->admin = User::factory()->admin()->create(['balance' => 0]);
        $this->creditHead = AccountHead::factory()->credit()->create(['name' => 'Donations']);
        $this->debitHead = AccountHead::factory()->debit()->create(['name' => 'Office Expense']);
    }

    public function test_admin_can_record_inflow_and_updates_balance(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.cash-flow.inflow.store'), [
                'account_head_id' => $this->creditHead->id,
                'amount' => '1500.50',
                'transaction_date' => now()->toDateString(),
                'narration' => 'Test inflow',
            ])
            ->assertRedirect(route('admin.cash-flow.inflow.create'))
            ->assertSessionHas('success');

        $this->assertEquals(1500.50, (float) $this->admin->fresh()->balance);

        $transaction = CashTransaction::first();
        $this->assertEquals(1500.50, (float) $transaction->current_balance);
        $this->assertSame(CashTransaction::TYPE_CREDIT, $transaction->type);
    }

    public function test_outflow_is_blocked_when_amount_exceeds_balance(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.cash-flow.inflow.store'), [
                'account_head_id' => $this->creditHead->id,
                'amount' => '100.00',
                'transaction_date' => now()->toDateString(),
            ]);

        $this->actingAs($this->admin)
            ->post(route('admin.cash-flow.outflow.store'), [
                'account_head_id' => $this->debitHead->id,
                'amount' => '500.00',
                'transaction_date' => now()->toDateString(),
            ])
            ->assertSessionHasErrors('amount');

        $this->assertEquals(100.00, (float) $this->admin->fresh()->balance);
    }

    public function test_admin_transfer_to_executive_creates_linked_rows(): void
    {
        $executive = User::factory()->executive()->create(['balance' => 0]);

        $this->actingAs($this->admin)
            ->post(route('admin.cash-flow.inflow.store'), [
                'account_head_id' => $this->creditHead->id,
                'amount' => '1000.00',
                'transaction_date' => now()->toDateString(),
            ]);

        $this->actingAs($this->admin)
            ->post(route('admin.cash-flow.transfer-to-executive.store'), [
                'executive_id' => $executive->id,
                'amount' => '400.00',
                'transaction_date' => now()->toDateString(),
                'narration' => 'Monthly float',
            ])
            ->assertRedirect(route('admin.cash-flow.transfer-to-executive.create'))
            ->assertSessionHas('success');

        $this->assertEquals(600.00, (float) $this->admin->fresh()->balance);
        $this->assertEquals(400.00, (float) $executive->fresh()->balance);

        $transferRows = CashTransaction::whereNotNull('transfer_group_id')->get();
        $this->assertCount(2, $transferRows);
        $this->assertSame($transferRows[0]->transfer_group_id, $transferRows[1]->transfer_group_id);
        $this->assertSame('Monthly float', $transferRows[0]->narration);
        $this->assertSame('Monthly float', $transferRows[1]->narration);
    }

    public function test_executive_can_transfer_to_admin(): void
    {
        $executive = User::factory()->executive()->create(['balance' => 0]);

        CashTransaction::create([
            'user_id' => $executive->id,
            'account_head_id' => $this->creditHead->id,
            'type' => CashTransaction::TYPE_CREDIT,
            'amount' => 300,
            'transaction_date' => now()->toDateString(),
        ]);

        $executive->refresh();
        $this->admin->refresh();

        $this->actingAs($executive)
            ->post(route('executive.cash-flow.transfer-to-admin.store'), [
                'amount' => '200.00',
                'transaction_date' => now()->toDateString(),
            ])
            ->assertRedirect(route('executive.cash-flow.transfer-to-admin.create'))
            ->assertSessionHas('success');

        $this->assertEquals(100.00, (float) $executive->fresh()->balance);
        $this->assertEquals(200.00, (float) $this->admin->fresh()->balance);
    }

    public function test_executive_cannot_transfer_to_self(): void
    {
        $executive = User::factory()->executive()->create(['balance' => 500]);

        $this->actingAs($executive)
            ->post(route('executive.cash-flow.transfer-to-executive.store'), [
                'executive_id' => $executive->id,
                'amount' => '100.00',
                'transaction_date' => now()->toDateString(),
            ])
            ->assertSessionHasErrors('executive_id');
    }

    public function test_show_transaction_list_and_detail_json(): void
    {
        CashTransaction::create([
            'user_id' => $this->admin->id,
            'account_head_id' => $this->creditHead->id,
            'type' => CashTransaction::TYPE_CREDIT,
            'amount' => 250,
            'transaction_date' => now()->toDateString(),
            'narration' => 'Short preview narration for modal testing',
        ]);

        $this->admin->refresh();
        $transaction = CashTransaction::first();

        $this->actingAs($this->admin)
            ->get(route('admin.cash-flow.transactions.index'))
            ->assertOk()
            ->assertSee('Show Transaction')
            ->assertSee('₹250.00');

        $this->actingAs($this->admin)
            ->get(route('admin.cash-flow.transactions.show', $transaction))
            ->assertOk()
            ->assertJsonPath('transaction.id', $transaction->id)
            ->assertJsonPath('transaction.narration', 'Short preview narration for modal testing');
    }

    public function test_admin_can_view_executive_transactions(): void
    {
        $executive = User::factory()->executive()->create();

        CashTransaction::create([
            'user_id' => $executive->id,
            'account_head_id' => $this->creditHead->id,
            'type' => CashTransaction::TYPE_CREDIT,
            'amount' => 99,
            'transaction_date' => now()->toDateString(),
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.cash-flow.executive-transactions.index', ['executive_id' => $executive->id]))
            ->assertOk()
            ->assertSee('₹99.00');
    }

    public function test_executive_list_shows_balance(): void
    {
        $executive = User::factory()->executive()->create(['balance' => 1234.50]);

        $this->actingAs($this->admin)
            ->get(route('admin.executives.index'))
            ->assertOk()
            ->assertSee('₹1,234.50');
    }
}
