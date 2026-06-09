<?php

namespace Tests\Unit;

use App\Services\SessionBatchValidator;
use App\Support\TimeHelper;
use PHPUnit\Framework\TestCase;

class BatchOverlapReproTest extends TestCase
{
    private function normalize(array $batch): array
    {
        return [
            'start_time' => TimeHelper::fromParts(
                (int) $batch['start_hour'],
                (int) $batch['start_minute'],
                $batch['start_period'],
            ),
            'end_time' => TimeHelper::fromParts(
                (int) $batch['end_hour'],
                (int) $batch['end_minute'],
                $batch['end_period'],
            ),
        ];
    }

    public function test_am_pm_mix_shows_row_specific_error(): void
    {
        $batches = [
            ['start_hour' => 6, 'start_minute' => 20, 'start_period' => 'AM', 'end_hour' => 7, 'end_minute' => 0, 'end_period' => 'AM'],
            ['start_hour' => 7, 'start_minute' => 1, 'start_period' => 'AM', 'end_hour' => 7, 'end_minute' => 40, 'end_period' => 'AM'],
            ['start_hour' => 10, 'start_minute' => 0, 'start_period' => 'AM', 'end_hour' => 11, 'end_minute' => 0, 'end_period' => 'AM'],
            ['start_hour' => 4, 'start_minute' => 0, 'start_period' => 'AM', 'end_hour' => 5, 'end_minute' => 0, 'end_period' => 'PM'],
        ];

        $validator = new SessionBatchValidator;
        $error = $validator->validate(array_map($this->normalize(...), $batches));

        $this->assertStringContainsString('Batch row 4', $error);
        $this->assertStringContainsString('set the start time to PM', $error);
    }

    public function test_screenshot_batches_with_pm_start_passes(): void
    {
        $batches = [
            ['start_hour' => 6, 'start_minute' => 20, 'start_period' => 'AM', 'end_hour' => 7, 'end_minute' => 0, 'end_period' => 'AM'],
            ['start_hour' => 7, 'start_minute' => 1, 'start_period' => 'AM', 'end_hour' => 7, 'end_minute' => 40, 'end_period' => 'AM'],
            ['start_hour' => 10, 'start_minute' => 0, 'start_period' => 'AM', 'end_hour' => 11, 'end_minute' => 0, 'end_period' => 'AM'],
            ['start_hour' => 4, 'start_minute' => 0, 'start_period' => 'PM', 'end_hour' => 5, 'end_minute' => 0, 'end_period' => 'PM'],
        ];

        $validator = new SessionBatchValidator;
        $this->assertNull($validator->validate(array_map($this->normalize(...), $batches)));
    }
}
