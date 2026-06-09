<?php

namespace Database\Factories;

use App\Models\AccountHead;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AccountHead>
 */
class AccountHeadFactory extends Factory
{
    protected $model = AccountHead::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'type' => fake()->randomElement([AccountHead::TYPE_CREDIT, AccountHead::TYPE_DEBIT]),
            'is_system' => false,
        ];
    }

    public function credit(): static
    {
        return $this->state(fn () => ['type' => AccountHead::TYPE_CREDIT]);
    }

    public function debit(): static
    {
        return $this->state(fn () => ['type' => AccountHead::TYPE_DEBIT]);
    }
}
