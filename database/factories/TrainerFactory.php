<?php

namespace Database\Factories;

use App\Models\Trainer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Trainer>
 */
class TrainerFactory extends Factory
{
    protected $model = Trainer::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'dob' => fake()->dateTimeBetween('-50 years', '-18 years')->format('Y-m-d'),
            'gender' => fake()->randomElement([
                Trainer::GENDER_MALE,
                Trainer::GENDER_FEMALE,
                Trainer::GENDER_OTHER,
            ]),
            'permanent_address' => fake()->address(),
            'current_address' => fake()->address(),
            'monthly_salary' => fake()->numberBetween(15000, 80000),
            'mobile' => fake()->unique()->numerify('9#########'),
            'alternative_mobile' => fake()->optional()->numerify('8#########'),
            'joining_date' => fake()->dateTimeBetween('-5 years', 'now')->format('Y-m-d'),
            'bank_account_number' => fake()->optional()->numerify('################'),
            'bank_account_holder_name' => null,
            'bank_name' => fake()->optional()->company(),
            'bank_ifsc' => fake()->optional()->regexify('[A-Z]{4}0[A-Z0-9]{6}'),
            'is_active' => true,
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (Trainer $trainer) {
            $trainer->bank_account_holder_name = $trainer->name;
        })->afterCreating(function (Trainer $trainer) {
            if (! $trainer->bank_account_holder_name) {
                $trainer->update(['bank_account_holder_name' => $trainer->name]);
            }
        });
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
