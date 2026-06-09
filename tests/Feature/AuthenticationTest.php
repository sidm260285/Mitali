<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_is_accessible(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Sign In');
    }

    public function test_admin_can_login_with_username(): void
    {
        $admin = User::factory()->admin()->create([
            'username' => 'adminuser',
            'password' => 'Password@123',
        ]);

        $this->post(route('login'), [
            'username' => 'adminuser',
            'password' => 'Password@123',
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($admin);
    }

    public function test_executive_can_login_with_username(): void
    {
        $executive = User::factory()->executive()->create([
            'username' => 'execuser',
            'password' => 'password',
        ]);

        $this->post(route('login'), [
            'username' => 'execuser',
            'password' => 'password',
        ])->assertRedirect(route('executive.dashboard'));

        $this->assertAuthenticatedAs($executive);
    }

    public function test_deactivated_executive_cannot_login(): void
    {
        User::factory()->executive()->inactive()->create([
            'username' => 'inactive',
            'password' => 'password',
        ]);

        $this->post(route('login'), [
            'username' => 'inactive',
            'password' => 'password',
        ])->assertSessionHasErrors('username');

        $this->assertGuest();
    }

    public function test_user_with_must_change_password_is_redirected(): void
    {
        User::factory()->executive()->mustChangePassword()->create([
            'username' => 'resetme',
            'password' => 'temppass',
        ]);

        $this->post(route('login'), [
            'username' => 'resetme',
            'password' => 'temppass',
        ])->assertRedirect(route('password.force-change'));
    }

    public function test_forced_password_change_updates_password_and_clears_flag(): void
    {
        $executive = User::factory()->executive()->mustChangePassword()->create([
            'password' => 'temppass',
        ]);

        $this->actingAs($executive)
            ->put(route('password.force-change.update'), [
                'password' => 'newpassword',
                'password_confirmation' => 'newpassword',
            ])
            ->assertRedirect(route('executive.dashboard'))
            ->assertSessionHas('success');

        $executive->refresh();

        $this->assertFalse($executive->must_change_password);
    }

    public function test_logout_redirects_to_login(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('logout'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }
}
