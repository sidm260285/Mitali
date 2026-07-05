<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminExecutiveTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
    }

    public function test_admin_can_view_executive_list(): void
    {
        User::factory()->executive()->count(2)->create();

        $this->actingAs($this->admin)
            ->get(route('admin.executives.index'))
            ->assertOk()
            ->assertSee('Executives')
            ->assertSee('Add Executive');
    }

    public function test_admin_can_create_executive(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.executives.store'), [
                'name' => 'John Executive',
                'username' => 'johnexec',
                'phone' => '9876543210',
                'email' => 'john@example.com',
                'address' => 'Test address',
                'monthly_salary' => 15000,
                'password' => 'password123',
            ])
            ->assertRedirect(route('admin.executives.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'username' => 'johnexec',
            'role' => User::ROLE_EXECUTIVE,
            'is_active' => true,
            'monthly_salary' => 15000,
        ]);
    }

    public function test_admin_can_search_executives_by_name_and_phone(): void
    {
        User::factory()->executive()->create([
            'name' => 'Alice Smith',
            'phone' => '1111111111',
        ]);
        User::factory()->executive()->create([
            'name' => 'Bob Jones',
            'phone' => '2222222222',
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.executives.index', ['name' => 'Alice', 'phone' => '1111']))
            ->assertOk()
            ->assertSee('Alice Smith')
            ->assertDontSee('Bob Jones');
    }

    public function test_admin_can_deactivate_and_activate_executive(): void
    {
        $executive = User::factory()->executive()->create();

        $this->actingAs($this->admin)
            ->patch(route('admin.executives.deactivate', $executive))
            ->assertRedirect(route('admin.executives.index'))
            ->assertSessionHas('success');

        $this->assertFalse($executive->fresh()->is_active);

        $this->actingAs($this->admin)
            ->patch(route('admin.executives.activate', $executive))
            ->assertRedirect(route('admin.executives.index'))
            ->assertSessionHas('success');

        $this->assertTrue($executive->fresh()->is_active);
    }

    public function test_admin_can_reset_executive_password_with_one_time_view(): void
    {
        $executive = User::factory()->executive()->create();

        $response = $this->actingAs($this->admin)
            ->post(route('admin.executives.reset-password', $executive));

        $response->assertRedirect(route('admin.executives.reset-password.reveal', $executive));

        $executive->refresh();
        $this->assertTrue($executive->must_change_password);

        $reveal = $this->actingAs($this->admin)
            ->get(route('admin.executives.reset-password.reveal', $executive));

        $reveal->assertOk()
            ->assertSee('One-Time Password View');

        $this->actingAs($this->admin)
            ->get(route('admin.executives.reset-password.reveal', $executive))
            ->assertRedirect(route('admin.executives.index'))
            ->assertSessionHas('error');
    }

    public function test_executive_cannot_access_admin_executive_pages(): void
    {
        $executive = User::factory()->executive()->create();

        $this->actingAs($executive)
            ->get(route('admin.executives.index'))
            ->assertForbidden();
    }

    public function test_two_executives_cannot_share_email_or_phone(): void
    {
        User::factory()->executive()->create([
            'email' => 'shared@example.com',
            'phone' => '9000000001',
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.executives.store'), [
                'name' => 'Another Executive',
                'username' => 'anotherexec',
                'phone' => '9000000001',
                'email' => 'shared@example.com',
                'monthly_salary' => 10000,
                'password' => 'password123',
            ])
            ->assertSessionHasErrors(['phone', 'email']);
    }

    public function test_executive_and_bank_can_share_email_and_phone(): void
    {
        User::factory()->bank()->create([
            'email' => 'shared@example.com',
            'phone' => '9000000002',
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.executives.store'), [
                'name' => 'New Executive',
                'username' => 'newexec',
                'phone' => '9000000002',
                'email' => 'shared@example.com',
                'monthly_salary' => 12000,
                'password' => 'password123',
            ])
            ->assertRedirect(route('admin.executives.index'))
            ->assertSessionHas('success');
    }

    public function test_monthly_salary_is_required_on_create(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.executives.store'), [
                'name' => 'John Executive',
                'username' => 'johnexec',
                'phone' => '9876543210',
                'password' => 'password123',
            ])
            ->assertSessionHasErrors('monthly_salary');
    }

    public function test_admin_can_update_executive_salary(): void
    {
        $executive = User::factory()->executive()->create(['monthly_salary' => 10000]);

        $this->actingAs($this->admin)
            ->put(route('admin.executives.update', $executive), [
                'name' => $executive->name,
                'username' => $executive->username,
                'phone' => $executive->phone,
                'monthly_salary' => 20000,
            ])
            ->assertRedirect(route('admin.executives.index'))
            ->assertSessionHas('success');

        $this->assertEquals(20000, $executive->fresh()->monthly_salary);
    }

    public function test_monthly_salary_is_required_on_update(): void
    {
        $executive = User::factory()->executive()->create(['monthly_salary' => 10000]);

        $this->actingAs($this->admin)
            ->put(route('admin.executives.update', $executive), [
                'name' => $executive->name,
                'username' => $executive->username,
                'phone' => $executive->phone,
            ])
            ->assertSessionHasErrors('monthly_salary');
    }
}
