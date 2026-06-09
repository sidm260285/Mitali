<?php

namespace Database\Factories;

use App\Models\AccountHead;
use App\Models\CashTransaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CashTransaction>
 */
class CashTransactionFactory extends Factory
{
    protected $model = CashTransaction::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'account_head_id' => AccountHead::factory()->credit(),
            'type' => CashTransaction::TYPE_CREDIT,
            'amount' => fake()->randomFloat(2, 100, 5000),
            'transaction_date' => now()->toDateString(),
            'narration' => fake()->optional()->sentence(),
        ];
    }
}
