<?php

namespace Database\Factories;

use App\Models\Session;
use App\Models\SessionBatch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SessionBatch>
 */
class SessionBatchFactory extends Factory
{
    protected $model = SessionBatch::class;

    public function definition(): array
    {
        return [
            'session_id' => Session::factory(),
            'start_time' => '05:20:00',
            'end_time' => '06:00:00',
            'buffer_time' => 10,
            'max_size' => 30,
            'admission_counter' => 0,
            'attendance_counter' => 0,
        ];
    }
}
