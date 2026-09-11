<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Route;
use Laravel\Passport\Passport;
use Tests\Support\ResponseSignature;
use Tests\TestCase;

/**
 * The Phase 2 migration gate.
 *
 * Every controller is being moved onto the shared ApiResponse envelope under an
 * additive-only constraint: the shipped mobile app must keep receiving the same
 * status codes and the same keys. This records the v1 shape of every endpoint
 * and fails on any drift.
 *
 * Regenerate deliberately, and only when a diff has been reviewed:
 *
 *     UPDATE_API_SNAPSHOTS=1 php vendor/bin/phpunit tests/Feature/Api/ResponseContractSnapshotTest.php
 */
class ResponseContractSnapshotTest extends TestCase
{
    use DatabaseTransactions;

    private const SNAPSHOT = __DIR__ . '/snapshots/v1_contract.json';

    private User $parent;
    private User $child;

    /**
     * The clock is frozen because mood-tracker's calendar_data is keyed by the
     * days of the current month. Left on the real clock the snapshot pins
     * September 2026 and every run from 1 October reports 30 keys "removed or
     * renamed" with no code change - and a gate that cries wolf gets
     * regenerated reflexively, which is how a real break would get waved
     * through. Any date inside the recorded month works; this one is it.
     */
    private const FROZEN_NOW = '2026-09-15 12:00:00';

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(self::FROZEN_NOW);

        $this->parent = User::create([
            'name' => 'Snapshot Parent',
            'email' => 'snapshot-parent@example.test',
            'password' => bcrypt('OldPass1@'),
            'user_type' => 'parent',
            'user_role_id' => 3,
            'status' => 'active',
            'language' => 'english',
            'country_code' => '+91',
            'phone_no' => '9000000001',
        ]);

        $this->child = User::create([
            'name' => 'Snapshot Child',
            'username' => 'snapshot_child',
            'email' => 'snapshot-child@example.test',
            'password' => bcrypt('OldPass1@'),
            'user_type' => 'child',
            'user_role_id' => 4,
            'status' => 'active',
            'language' => 'english',
            'parent_id' => $this->parent->id,
            'dob' => '2015-04',
            'battery_points' => 100,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    /**
     * Endpoint cases: label => [method, uri, payload, actAs].
     * Payloads are representative rather than exhaustive - the point is to pin
     * the response envelope, including the error envelopes.
     */
    private function cases(): array
    {
        $child = fn () => $this->child->id;

        return [
            // --- public ---
            'register:invalid'          => ['POST', 'api/register', []],
            'login:invalid'             => ['POST', 'api/login', ['type' => 'parent']],
            'login:missing-type'        => ['POST', 'api/login', []],
            'forgot-password:invalid'   => ['POST', 'api/forgot-password', []],
            'verify-otp:missing'        => ['POST', 'api/verify-otp', []],
            'verify-otp:wrong'          => ['POST', 'api/verify-otp', ['otp' => '9999', 'phone_no' => '9000000001', 'country_code' => '+91']],
            'resend-otp:invalid'        => ['POST', 'api/resend-otp', []],
            'student-login:invalid'     => ['POST', 'api/student-login', []],
            'individual-login:invalid'  => ['POST', 'api/individual-login', []],
            'get-child-support'         => ['GET', 'api/get-child-support', []],

            // --- authenticated ---
            'get-profile'               => ['GET', 'api/get-profile', [], 'parent'],
            'add-child:invalid'         => ['POST', 'api/add-child', [], 'parent'],
            'edit-child:invalid'        => ['POST', 'api/edit-child', [], 'parent'],
            'delete-child:unknown'      => ['POST', 'api/delete-child', ['id' => 99999999], 'parent'],
            'primary-child:unknown'     => ['POST', 'api/primary-child', ['id' => 99999999], 'parent'],
            'reset-password:invalid'    => ['POST', 'api/reset-password', ['user_type' => 'parent'], 'parent'],
            'get-child-profile'         => ['POST', 'api/get-child-profile', ['child_id' => $child], 'parent'],
            'parent-dashboard'          => ['POST', 'api/parent-dashboard', [], 'parent'],
            'child-percentage'          => ['POST', 'api/child-percentage', [], 'child'],
            'update-parent-profile'     => ['POST', 'api/update-parent-profile', ['name' => 'Renamed'], 'parent'],
            'update-teacher-profile:invalid' => ['POST', 'api/update-teacher-profile', [], 'parent'],
            'subscription:invalid'      => ['POST', 'api/subscription', [], 'parent'],

            'avtar-image'               => ['POST', 'api/avtar-image', ['id' => $child], 'parent'],
            'avtar-image:invalid'       => ['POST', 'api/avtar-image', [], 'parent'],
            'store-avtar:invalid'       => ['POST', 'api/store-avtar', [], 'parent'],
            'get-category'              => ['POST', 'api/get-category', [], 'child'],
            'unlock-avtars:invalid'     => ['POST', 'api/unlock-avtars', [], 'child'],

            'video-content'             => ['POST', 'api/video-content', ['user_id' => $child], 'parent'],
            'video-content-quiz'        => ['POST', 'api/video-content-quiz', ['user_id' => $child], 'parent'],
            'video-content-details'     => ['POST', 'api/video-content-details', ['video_id' => 999999], 'child'],
            'video-watch-status'        => ['POST', 'api/video-watch-status', ['user_id' => $child, 'category_id' => 1], 'parent'],
            'user-content-watch-histories:invalid' => ['POST', 'api/user-content-watch-histories', [], 'child'],
            'video-content-for-parent'  => ['POST', 'api/video-content-for-parent', [], 'parent'],
            'video-content-for-child'   => ['POST', 'api/video-content-for-child', [], 'child'],

            'knowledge-session'         => ['POST', 'api/knowledge-session', ['user_id' => $child], 'parent'],
            'knowledge-session:invalid' => ['POST', 'api/knowledge-session', [], 'parent'],
            'knowledge-session-details' => ['POST', 'api/knowledge-session-details', ['id' => 999999], 'child'],
            'knowledge-session-for-parent' => ['POST', 'api/knowledge-session-for-parent', ['user_id' => $child], 'parent'],
            'featured-content'          => ['POST', 'api/featured-content', ['user_id' => $child], 'parent'],
            'featured-content-for-parent' => ['POST', 'api/featured-content-for-parent', [], 'parent'],

            'article-like:invalid'      => ['POST', 'api/article-like', [], 'child'],
            'quiz-category'             => ['POST', 'api/quiz-category', [], 'child'],
            'quiz:invalid'              => ['POST', 'api/quiz', [], 'child'],
            'quiz-question:invalid'     => ['POST', 'api/quiz-question', [], 'child'],
            'user-attempt-questions:invalid' => ['POST', 'api/user-attempt-questions', [], 'child'],
            'quiz-completion:invalid'   => ['POST', 'api/quiz-completion', [], 'child'],

            'get-all-mood'              => ['POST', 'api/get-all-mood', [], 'child'],
            'store-child-mood:invalid'  => ['POST', 'api/store-child-mood', [], 'child'],
            'get-child-mood'            => ['POST', 'api/get-child-mood', [], 'child'],
            'activity-list'             => ['POST', 'api/activity-list', [], 'child'],
            'store-activity:invalid'    => ['POST', 'api/store-activity', [], 'child'],
            'get-suggested-activity'    => ['POST', 'api/get-suggested-activity', [], 'child'],
            'mood-tracker'              => ['POST', 'api/mood-tracker', [], 'child'],
            'child-support'             => ['POST', 'api/child-support', [], 'child'],
            'store-liked-content:invalid' => ['POST', 'api/store-liked-content', [], 'child'],

            'notification-list'         => ['POST', 'api/notification-list', [], 'parent'],
            'manage-notification:invalid' => ['POST', 'api/manage-notification', [], 'parent'],
            'contact-support:invalid'   => ['POST', 'api/contact-support', [], 'parent'],
            'mark-as-read'              => ['POST', 'api/mark-as-read', ['notification_id' => 999999], 'parent'],
            'delete-notification'       => ['POST', 'api/delete-notification', ['notification_id' => 999999], 'parent'],

            'products'                  => ['POST', 'api/products', [], 'parent'],
            'meet-team'                 => ['POST', 'api/meet-team', [], 'parent'],
            'faq'                       => ['GET', 'api/faq', [], 'parent'],
            'popup'                     => ['GET', 'api/popup', [], 'parent'],
            'popup-store'               => ['POST', 'api/popup-store', [], 'parent'],
            'request-video:invalid'     => ['POST', 'api/request-video', [], 'parent'],

            // --- contract guarantees that must not regress ---
            'unauthenticated'           => ['GET', 'api/get-profile', []],
            'unknown-endpoint'          => ['POST', 'api/does-not-exist', []],
        ];
    }

    public function test_v1_response_contract_has_not_drifted(): void
    {
        $actual = [];

        foreach ($this->cases() as $label => $case) {
            [$method, $uri, $payload] = $case;

            $this->refreshApplicationForCase($case[3] ?? null);

            $resolved = [];
            foreach ($payload as $key => $value) {
                $resolved[$key] = $value instanceof \Closure ? $value() : $value;
            }

            $response = $method === 'GET'
                ? $this->getJson('/' . $uri)
                : $this->postJson('/' . $uri, $resolved);

            $actual[$label] = ResponseSignature::of(
                $response->getStatusCode(),
                // Not assoc: an empty object must stay distinguishable from
                // an empty array. See ResponseSignature::shape().
                json_decode($response->getContent())
            );
        }

        ksort($actual);

        if (getenv('UPDATE_API_SNAPSHOTS')) {
            file_put_contents(
                self::SNAPSHOT,
                json_encode($actual, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n"
            );
            $this->markTestSkipped('Snapshot regenerated: ' . count($actual) . ' endpoints. Review the diff.');
        }

        $this->assertFileExists(
            self::SNAPSHOT,
            'Snapshot missing. Generate it with UPDATE_API_SNAPSHOTS=1.'
        );

        $expected = json_decode(file_get_contents(self::SNAPSHOT), true);

        foreach ($expected as $label => $shape) {
            $this->assertArrayHasKey($label, $actual, "Endpoint case '{$label}' disappeared.");

            // The status code is exact: moving one breaks clients that branch
            // on it, and it is never an "addition".
            $this->assertSame(
                $shape['status_code'],
                $actual[$label]['status_code'],
                "Status code changed for '{$label}'."
            );

            // The body is a subset check, which is the additive-only contract:
            // new keys are allowed, but nothing the app already reads may be
            // removed, renamed, or change type.
            $violations = [];
            $this->assertContract($shape['body'], $actual[$label]['body'], $label, $violations);

            $this->assertSame(
                [],
                $violations,
                "v1 response contract broken for '{$label}':\n  - " . implode("\n  - ", $violations)
            );
        }

        $this->assertSame(array_keys($expected), array_keys($actual), 'Endpoint case list changed.');
    }


    /**
     * Every leaf in $expected must still exist in $actual with the same shape.
     * Extra keys in $actual are fine - that is what "additive" means.
     */
    private function assertContract(mixed $expected, mixed $actual, string $path, array &$violations): void
    {
        if (is_array($expected)) {
            if (!is_array($actual)) {
                $violations[] = "{$path}: expected a structure, got " . json_encode($actual);

                return;
            }

            foreach ($expected as $key => $child) {
                if (!array_key_exists($key, $actual)) {
                    $violations[] = "{$path}.{$key}: key removed or renamed";

                    continue;
                }

                $this->assertContract($child, $actual[$key], "{$path}.{$key}", $violations);
            }

            return;
        }

        if ($expected !== $actual) {
            $violations[] = "{$path}: was {$expected}, now " . json_encode($actual);
        }
    }

    private function refreshApplicationForCase(?string $actAs): void
    {
        if ($actAs === 'parent') {
            Passport::actingAs($this->parent, [], 'api');
        } elseif ($actAs === 'child') {
            Passport::actingAs($this->child, [], 'api');
        } else {
            app('auth')->forgetGuards();
            app('auth')->setDefaultDriver('web');
        }
    }
}
