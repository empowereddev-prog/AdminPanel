<?php

namespace Tests\Feature\School;

use App\Models\EmailTemplate;
use Database\Seeders\SchoolEmailTemplateSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class MailSenderTest extends TestCase
{
    use DatabaseTransactions;

    public function test_missing_template_returns_false(): void
    {
        EmailTemplate::where('variable_name', 'definitely_not_a_template')->delete();

        $ok = ___mail_sender('nobody@example.test', 'definitely_not_a_template', ['year' => '2026'], 'english');

        $this->assertFalse($ok);
    }

    public function test_seeded_template_returns_true(): void
    {
        (new SchoolEmailTemplateSeeder())->run();
        Mail::mailer()->getSymfonyTransport()->flush();

        $ok = ___mail_sender('office@school.test', 'school_onboarded', [
            'school_name' => 'Riverside',
            'school_code' => 'ABC12345',
            'subscription_type' => 'Monthly',
            'parent_limit' => 'Unlimited',
            'child_seat_limit' => '50',
            'year' => '2026',
        ], 'english');

        $this->assertTrue($ok);
    }
}
