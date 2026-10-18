<?php

namespace Tests\Unit;

use App\Mail\SchoolStudentCredentials;
use App\Models\EmailTemplate;
use App\Services\School\CredentialsWorkbook;
use App\Services\TeacherSignupEmail;
use Database\Seeders\SchoolEmailTemplateSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Runs on an in-memory SQLite connection, never the database from .env.
 */
class StudentCredentialsEmailTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config([
            'database.default' => 'student_email_test',
            'database.connections.student_email_test' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
            ],
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

    private function lastMessage()
    {
        return collect(Mail::mailer()->getSymfonyTransport()->messages())->last()->getOriginalMessage();
    }

    public function test_student_template_is_seeded_editable_and_carries_the_qr_marker(): void
    {
        $row = EmailTemplate::where('variable_name', 'school_student_credentials')->where('language', 'english')->first();

        $this->assertNotNull($row);
        $this->assertStringContainsString(TeacherSignupEmail::DOWNLOAD_MARKER, $row->description);
        $this->assertFalse(view()->exists('emails.school_student_credentials'), 'A Blade view would make the template uneditable in admin.');
    }

    public function test_mailable_sends_school_wording_qr_website_link_and_the_spreadsheet(): void
    {
        $sheet = CredentialsWorkbook::build([['Name', 'Username'], ['Ann', 'ann_1']]);

        Mail::to('school@example.test')->send(new SchoolStudentCredentials('Oak School', $sheet, 12, 7));

        $mail = $this->lastMessage();
        $body = $mail->getHtmlBody();
        $this->assertSame('Student accounts are ready for Oak School', $mail->getSubject());
        $this->assertStringContainsString('Oak School', $body);
        $this->assertStringContainsString('(12 students)', $body);
        $this->assertStringContainsString('7 days', $body);
        $this->assertStringContainsString('src="cid:', $body);
        $this->assertStringContainsString('href="https://empoweredhealth.asia/"', $body);
        $this->assertStringNotContainsString(TeacherSignupEmail::DOWNLOAD_MARKER, $body);
        $this->assertSame([], $this->unreplacedTokens($mail->getSubject() . $body));

        $names = collect($mail->getAttachments())->map(fn ($a) => $a->getFilename() ?? $a->getPreparedHeaders()->getHeaderParameter('Content-Disposition', 'filename'))->all();
        $this->assertContains('student-credentials.xlsx', $names);
        $this->assertCount(2, $mail->getAttachments(), 'spreadsheet + inline QR');
    }

    public function test_mailable_falls_back_to_plain_text_when_the_template_row_is_missing(): void
    {
        EmailTemplate::where('variable_name', 'school_student_credentials')->delete();

        Mail::to('school@example.test')->send(new SchoolStudentCredentials('Oak School', 'xlsx-bytes', 3, 7));

        $mail = $this->lastMessage();
        $this->assertSame('Student credentials for Oak School', $mail->getSubject());
        $this->assertStringContainsString('valid for 7 days', $mail->getHtmlBody());
        $this->assertCount(1, $mail->getAttachments());
    }

    public function test_mail_helper_supports_attachments_and_applies_the_qr_to_the_student_template(): void
    {
        $this->assertTrue(___mail_sender('school@example.test', 'school_student_credentials', [
            'school_name' => 'Oak School',
            'student_count' => '5',
            'reset_days' => '7',
            'year' => '2026',
            'attachments' => [['name' => 'x.xlsx', 'content' => 'bytes', 'mime' => 'application/octet-stream']],
        ], 'english'));

        $mail = $this->lastMessage();
        $this->assertStringContainsString('src="cid:', $mail->getHtmlBody());
        $this->assertCount(2, $mail->getAttachments());
    }

    public function test_preview_inlines_the_qr_as_a_data_uri_and_removes_the_marker(): void
    {
        $html = app(TeacherSignupEmail::class)->previewHtml('<p>Hello</p>' . TeacherSignupEmail::DOWNLOAD_MARKER);

        $this->assertStringContainsString('src="data:image/jpeg;base64,', $html);
        $this->assertStringNotContainsString('cid:', $html);
        $this->assertStringNotContainsString(TeacherSignupEmail::DOWNLOAD_MARKER, $html);
    }

    public function test_qr_block_applies_to_the_onboarding_templates_only(): void
    {
        $this->assertTrue(TeacherSignupEmail::appliesTo('signup_teacher'));
        $this->assertTrue(TeacherSignupEmail::appliesTo('school_student_credentials'));
        $this->assertTrue(TeacherSignupEmail::appliesTo('school_parent_invited'));
        $this->assertFalse(TeacherSignupEmail::appliesTo('signup_school_user'));
    }

    /** @return array<int,string> */
    private function unreplacedTokens(string $text): array
    {
        preg_match_all('/\{[a-z_]+\}/', $text, $m);

        return array_values(array_unique($m[0]));
    }
}
