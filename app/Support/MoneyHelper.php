<?php

namespace App\Support;

class MoneyHelper
{
    public static function format(float|string|null $amount): string
    {
        return '₹'.number_format((float) $amount, 2);
    }
}
