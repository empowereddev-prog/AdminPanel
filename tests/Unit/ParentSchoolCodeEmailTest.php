<?php

namespace Tests\Unit;

use App\Http\Controllers\Admin\SchoolParentCodeController;
use App\Jobs\SendSchoolParentCodeMail;
use App\Models\School;
use Barryvdh\DomPDF\Facade\Pdf;
use Database\Seeders\SchoolEmailTemplateSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * In-memory SQLite only; never the database from .env.
 */
class ParentSchoolCodeEmailTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config([
            'database.default' => 'parent_code_test',
            'database.connections.parent_code_test' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''],
            'mail.default' => 'array',
        ]);
        Schema::create('email_templates', function (Blueprint $table) {
            $table->id();
            $table->string('variable_name');
            $table->string('language');
            $table->string('subject');
            $table->string('variables');
            $table->text('description');
            $table->boolean('active');
            $table->timestamps();
        });
        (new SchoolEmailTemplateSeeder())->run();
        Mail::mailer()->getSymfonyTransport()->flush();
    }

    public function test_email_list_is_split_normalised_and_deduplicated(): void
    {
        $parsed = SchoolParentCodeController::parseEmails("A@Example.com, b@example.com;\nnot-an-email  a@example.com\n\n c@example.com");

        $this->assertSame(['a@example.com', 'b@example.com', 'c@example.com'], $parsed['valid']);
        $this->assertSame(['not-an-email'], $parsed['invalid']);
    }

    public function test_job_sends_the_code_with_qr_and_website_link_and_no_unreplaced_tokens(): void
    {
        (new SendSchoolParentCodeMail(['p1@example.com', 'p2@example.com'], 'Oak School', 'SCH-123456'))->handle();

        $messages = collect(Mail::mailer()->getSymfonyTransport()->messages());
        $this->assertCount(2, $messages);
        $mail = $messages->first()->getOriginalMessage();
        $body = $mail->getHtmlBody();
        $this->assertSame('Join Oak School on EmpowerED', $mail->getSubject());
        $this->assertStringContainsString('SCH-123456', $body);
        $this->assertStringContainsString('src="cid:', $body);
        $this->assertStringContainsString('href="https://empoweredhealth.asia/"', $body);
        preg_match_all('/\{[a-z_]+\}/', $mail->getSubject() . $body, $m);
        $this->assertSame([], $m[0]);
    }

    public function test_recipients_are_chunked_and_a_schedule_delays_every_chunk(): void
    {
        Queue::fake();
        $emails = array_map(fn ($i) => "p{$i}@example.com", range(1, 45));
        $sendAt = now()->addDay();

        $chunks = SendSchoolParentCodeMail::dispatchInChunks($emails, 'Oak School', 'SCH-1', $sendAt);

        $this->assertSame(3, $chunks);
        Queue::assertPushed(SendSchoolParentCodeMail::class, 3);
        Queue::assertPushed(SendSchoolParentCodeMail::class, fn ($job) => $job->delay !== null);
    }

    public function test_printable_letter_renders_to_a_pdf_with_the_code(): void
    {
        $school = new School(['name' => 'Oak School', 'school_code' => 'SCH-654321']);
        $qr = 'data:image/jpeg;base64,' . base64_encode(file_get_contents(resource_path('images/email/teacher-app-download-qr.jpeg')));
        $html = view('pdf.school-parent-letter', ['school' => $school, 'qr' => $qr, 'website' => 'https://empoweredhealth.asia/'])->render();

        $this->assertStringContainsString('SCH-654321', $html);
        $this->assertStringContainsString('Oak School', $html);
        $pdf = Pdf::loadHTML($html)->output();
        $this->assertStringStartsWith('%PDF', $pdf);
    }
}
