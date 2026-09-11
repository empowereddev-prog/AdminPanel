<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Passport\Passport;
use Tests\TestCase;

/**
 * Phase 1 contract: every api/* response is JSON, even when the client sends
 * no Accept header. That path previously produced 302 redirects and HTML.
 */
class ApiContractTest extends TestCase
{
    use DatabaseTransactions;

    private function makeUser(array $attrs = []): User
    {
        return User::create(array_merge([
            'name' => 'Contract User',
            'email' => 'c' . uniqid() . '@example.test',
            'password' => bcrypt('OldPass1@'),
            'user_type' => 'parent',
            'user_role_id' => 3,
            'status' => 'active',
            'language' => 'english',
        ], $attrs));
    }

    /** A validation failure without an Accept header used to be a 302 to '/'. */
    public function test_validation_failure_returns_json_without_accept_header(): void
    {
        $response = $this->post('/api/resend-otp', []); // uses $request->validate()

        $response->assertStatus(422);
        $this->assertStringContainsString('application/json', $response->headers->get('Content-Type'));
        $response->assertJsonStructure(['status', 'message', 'data', 'errors']);
        $response->assertJsonPath('status', false);
    }

    /** Laravel's native errors object is preserved for v1 clients. */
    public function test_validation_failure_keeps_laravel_errors_key(): void
    {
        $this->post('/api/resend-otp', [])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['phone_no']]);
    }

    /** An unauthenticated call used to redirect to the web login page. */
    public function test_unauthenticated_request_returns_401_json(): void
    {
        $response = $this->get('/api/get-profile');

        $response->assertStatus(401);
        $this->assertStringContainsString('application/json', $response->headers->get('Content-Type'));
        $response->assertJsonPath('status', false);
    }

    /** An unknown api route returns JSON, not the HTML 404 page. */
    public function test_unknown_api_route_returns_json_404(): void
    {
        $response = $this->post('/api/no-such-endpoint', []);

        $response->assertStatus(404);
        $this->assertStringContainsString('application/json', $response->headers->get('Content-Type'));
    }

    /** Wrong verb returns JSON 405 rather than an HTML error page. */
    public function test_method_not_allowed_returns_json(): void
    {
        $response = $this->get('/api/verify-otp');

        $response->assertStatus(405);
        $this->assertStringContainsString('application/json', $response->headers->get('Content-Type'));
    }

    /** An inactive account is rejected by apicheckstatus. */
    public function test_inactive_account_is_rejected(): void
    {
        $user = $this->makeUser(['status' => 'inactive']);
        Passport::actingAs($user, [], 'api');

        $this->getJson('/api/get-profile')
            ->assertStatus(401)
            ->assertJsonPath('status', false);
    }

    /** v2 upgrades that same rejection to a correct 403. */
    public function test_inactive_account_is_403_under_v2(): void
    {
        $user = $this->makeUser(['status' => 'inactive']);
        Passport::actingAs($user, [], 'api');

        $this->getJson('/api/get-profile', ['Accept-Version' => '2'])
            ->assertStatus(403)
            ->assertJsonPath('status', false);
    }

    /**
     * FormRequest::failedValidation throws HttpResponseException. The parent
     * handler returns its response before anything else; the api/* branch must
     * do the same or every FormRequest failure becomes a 500.
     */
    public function test_form_request_validation_failure_is_not_a_500(): void
    {
        $teacher = $this->makeUser(['user_type' => 'teacher', 'user_role_id' => 5]);
        Passport::actingAs($teacher, [], 'api');

        $response = $this->postJson('/api/update-teacher-profile', []);

        $this->assertNotSame(500, $response->getStatusCode());
        $response->assertJsonPath('status', false);
    }

    /** An unauthorized FormRequest returns JSON 403, not the HTML 403 page. */
    public function test_form_request_authorization_failure_returns_json_403(): void
    {
        $parent = $this->makeUser(); // not a teacher
        Passport::actingAs($parent, [], 'api');

        $response = $this->postJson('/api/update-teacher-profile', []);

        $response->assertStatus(403)->assertJsonPath('status', false);
        $this->assertStringContainsString('application/json', $response->headers->get('Content-Type'));
    }

    /** A raw HttpResponseException keeps its own status and body. */
    public function test_http_response_exception_is_returned_verbatim(): void
    {
        $handler = app(\App\Exceptions\Handler::class);
        $request = \Illuminate\Http\Request::create('/api/probe', 'POST');

        $response = $handler->render($request, new \Illuminate\Http\Exceptions\HttpResponseException(
            response()->json(['status' => false, 'probe' => true], 422)
        ));

        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame('{"status":false,"probe":true}', $response->getContent());
    }

    /** The web side must keep its redirect behaviour. */
    public function test_web_routes_still_redirect_when_unauthenticated(): void
    {
        $response = $this->get('/admin/dashboard');

        $this->assertNotSame(
            401,
            $response->getStatusCode(),
            'Web routes must not be switched to the API JSON contract.'
        );
    }
}
