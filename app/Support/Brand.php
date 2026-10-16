<?php

namespace App\Support;

/**
 * The product name as it must read in anything a person sees. The canonical
 * spelling is "EmpowerED"; variants such as "EmpowerEd" or "Empower Ed" crept
 * in through hand-edited email_templates rows and the admin mail settings,
 * which code changes alone cannot reach.
 */
class Brand
{
    public const NAME = 'EmpowerED';

    /**
     * Case-sensitive on purpose: "empowered" is also an ordinary English word
     * and part of the domain empoweredhealth.asia, neither of which may change.
     */
    private const VARIANTS = '/\b(?:Empower Ed|Empower ED|EmpowerEd|Empowered(?= Health))\b/';

    public static function normalize(?string $text): string
    {
        return (string) preg_replace(self::VARIANTS, self::NAME, (string) $text);
    }
}
