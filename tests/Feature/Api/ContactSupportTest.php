<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Laravel\Passport\Passport;
use Tests\TestCase;

class ContactSupportTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'contact_support_test',
            'database.connections.contact_support_test' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
            ],
            'mail.default' => 'array',
        ]);

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('email');
            $table->string('user_type');
            $table->softDeletes();
        });
        DB::table('users')->insert([
            'email' => 'admin@example.test',
            'user_type' => 'admin',
        ]);

        Schema::create('contact_us', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('subject');
            $table->text('message');
            $table->timestamps();
        });
        Schema::create('email_templates', function (Blueprint $table) {
            $table->id();
            $table->string('variable_name');
            $table->string('language');
            $table->string('subject');
            $table->string('variables');
            $table->text('description');
        });
        foreach (['contact_support', 'contact_support_user'] as $code) {
            DB::table('email_templates')->insert([
                'variable_name' => $code,
                'language' => 'english',
                'subject' => '{subject}',
                'variables' => '{subject},{message}',
                'description' => '<p>{message}</p>',
            ]);
        }

        Passport::actingAs(new User([
            'id' => 100,
            'email' => 'parent@example.test',
            'user_type' => 'parent',
            'status' => 'active',
        ]), [], 'api');
    }

    public function test_mobile_payload_without_subject_saves_and_sends_english_emails(): void
    {
        $this->postJson('/api/contact-support', [
            'name' => 'radha',
            'email' => 'caller@example.test',
            'message' => 'this',
            'laguage' => 'en',
        ])->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonPath('message', 'Your message has been sent successfully!')
            ->assertJsonPath('data.subject', 'Contact Support')
            ->assertJsonPath('data.email', 'parent@example.test');

        $this->assertDatabaseHas('contact_us', [
            'name' => 'radha',
            'email' => 'parent@example.test',
            'subject' => 'Contact Support',
            'message' => 'this',
        ]);
        $messages = collect(Mail::mailer()->getSymfonyTransport()->messages());
        $this->assertCount(2, $messages);
        $this->assertSame('Contact Support', $messages->first()->getOriginalMessage()->getSubject());
        $this->assertSame('admin@example.test', $messages->first()->getOriginalMessage()->getTo()[0]->getAddress());
        $this->assertSame('parent@example.test', $messages->last()->getOriginalMessage()->getTo()[0]->getAddress());
    }

    public function test_explicit_subject_and_canonical_language_take_precedence(): void
    {
        $this->postJson('/api/contact-support', [
            'name' => 'Parent',
            'subject' => 'Account help',
            'message' => 'Please help',
            'language' => 'en',
            'laguage' => 'zh',
        ])->assertOk()
            ->assertJsonPath('data.subject', 'Account help')
            ->assertJsonPath('message', 'Your message has been sent successfully!');
    }

    public function test_empty_subject_uses_default_and_invalid_subject_is_rejected(): void
    {
        $payload = ['name' => 'Parent', 'message' => 'Please help'];

        $this->postJson('/api/contact-support', $payload + ['subject' => ''])
            ->assertOk()->assertJsonPath('data.subject', 'Contact Support');

        $this->postJson('/api/contact-support', $payload + ['subject' => ['invalid']])
            ->assertStatus(422)->assertJsonPath('status', false);
        $this->postJson('/api/contact-support', $payload + ['subject' => str_repeat('x', 256)])
            ->assertStatus(422)->assertJsonPath('status', false);
        $this->postJson('/api/contact-support', ['name' => 'Parent'])
            ->assertStatus(422)->assertJsonPath('message', 'The message field is required.');

        $this->assertDatabaseCount('contact_us', 1);
    }
}
