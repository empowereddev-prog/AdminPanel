<?php

namespace Tests\Unit;

use App\Support\Brand;
use PHPUnit\Framework\TestCase;

class BrandTest extends TestCase
{
    public function test_variant_spellings_become_the_canonical_brand(): void
    {
        $this->assertSame('Welcome to EmpowerED', Brand::normalize('Welcome to EmpowerEd'));
        $this->assertSame('EmpowerED Health', Brand::normalize('Empowered Health'));
        $this->assertSame('EmpowerED', Brand::normalize('Empower Ed'));
        $this->assertSame('EmpowerED', Brand::normalize('EmpowerED'));
    }

    public function test_domains_addresses_and_ordinary_words_are_untouched(): void
    {
        $text = 'Visit https://empoweredhealth.asia or info@empoweredhealth.asia; teens feel empowered. empowered://open';

        $this->assertSame($text, Brand::normalize($text));
    }

    public function test_null_is_an_empty_string(): void
    {
        $this->assertSame('', Brand::normalize(null));
    }

    /** The copy shipped in the repo must already use the canonical spelling. */
    public function test_shipped_mail_and_verification_copy_uses_the_canonical_brand(): void
    {
        $root = dirname(__DIR__, 2);
        $paths = array_merge(
            glob($root . '/database/seeders/*.php'),
            glob($root . '/database/seeders/Emails/*.php'),
            glob($root . '/resources/views/emails/*.php'),
            [$root . '/resources/views/verifySignUp.blade.php', $root . '/app/helper.php']
        );

        foreach ($paths as $path) {
            $this->assertSame(
                0,
                preg_match('/\b(?:Empower Ed|Empower ED|EmpowerEd)\b/', (string) file_get_contents($path)),
                basename($path) . ' contains a non-canonical spelling of the brand.'
            );
        }
    }
}
