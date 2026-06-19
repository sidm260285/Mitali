<?php

namespace Tests\Feature;

use App\Models\AccountHead;
use App\Models\CashTransaction;
use App\Models\User;
use App\Support\MoneyHelper;
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

    public function test_admin_can_view_transfer_to_executive_page(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.cash-flow.transfer-to-executive.create'))
            ->assertOk()
            ->assertSee('Transfer to Executive');
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
            'mode' => CashTransaction::MODE_CASH,
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

    public function test_executive_transfer_detail_hides_other_users_linked_balance(): void
    {
        $executive = User::factory()->executive()->create(['balance' => 0]);

        $this->actingAs($this->admin)
            ->post(route('admin.cash-flow.inflow.store'), [
                'account_head_id' => $this->creditHead->id,
                'amount' => '10000.00',
                'transaction_date' => now()->toDateString(),
            ]);

        $this->actingAs($this->admin)
            ->post(route('admin.cash-flow.transfer-to-executive.store'), [
                'executive_id' => $executive->id,
                'amount' => '10000.00',
                'transaction_date' => now()->toDateString(),
            ]);

        $executiveTransaction = CashTransaction::query()
            ->where('user_id', $executive->id)
            ->whereNotNull('transfer_group_id')
            ->firstOrFail();

        $adminTransaction = CashTransaction::query()
            ->where('user_id', $this->admin->id)
            ->where('transfer_group_id', $executiveTransaction->transfer_group_id)
            ->firstOrFail();

        $response = $this->actingAs($executive)
            ->getJson(route('executive.cash-flow.transactions.show', $executiveTransaction))
            ->assertOk()
            ->assertJsonPath('transaction.id', $executiveTransaction->id)
            ->assertJsonCount(2, 'linked');

        $linked = collect($response->json('linked'))->keyBy('id');

        $this->assertSame(
            MoneyHelper::format($executiveTransaction->current_balance),
            $linked[$executiveTransaction->id]['current_balance'],
        );
        $this->assertNull($linked[$adminTransaction->id]['current_balance']);
    }

    public function test_admin_transfer_detail_shows_all_linked_balances(): void
    {
        $executive = User::factory()->executive()->create(['balance' => 0]);

        $this->actingAs($this->admin)
            ->post(route('admin.cash-flow.inflow.store'), [
                'account_head_id' => $this->creditHead->id,
                'amount' => '10000.00',
                'transaction_date' => now()->toDateString(),
            ]);

        $this->actingAs($this->admin)
            ->post(route('admin.cash-flow.transfer-to-executive.store'), [
                'executive_id' => $executive->id,
                'amount' => '10000.00',
                'transaction_date' => now()->toDateString(),
            ]);

        $adminTransaction = CashTransaction::query()
            ->where('user_id', $this->admin->id)
            ->whereNotNull('transfer_group_id')
            ->firstOrFail();

        $executiveTransaction = CashTransaction::query()
            ->where('user_id', $executive->id)
            ->where('transfer_group_id', $adminTransaction->transfer_group_id)
            ->firstOrFail();

        $response = $this->actingAs($this->admin)
            ->getJson(route('admin.cash-flow.transactions.show', $adminTransaction))
            ->assertOk()
            ->assertJsonCount(2, 'linked');

        $linked = collect($response->json('linked'))->keyBy('id');

        $this->assertSame(
            MoneyHelper::format($adminTransaction->current_balance),
            $linked[$adminTransaction->id]['current_balance'],
        );
        $this->assertSame(
            MoneyHelper::format($executiveTransaction->current_balance),
            $linked[$executiveTransaction->id]['current_balance'],
        );
    }

    public function test_executive_peer_transfer_detail_hides_other_executive_linked_balance(): void
    {
        $sender = User::factory()->executive()->create(['balance' => 0]);
        $receiver = User::factory()->executive()->create(['balance' => 0]);

        CashTransaction::create([
            'user_id' => $sender->id,
            'account_head_id' => $this->creditHead->id,
            'type' => CashTransaction::TYPE_CREDIT,
            'mode' => CashTransaction::MODE_CASH,
            'amount' => 500,
            'transaction_date' => now()->toDateString(),
        ]);

        $this->actingAs($sender)
            ->post(route('executive.cash-flow.transfer-to-executive.store'), [
                'executive_id' => $receiver->id,
                'amount' => '200.00',
                'transaction_date' => now()->toDateString(),
            ]);

        $senderTransaction = CashTransaction::query()
            ->where('user_id', $sender->id)
            ->whereNotNull('transfer_group_id')
            ->firstOrFail();

        $receiverTransaction = CashTransaction::query()
            ->where('user_id', $receiver->id)
            ->where('transfer_group_id', $senderTransaction->transfer_group_id)
            ->firstOrFail();

        $response = $this->actingAs($receiver)
            ->getJson(route('executive.cash-flow.transactions.show', $receiverTransaction))
            ->assertOk()
            ->assertJsonCount(2, 'linked');

        $linked = collect($response->json('linked'))->keyBy('id');

        $this->assertSame(
            MoneyHelper::format($receiverTransaction->current_balance),
            $linked[$receiverTransaction->id]['current_balance'],
        );
        $this->assertNull($linked[$senderTransaction->id]['current_balance']);
    }

    public function test_show_transaction_list_and_detail_json(): void
    {
        CashTransaction::create([
            'user_id' => $this->admin->id,
            'account_head_id' => $this->creditHead->id,
            'type' => CashTransaction::TYPE_CREDIT,
            'mode' => CashTransaction::MODE_CASH,
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
            'mode' => CashTransaction::MODE_CASH,
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

    public function test_inflow_records_mode_as_cash(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.cash-flow.inflow.store'), [
                'account_head_id' => $this->creditHead->id,
                'amount' => '500.00',
                'transaction_date' => now()->toDateString(),
            ]);

        $transaction = CashTransaction::first();
        $this->assertSame(CashTransaction::MODE_CASH, $transaction->mode);
        $this->assertNull($transaction->transaction_id);
    }

    public function test_outflow_records_mode_as_cash(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.cash-flow.inflow.store'), [
                'account_head_id' => $this->creditHead->id,
                'amount' => '500.00',
                'transaction_date' => now()->toDateString(),
            ]);

        $this->actingAs($this->admin)
            ->post(route('admin.cash-flow.outflow.store'), [
                'account_head_id' => $this->debitHead->id,
                'amount' => '200.00',
                'transaction_date' => now()->toDateString(),
            ]);

        $outflow = CashTransaction::where('type', CashTransaction::TYPE_DEBIT)->first();
        $this->assertSame(CashTransaction::MODE_CASH, $outflow->mode);
        $this->assertNull($outflow->transaction_id);
    }

    public function test_transfer_rows_record_mode_as_cash(): void
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
            ]);

        $transferRows = CashTransaction::whereNotNull('transfer_group_id')->get();
        foreach ($transferRows as $row) {
            $this->assertSame(CashTransaction::MODE_CASH, $row->mode);
            $this->assertNull($row->transaction_id);
        }
    }

    public function test_transaction_id_must_be_unique_per_user(): void
    {
        $user = User::factory()->executive()->create(['balance' => 0]);

        CashTransaction::create([
            'user_id' => $user->id,
            'account_head_id' => $this->creditHead->id,
            'type' => CashTransaction::TYPE_CREDIT,
            'mode' => CashTransaction::MODE_BANK,
            'amount' => 100,
            'transaction_date' => now()->toDateString(),
            'transaction_id' => 'TXN-001',
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        CashTransaction::create([
            'user_id' => $user->id,
            'account_head_id' => $this->creditHead->id,
            'type' => CashTransaction::TYPE_CREDIT,
            'mode' => CashTransaction::MODE_BANK,
            'amount' => 200,
            'transaction_date' => now()->toDateString(),
            'transaction_id' => 'TXN-001',
        ]);
    }

    public function test_same_transaction_id_allowed_for_different_users(): void
    {
        $userA = User::factory()->executive()->create(['balance' => 0]);
        $userB = User::factory()->executive()->create(['balance' => 0]);

        CashTransaction::create([
            'user_id' => $userA->id,
            'account_head_id' => $this->creditHead->id,
            'type' => CashTransaction::TYPE_CREDIT,
            'mode' => CashTransaction::MODE_BANK,
            'amount' => 100,
            'transaction_date' => now()->toDateString(),
            'transaction_id' => 'TXN-001',
        ]);

        CashTransaction::create([
            'user_id' => $userB->id,
            'account_head_id' => $this->creditHead->id,
            'type' => CashTransaction::TYPE_CREDIT,
            'mode' => CashTransaction::MODE_BANK,
            'amount' => 200,
            'transaction_date' => now()->toDateString(),
            'transaction_id' => 'TXN-001',
        ]);

        $this->assertEquals(2, CashTransaction::where('transaction_id', 'TXN-001')->count());
    }

    public function test_null_transaction_id_allowed_for_multiple_cash_rows(): void
    {
        CashTransaction::create([
            'user_id' => $this->admin->id,
            'account_head_id' => $this->creditHead->id,
            'type' => CashTransaction::TYPE_CREDIT,
            'mode' => CashTransaction::MODE_CASH,
            'amount' => 100,
            'transaction_date' => now()->toDateString(),
            'transaction_id' => null,
        ]);

        CashTransaction::create([
            'user_id' => $this->admin->id,
            'account_head_id' => $this->creditHead->id,
            'type' => CashTransaction::TYPE_CREDIT,
            'mode' => CashTransaction::MODE_CASH,
            'amount' => 200,
            'transaction_date' => now()->toDateString(),
            'transaction_id' => null,
        ]);

        $this->assertEquals(2, CashTransaction::where('user_id', $this->admin->id)->count());
    }
}
