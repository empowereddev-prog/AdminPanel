<?php

namespace App\Services\School;

use Illuminate\Support\Str;

class TemporaryPassword
{
    /**
     * 11 characters with upper, lower, digit and symbol so it satisfies the
     * reset-page policy; ~36 bits of randomness instead of the old ~18.
     */
    public static function generate(): string
    {
        return 'Stu@'.Str::random(6).random_int(1, 9);
    }
}
