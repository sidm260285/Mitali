<?php

namespace App\Services;

use App\Support\TimeHelper;

class SessionBatchValidator
{
    public function validate(array $batches): ?string
    {
        if (count($batches) < 1) {
            return 'At least one batch timing is required.';
        }

        $normalized = [];

        foreach ($batches as $index => $batch) {
            $start = $batch['start_time'] ?? '';
            $end = $batch['end_time'] ?? '';
            $row = $index + 1;
            $label = TimeHelper::format12Hour($start).' – '.TimeHelper::format12Hour($end);

            if (! TimeHelper::isWithinAllowedWindow($start) || ! TimeHelper::isWithinAllowedWindow($end)) {
                return sprintf(
                    'Batch row %d (%s): times must be between 1:00 AM and 11:00 PM.',
                    $row,
                    $label,
                );
            }

            $startMinutes = TimeHelper::toMinutes($start);
            $endMinutes = TimeHelper::toMinutes($end);

            if ($startMinutes >= $endMinutes) {
                return sprintf(
                    'Batch row %d (%s): start time must be earlier than end time.',
                    $row,
                    $label,
                );
            }

            if ($this->looksLikeAmPmMistake($startMinutes, $endMinutes)) {
                return sprintf(
                    'Batch row %d (%s): this slot spans morning to evening. If this is an afternoon batch, set the start time to PM as well.',
                    $row,
                    $label,
                );
            }

            $normalized[] = [
                'start' => $startMinutes,
                'end' => $endMinutes,
                'row' => $row,
                'label' => $label,
            ];
        }

        usort($normalized, fn ($a, $b) => $a['start'] <=> $b['start']);

        for ($i = 1; $i < count($normalized); $i++) {
            $previous = $normalized[$i - 1];
            $current = $normalized[$i];

            if ($current['start'] < $previous['end']) {
                return sprintf(
                    'Batch row %d (%s) overlaps with batch row %d (%s).',
                    $current['row'],
                    $current['label'],
                    $previous['row'],
                    $previous['label'],
                );
            }
        }

        return null;
    }

    private function looksLikeAmPmMistake(int $startMinutes, int $endMinutes): bool
    {
        $duration = $endMinutes - $startMinutes;
        $noon = 12 * 60;

        return $duration > (10 * 60)
            && $startMinutes < $noon
            && $endMinutes > $noon;
    }
}
