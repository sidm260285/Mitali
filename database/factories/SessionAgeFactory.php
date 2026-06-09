<?php

namespace Database\Factories;

use App\Models\Session;
use App\Models\SessionAge;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SessionAge>
 */
class SessionAgeFactory extends Factory
{
    protected $model = SessionAge::class;

    public function definition(): array
    {
        return [
            'session_id' => Session::factory(),
            'from_age' => 1,
            'to_age' => 150,
            'fee' => 3000,
            'admission_counter' => 0,
            'attendance_counter' => 0,
        ];
    }
}
