<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_user_seeder_creates_default_admin(): void
    {
        $this->seed(AdminUserSeeder::class);

        $admin = User::where('username', 'admin')->first();

        $this->assertNotNull($admin);
        $this->assertSame('Administrator', $admin->name);
        $this->assertTrue($admin->isAdmin());
        $this->assertTrue($admin->is_active);

        $this->post(route('login'), [
            'username' => 'admin',
            'password' => 'Password@123',
        ])->assertRedirect(route('admin.dashboard'));
    }
}
