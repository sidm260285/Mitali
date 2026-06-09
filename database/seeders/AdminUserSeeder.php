<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'Administrator',
                'email' => null,
                'phone' => '9999999999',
                'address' => null,
                'password' => 'Password@123',
                'role' => User::ROLE_ADMIN,
                'is_active' => true,
                'must_change_password' => false,
            ]
        );
    }
}
