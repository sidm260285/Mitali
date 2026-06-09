<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_redirects_guest_to_login(): void
    {
        $this->get('/')
            ->assertRedirect(route('login'));
    }

    public function test_home_redirects_authenticated_executive_to_dashboard(): void
    {
        $executive = User::factory()->executive()->create();

        $this->actingAs($executive)
            ->get('/')
            ->assertRedirect(route('executive.dashboard'));
    }

    public function test_login_redirects_authenticated_user_to_dashboard(): void
    {
        $executive = User::factory()->executive()->create();

        $this->actingAs($executive)
            ->get(route('login'))
            ->assertRedirect(route('executive.dashboard'));
    }
}
