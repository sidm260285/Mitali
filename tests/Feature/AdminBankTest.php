<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminBankTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
    }

    public function test_admin_can_view_bank_list(): void
    {
        User::factory()->bank()->count(2)->create();

        $this->actingAs($this->admin)
            ->get(route('admin.banks.index'))
            ->assertOk()
            ->assertSee('Banks')
            ->assertSee('Add Bank');
    }

    public function test_admin_can_create_bank(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.banks.store'), [
                'name' => 'State Bank of India',
                'username' => '23456',
                'account_no' => '123456789023456',
                'account_type' => 'Savings',
                'branch_name' => 'Main Branch',
                'phone' => null,
                'email' => null,
                'password' => 'password123',
            ])
            ->assertRedirect(route('admin.banks.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'username' => '23456',
            'role' => User::ROLE_BANK,
            'account_no' => '123456789023456',
            'account_type' => 'Savings',
            'branch_name' => 'Main Branch',
            'is_active' => true,
        ]);
    }

    public function test_bank_creation_requires_mandatory_fields(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.banks.store'), [])
            ->assertSessionHasErrors(['name', 'username', 'account_no', 'account_type', 'branch_name', 'password']);
    }

    public function test_account_no_must_be_at_least_8_characters(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.banks.store'), [
                'name' => 'Test Bank',
                'username' => 'testb',
                'account_no' => '1234567',
                'account_type' => 'Savings',
                'branch_name' => 'Test Branch',
                'password' => 'password123',
            ])
            ->assertSessionHasErrors('account_no');

        $this->actingAs($this->admin)
            ->post(route('admin.banks.store'), [
                'name' => 'Test Bank',
                'username' => 'testb',
                'account_no' => '12345678',
                'account_type' => 'Savings',
                'branch_name' => 'Test Branch',
                'password' => 'password123',
            ])
            ->assertSessionDoesntHaveErrors('account_no');
    }

    public function test_bank_account_type_must_be_savings_or_current(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.banks.store'), [
                'name' => 'Test Bank',
                'username' => 'testb',
                'account_no' => '1234567890',
                'account_type' => 'Invalid',
                'branch_name' => 'Test Branch',
                'password' => 'password123',
            ])
            ->assertSessionHasErrors('account_type');
    }

    public function test_admin_can_search_banks_by_name_and_account_no(): void
    {
        User::factory()->bank()->create([
            'name' => 'HDFC Bank',
            'account_no' => '1111111111',
            'username' => '11111',
        ]);
        User::factory()->bank()->create([
            'name' => 'ICICI Bank',
            'account_no' => '2222222222',
            'username' => '22222',
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.banks.index', ['name' => 'HDFC', 'account_no' => '1111']))
            ->assertOk()
            ->assertSee('HDFC Bank')
            ->assertDontSee('ICICI Bank');
    }

    public function test_admin_can_view_bank_detail(): void
    {
        $bank = User::factory()->bank()->create(['name' => 'Axis Bank']);

        $this->actingAs($this->admin)
            ->get(route('admin.banks.show', $bank))
            ->assertOk()
            ->assertSee('Axis Bank');
    }

    public function test_admin_can_edit_bank(): void
    {
        $bank = User::factory()->bank()->create([
            'account_no' => '9999999999',
            'username' => '99999',
        ]);

        $this->actingAs($this->admin)
            ->put(route('admin.banks.update', $bank), [
                'name' => 'Updated Bank Name',
                'username' => '99999',
                'account_no' => '9999999999',
                'account_type' => 'Current',
                'branch_name' => 'Updated Branch',
            ])
            ->assertRedirect(route('admin.banks.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'id' => $bank->id,
            'name' => 'Updated Bank Name',
            'account_type' => 'Current',
            'branch_name' => 'Updated Branch',
        ]);
    }

    public function test_admin_can_deactivate_and_activate_bank(): void
    {
        $bank = User::factory()->bank()->create();

        $this->actingAs($this->admin)
            ->patch(route('admin.banks.deactivate', $bank))
            ->assertRedirect(route('admin.banks.index'))
            ->assertSessionHas('success');

        $this->assertFalse($bank->fresh()->is_active);

        $this->actingAs($this->admin)
            ->patch(route('admin.banks.activate', $bank))
            ->assertRedirect(route('admin.banks.index'))
            ->assertSessionHas('success');

        $this->assertTrue($bank->fresh()->is_active);
    }

    public function test_admin_can_reset_bank_password_with_one_time_view(): void
    {
        $bank = User::factory()->bank()->create();

        $response = $this->actingAs($this->admin)
            ->post(route('admin.banks.reset-password', $bank));

        $response->assertRedirect(route('admin.banks.reset-password.reveal', $bank));

        $bank->refresh();
        $this->assertTrue($bank->must_change_password);

        $reveal = $this->actingAs($this->admin)
            ->get(route('admin.banks.reset-password.reveal', $bank));

        $reveal->assertOk()
            ->assertSee('One-Time Password View');

        $this->actingAs($this->admin)
            ->get(route('admin.banks.reset-password.reveal', $bank))
            ->assertRedirect(route('admin.banks.index'))
            ->assertSessionHas('error');
    }

    public function test_executive_cannot_access_admin_bank_pages(): void
    {
        $executive = User::factory()->executive()->create();

        $this->actingAs($executive)
            ->get(route('admin.banks.index'))
            ->assertForbidden();
    }

    public function test_bank_user_cannot_access_admin_bank_pages(): void
    {
        $bank = User::factory()->bank()->create();

        $this->actingAs($bank)
            ->get(route('admin.banks.index'))
            ->assertForbidden();
    }

    public function test_bank_user_can_access_bank_dashboard(): void
    {
        $bank = User::factory()->bank()->create();

        $this->actingAs($bank)
            ->get(route('bank.dashboard'))
            ->assertOk();
    }

    public function test_admin_cannot_access_bank_dashboard(): void
    {
        $this->actingAs($this->admin)
            ->get(route('bank.dashboard'))
            ->assertForbidden();
    }

    public function test_executive_cannot_access_bank_dashboard(): void
    {
        $executive = User::factory()->executive()->create();

        $this->actingAs($executive)
            ->get(route('bank.dashboard'))
            ->assertForbidden();
    }

    public function test_accessing_executive_route_with_bank_id_returns_404(): void
    {
        $bank = User::factory()->bank()->create();

        $this->actingAs($this->admin)
            ->get(route('admin.executives.show', $bank->id))
            ->assertNotFound();
    }

    public function test_accessing_bank_route_with_executive_id_returns_404(): void
    {
        $executive = User::factory()->executive()->create();

        $this->actingAs($this->admin)
            ->get(route('admin.banks.show', $executive->id))
            ->assertNotFound();
    }

    public function test_username_must_be_unique_across_all_users(): void
    {
        User::factory()->executive()->create(['username' => 'shared1']);

        $this->actingAs($this->admin)
            ->post(route('admin.banks.store'), [
                'name' => 'Test Bank',
                'username' => 'shared1',
                'account_no' => '1234567890',
                'account_type' => 'Savings',
                'branch_name' => 'Branch',
                'password' => 'password123',
            ])
            ->assertSessionHasErrors('username');
    }

    public function test_two_banks_cannot_share_email_or_phone(): void
    {
        User::factory()->bank()->create([
            'email' => 'bank@example.com',
            'phone' => '9000000003',
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.banks.store'), [
                'name' => 'Another Bank',
                'username' => '99999',
                'account_no' => '1234599999',
                'account_type' => 'Savings',
                'branch_name' => 'Branch',
                'phone' => '9000000003',
                'email' => 'bank@example.com',
                'password' => 'password123',
            ])
            ->assertSessionHasErrors(['phone', 'email']);
    }

    public function test_bank_and_executive_can_share_email_and_phone(): void
    {
        User::factory()->executive()->create([
            'email' => 'shared@example.com',
            'phone' => '9000000004',
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.banks.store'), [
                'name' => 'New Bank',
                'username' => '88888',
                'account_no' => '1234588888',
                'account_type' => 'Current',
                'branch_name' => 'Branch',
                'phone' => '9000000004',
                'email' => 'shared@example.com',
                'password' => 'password123',
            ])
            ->assertRedirect(route('admin.banks.index'))
            ->assertSessionHas('success');
    }
}
