<?php

namespace App\Support;

use Illuminate\Support\Str;

class PasswordGenerator
{
    public static function random(int $length = 12): string
    {
        return Str::password($length, symbols: false);
    }
}
