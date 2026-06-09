<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardAndProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dashboard_shows_welcome_message(): void
    {
        $admin = User::factory()->admin()->create(['name' => 'Administrator']);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Welcome Administrator')
            ->assertSee('Mitali SP')
            ->assertSee('Executive');
    }

    public function test_executive_dashboard_shows_welcome_message(): void
    {
        $executive = User::factory()->executive()->create(['name' => 'Jane Doe']);

        $response = $this->actingAs($executive)
            ->get(route('executive.dashboard'));

        $response->assertOk()
            ->assertSee('Welcome Jane Doe')
            ->assertDontSee('href="'.route('admin.executives.index').'"', false);
    }

    public function test_admin_can_update_profile(): void
    {
        $admin = User::factory()->admin()->create([
            'username' => 'admin1',
            'phone' => '9999999999',
        ]);

        $this->actingAs($admin)
            ->put(route('admin.profile.update'), [
                'name' => 'Updated Admin',
                'username' => 'adminupdated',
                'phone' => '8888888888',
                'email' => 'admin@example.com',
                'address' => 'Admin address',
            ])
            ->assertRedirect(route('admin.profile.show'))
            ->assertSessionHas('success');

        $admin->refresh();

        $this->assertSame('Updated Admin', $admin->name);
        $this->assertSame('adminupdated', $admin->username);
    }

    public function test_executive_can_update_profile_without_changing_username(): void
    {
        $executive = User::factory()->executive()->create([
            'username' => 'exec1',
            'phone' => '9876543210',
        ]);

        $this->actingAs($executive)
            ->put(route('executive.profile.update'), [
                'name' => 'Updated Executive',
                'phone' => '9123456789',
                'email' => 'exec@example.com',
                'address' => 'Exec address',
            ])
            ->assertRedirect(route('executive.profile.show'))
            ->assertSessionHas('success');

        $executive->refresh();

        $this->assertSame('Updated Executive', $executive->name);
        $this->assertSame('exec1', $executive->username);
    }

    public function test_user_can_change_password(): void
    {
        $admin = User::factory()->admin()->create([
            'password' => 'oldpassword',
        ]);

        $this->actingAs($admin)
            ->put(route('admin.password.update'), [
                'current_password' => 'oldpassword',
                'password' => 'newpassword',
                'password_confirmation' => 'newpassword',
            ])
            ->assertRedirect(route('admin.password.edit'))
            ->assertSessionHas('success');
    }
}
