<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'username' => fake()->unique()->userName(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->numerify('##########'),
            'address' => fake()->optional()->address(),
            'role' => User::ROLE_EXECUTIVE,
            'is_active' => true,
            'must_change_password' => false,
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => User::ROLE_ADMIN,
            'username' => 'admin'.fake()->unique()->numerify('###'),
        ]);
    }

    public function executive(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => User::ROLE_EXECUTIVE,
        ]);
    }

    public function bank(): static
    {
        return $this->state(function (array $attributes) {
            $suffix = fake()->unique()->numerify('#####');
            $accountNo = fake()->numerify('#############').$suffix;

            return [
                'role' => User::ROLE_BANK,
                'name' => fake()->company(),
                'username' => $suffix,
                'account_no' => $accountNo,
                'account_type' => fake()->randomElement([User::ACCOUNT_TYPE_SAVINGS, User::ACCOUNT_TYPE_CURRENT]),
                'branch_name' => fake()->city().' Branch',
                'phone' => null,
                'email' => null,
            ];
        });
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function mustChangePassword(): static
    {
        return $this->state(fn (array $attributes) => [
            'must_change_password' => true,
        ]);
    }
}
