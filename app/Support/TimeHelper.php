<?php

namespace App\Support;

use InvalidArgumentException;

class TimeHelper
{
    public const MIN_MINUTES = 60; // 1:00 AM

    public const MAX_MINUTES = 1380; // 11:00 PM

    public static function fromParts(int $hour, int $minute, string $period): string
    {
        $hour = self::to24Hour($hour, $period);

        return sprintf('%02d:%02d:00', $hour, $minute);
    }

    public static function toParts(string $time): array
    {
        [$hour, $minute] = array_map('intval', explode(':', substr($time, 0, 5)));
        $period = $hour >= 12 ? 'PM' : 'AM';
        $displayHour = $hour % 12;
        $displayHour = $displayHour === 0 ? 12 : $displayHour;

        return [
            'hour' => $displayHour,
            'minute' => $minute,
            'period' => $period,
        ];
    }

    public static function toMinutes(string $time): int
    {
        [$hour, $minute] = array_map('intval', explode(':', substr($time, 0, 5)));

        return ($hour * 60) + $minute;
    }

    public static function format12Hour(string $time): string
    {
        $parts = self::toParts($time);

        return sprintf('%d:%02d %s', $parts['hour'], $parts['minute'], $parts['period']);
    }

    public static function isWithinAllowedWindow(string $time): bool
    {
        $minutes = self::toMinutes($time);

        return $minutes >= self::MIN_MINUTES && $minutes <= self::MAX_MINUTES;
    }

    private static function to24Hour(int $hour, string $period): int
    {
        $period = strtoupper($period);

        if ($period === 'AM') {
            return $hour === 12 ? 0 : $hour;
        }

        if ($period === 'PM') {
            return $hour === 12 ? 12 : $hour + 12;
        }

        throw new InvalidArgumentException('Invalid time period.');
    }
}
