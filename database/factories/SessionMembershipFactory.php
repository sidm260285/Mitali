<?php

namespace Database\Factories;

use App\Models\Session;
use App\Models\SessionMembership;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SessionMembership>
 */
class SessionMembershipFactory extends Factory
{
    protected $model = SessionMembership::class;

    public function definition(): array
    {
        return [
            'session_id' => Session::factory(),
            'card_name' => fake()->unique()->words(2, true),
            'no_of_slot' => 1,
            'membership_cost' => 500,
            'admission_counter' => 0,
            'attendance_counter' => 0,
        ];
    }
}
