<?php

namespace Database\Factories;

use App\Models\Session;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Session>
 */
class SessionFactory extends Factory
{
    protected $model = Session::class;

    public function definition(): array
    {
        return [
            'name' => 'Session '.fake()->unique()->year(),
            'status' => Session::STATUS_UPCOMING,
            'form_fee' => 0,
            'admission_count' => 0,
            'attendance_count' => 0,
        ];
    }

    public function current(): static
    {
        return $this->state(fn () => ['status' => Session::STATUS_CURRENT]);
    }

    public function over(): static
    {
        return $this->state(fn () => [
            'status' => Session::STATUS_OVER,
            'admission_count' => 1,
        ]);
    }

    public function withAttendance(): static
    {
        return $this->state(fn () => ['attendance_count' => 1]);
    }
}
