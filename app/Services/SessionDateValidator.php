<?php

namespace App\Services;

use Carbon\Carbon;

class SessionDateValidator
{
    public function validate(?string $fromDate, ?string $toDate, ?string $extendedDate): ?string
    {
        if (! $fromDate || ! $toDate || ! $extendedDate) {
            return 'From Date, To Date, and Extended Date are required.';
        }

        $from = Carbon::parse($fromDate)->startOfDay();
        $to = Carbon::parse($toDate)->startOfDay();
        $extended = Carbon::parse($extendedDate)->startOfDay();

        if ($from->year !== $to->year || $to->year !== $extended->year) {
            return 'From Date, To Date, and Extended Date must be in the same year.';
        }

        if (! $this->isAtLeastOneMonthBefore($from, $to)) {
            return 'From Date must be at least 1 month before To Date.';
        }

        if (! $this->isAtLeastOneMonthBefore($to, $extended)) {
            return 'To Date must be at least 1 month before Extended Date.';
        }

        return null;
    }

    private function isAtLeastOneMonthBefore(Carbon $earlier, Carbon $later): bool
    {
        return $earlier->copy()->addMonthNoOverflow()->lte($later);
    }
}
