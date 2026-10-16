<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Database\Seeders\AccountEmailTemplateSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ParentLoginEmailVerificationTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        (new AccountEmailTemplateSeeder())->run();
        Mail::mailer()->getSymfonyTransport()->flush();
    }

    private function parentWithEmailState(?\DateTimeInterface $verifiedAt): User
    {
        if (!DB::table('roles')->where('name', 'parent')->exists()) {
            DB::table('roles')->insert(['id' => 3, 'name' => 'parent', 'title' => 'Parent']);
        }

        return User::factory()->parent()->create([
            'country_code' => '+65',
            'phone_no' => '9' . random_int(1000000, 9999999),
            'password' => 'Secret@123',
            'email' => 'parent.' . uniqid() . '@example.test',
            'is_mobile_verified' => 'yes',
            'email_verified_at' => $verifiedAt,
            'school_id' => null,
        ]);
    }

    private function login(User $user)
    {
        return $this->postJson('/api/login', [
            'type' => 'parent',
            'country_code' => $user->country_code,
            'phone_no' => $user->phone_no,
            'password' => 'Secret@123',
            'language' => 'english',
            'device_token' => 'test-token',
        ]);
    }

    private function sentMessages()
    {
        return collect(Mail::mailer()->getSymfonyTransport()->messages());
    }

    public function test_unverified_email_login_is_refused_with_a_clear_message_and_resends_the_mail(): void
    {
        $user = $this->parentWithEmailState(null);

        $this->login($user)->assertOk()
            ->assertJsonPath('status', false)
            ->assertJsonPath('message', 'Your email is not verified. Please verify your email to continue using the app. We have sent you a new verification email.')
            ->assertJsonPath('data.requires_email_verification', true)
            ->assertJsonMissingPath('data.token');

        $mail = $this->sentMessages()->last()?->getOriginalMessage();
        $this->assertNotNull($mail, 'The verification email must be re-sent on a blocked login.');
        $this->assertSame('Verify your EmpowerED email address', $mail->getSubject());

        $body = $mail->getHtmlBody();
        $this->assertStringContainsString('/verify/' . $user->id . '/', $body);
        $this->assertStringContainsString($user->email, $body);
        $this->assertStringContainsString('Verify my email', $body);
        $this->assertSame([], $this->unreplacedTokens($body), 'Every token the template declares must be supplied.');
    }

    public function test_verified_email_login_succeeds(): void
    {
        $user = $this->parentWithEmailState(now());

        $this->login($user)->assertOk()->assertJsonPath('status', true);
        $this->assertCount(0, $this->sentMessages());
    }

    public function test_the_emailed_link_verifies_the_account_and_unblocks_login(): void
    {
        $user = $this->parentWithEmailState(null);

        $this->get(route('user.verify', ['user_id' => $user->id, 'email' => Crypt::encryptString($user->email)]))
            ->assertOk()->assertSee('Email verified');

        $this->assertNotNull($user->fresh()->email_verified_at);
        $this->login($user)->assertOk()->assertJsonPath('status', true);
    }

    public function test_a_link_issued_for_a_different_email_does_not_rewrite_the_account(): void
    {
        $user = $this->parentWithEmailState(null);
        $original = $user->email;

        $this->get(route('user.verify', ['user_id' => $user->id, 'email' => Crypt::encryptString('someone.else@example.test')]))
            ->assertOk()->assertSee('could not verify');

        $user->refresh();
        $this->assertSame($original, $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_a_malformed_link_is_a_friendly_error_not_a_500(): void
    {
        $user = $this->parentWithEmailState(null);

        $this->get(route('user.verify', ['user_id' => $user->id, 'email' => 'not-encrypted']))
            ->assertOk()->assertSee('Invalid verification link.');
    }

    /** @return array<int,string> */
    private function unreplacedTokens(string $text): array
    {
        preg_match_all('/\{[a-z_]+\}/', $text, $m);

        return array_values(array_unique($m[0]));
    }
}
