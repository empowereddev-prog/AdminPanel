<?php

namespace Tests\Unit;

use App\Services\TeacherSignupEmail;
use Database\Seeders\SchoolEmailTemplateSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Mime\Email;
use Tests\TestCase;

class TeacherSignupEmailTest extends TestCase
{
    public function test_qr_is_embedded_at_the_template_marker_with_the_website_link(): void
    {
        $email = (new Email())->html('<p>Staff credentials</p>' . TeacherSignupEmail::DOWNLOAD_MARKER . '<p>Footer</p>');

        app(TeacherSignupEmail::class)->addDownloadBlock(new Message($email));

        $body = $email->getHtmlBody();
        $this->assertStringNotContainsString(TeacherSignupEmail::DOWNLOAD_MARKER, $body);
        $this->assertStringContainsString('href="https://empoweredhealth.asia/"', $body);
        $this->assertStringContainsString('src="cid:', $body);
        $this->assertLessThan(strpos($body, '<p>Footer</p>'), strpos($body, 'src="cid:'));
        $this->assertCount(1, $email->getAttachments());
        $image = $email->getAttachments()[0];
        $this->assertSame('inline', $image->getDisposition());
        $this->assertSame('image', $image->getMediaType());
        $this->assertSame('jpeg', $image->getMediaSubtype());
        $this->assertSame(
            hash_file('sha256', resource_path('images/email/teacher-app-download-qr.jpeg')),
            hash('sha256', base64_decode($image->bodyToString()))
        );
    }

    public function test_existing_database_copy_is_preserved_and_receives_the_qr_without_reseeding(): void
    {
        $original = '<p>Custom school wording and staff credentials</p>';
        $email = (new Email())->html($original);

        app(TeacherSignupEmail::class)->addDownloadBlock(new Message($email));

        $this->assertStringStartsWith($original, $email->getHtmlBody());
        $this->assertStringContainsString('src="cid:', $email->getHtmlBody());
        $this->assertStringContainsString('href="https://empoweredhealth.asia/"', $email->getHtmlBody());
        $this->assertCount(1, $email->getAttachments());
    }

    public function test_legacy_full_html_keeps_the_download_block_inside_the_body(): void
    {
        $email = (new Email())->html('<!DOCTYPE html><html><body><p>Staff access and credentials</p></BODY></html>');

        app(TeacherSignupEmail::class)->addDownloadBlock(new Message($email));

        $body = $email->getHtmlBody();
        $this->assertStringStartsWith('<!DOCTYPE html><html><body><p>Staff access and credentials</p>', $body);
        $this->assertLessThan(strpos($body, '</BODY>'), strpos($body, 'Get the EmpowerED app'));
        $this->assertStringEndsWith('</BODY></html>', $body);
        $this->assertCount(1, $email->getAttachments());
    }

    public function test_real_mail_helper_embeds_the_teacher_qr_and_leaves_parent_mail_unchanged(): void
    {
        // This test never uses the database configured in .env.
        config([
            'database.default' => 'teacher_qr_test',
            'database.connections.teacher_qr_test' => [
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
        $transport = Mail::mailer()->getSymfonyTransport();
        $transport->flush();
        $data = [
            'name' => 'Sample Teacher',
            'username' => 'sample_teacher',
            'password' => 'Example@123',
            'school_name' => 'Sample School',
            'year' => '2026',
        ];

        $this->assertTrue(___mail_sender('teacher@example.test', 'signup_teacher', $data, 'english'));
        $teacherMail = collect($transport->messages())->last()->getOriginalMessage();
        $this->assertStringContainsString('sample_teacher', $teacherMail->getHtmlBody());
        $this->assertStringContainsString('Example@123', $teacherMail->getHtmlBody());
        $this->assertStringContainsString('href="https://empoweredhealth.asia/"', $teacherMail->getHtmlBody());
        $this->assertStringContainsString('src="cid:', $teacherMail->getHtmlBody());
        $this->assertStringNotContainsString(TeacherSignupEmail::DOWNLOAD_MARKER, $teacherMail->getHtmlBody());
        $this->assertCount(1, $teacherMail->getAttachments());

        $this->assertTrue(___mail_sender('parent@example.test', 'signup_school_user', $data + [
            'email' => 'parent@example.test',
            'school_code' => 'SAMPLE',
        ], 'english'));
        $parentMail = collect($transport->messages())->last()->getOriginalMessage();
        $this->assertStringNotContainsString('src="cid:', $parentMail->getHtmlBody());
        $this->assertCount(0, $parentMail->getAttachments());
    }
}
