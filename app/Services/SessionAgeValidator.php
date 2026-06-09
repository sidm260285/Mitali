<?php

namespace App\Services;

class SessionAgeValidator
{
    public const MIN_AGE = 1;

    public const MAX_AGE = 150;

    public function validate(array $ages): ?string
    {
        if (count($ages) < 1) {
            return 'At least one age range is required.';
        }

        $normalized = [];

        foreach ($ages as $index => $age) {
            $from = (int) ($age['from_age'] ?? 0);
            $to = (int) ($age['to_age'] ?? 0);
            $row = $index + 1;
            $label = $from.'–'.$to;

            if ($from < self::MIN_AGE || $to > self::MAX_AGE) {
                return sprintf(
                    'Age row %d (%s): ages must be between %d and %d.',
                    $row,
                    $label,
                    self::MIN_AGE,
                    self::MAX_AGE,
                );
            }

            if ($from > $to) {
                return sprintf(
                    'Age row %d (%s): from age must be less than or equal to to age.',
                    $row,
                    $label,
                );
            }

            $normalized[] = [
                'from' => $from,
                'to' => $to,
                'row' => $row,
                'label' => $label,
            ];
        }

        usort($normalized, fn ($a, $b) => $a['from'] <=> $b['from']);

        if ($normalized[0]['from'] !== self::MIN_AGE) {
            return sprintf(
                'Age ranges must start from %d. Row %d (%s) is the earliest range but does not begin at %d.',
                self::MIN_AGE,
                $normalized[0]['row'],
                $normalized[0]['label'],
                self::MIN_AGE,
            );
        }

        $last = $normalized[array_key_last($normalized)];

        if ($last['to'] !== self::MAX_AGE) {
            return sprintf(
                'Age ranges must cover up to %d. Row %d (%s) is the last range but does not end at %d.',
                self::MAX_AGE,
                $last['row'],
                $last['label'],
                self::MAX_AGE,
            );
        }

        for ($i = 1; $i < count($normalized); $i++) {
            $previous = $normalized[$i - 1];
            $current = $normalized[$i];

            if ($current['from'] <= $previous['to']) {
                return sprintf(
                    'Age row %d (%s) overlaps with age row %d (%s).',
                    $current['row'],
                    $current['label'],
                    $previous['row'],
                    $previous['label'],
                );
            }

            if ($current['from'] !== $previous['to'] + 1) {
                return sprintf(
                    'Gap between age row %d (%s) and age row %d (%s). Expected row %d to start at age %d.',
                    $previous['row'],
                    $previous['label'],
                    $current['row'],
                    $current['label'],
                    $current['row'],
                    $previous['to'] + 1,
                );
            }
        }

        return null;
    }
}
