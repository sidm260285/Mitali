<?php

namespace Tests\Feature;

use App\Models\AccountHead;
use App\Models\CashTransaction;
use App\Models\User;
use Database\Seeders\SystemAccountHeadSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountHeadTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SystemAccountHeadSeeder::class);
        $this->admin = User::factory()->admin()->create();
    }

    public function test_admin_can_manage_accounts_heads(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.account-heads.index'))
            ->assertOk()
            ->assertSee('Accounts Head');

        $this->actingAs($this->admin)
            ->post(route('admin.account-heads.store'), [
                'name' => 'Membership Fees',
                'type' => AccountHead::TYPE_CREDIT,
            ])
            ->assertRedirect(route('admin.account-heads.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('account_heads', [
            'name' => 'Membership Fees',
            'type' => AccountHead::TYPE_CREDIT,
            'is_system' => false,
        ]);
    }

    public function test_duplicate_name_and_type_is_rejected(): void
    {
        AccountHead::factory()->credit()->create(['name' => 'Fees']);

        $this->actingAs($this->admin)
            ->post(route('admin.account-heads.store'), [
                'name' => 'Fees',
                'type' => AccountHead::TYPE_CREDIT,
            ])
            ->assertSessionHasErrors('name');
    }

    public function test_accounts_head_in_use_cannot_be_edited_or_deleted(): void
    {
        $head = AccountHead::factory()->debit()->create(['name' => 'Expenses']);
        $user = User::factory()->admin()->create();

        CashTransaction::factory()->create([
            'user_id' => $user->id,
            'account_head_id' => $head->id,
            'type' => CashTransaction::TYPE_DEBIT,
            'amount' => 10,
        ]);

        $this->actingAs($this->admin)
            ->put(route('admin.account-heads.update', $head), [
                'name' => 'Updated',
                'type' => AccountHead::TYPE_DEBIT,
            ])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->actingAs($this->admin)
            ->delete(route('admin.account-heads.destroy', $head))
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    public function test_system_heads_are_not_listed(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.account-heads.index'))
            ->assertOk()
            ->assertDontSee('admin to executive');
    }
}
