<?php

namespace Tests\Feature\Api;

use App\Models\DeviceToken;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Passport\Passport;
use Tests\TestCase;

/**
 * The app's delete-account dialog tells a parent that "if your child's account
 * is synced to your account, your child's profile information and data will
 * also be deleted". These tests pin that the API does what the copy says.
 * Uses DatabaseTransactions so it is safe to run against a shared database.
 */
class DeleteAccountCascadeTest extends TestCase
{
    use DatabaseTransactions;

    private function makeUser(array $attrs = []): User
    {
        return User::create(array_merge([
            'name' => 'Test User',
            'email' => 'u' . uniqid() . '@example.test',
            'password' => bcrypt('OldPass1@'),
            'user_type' => 'parent',
            'user_role_id' => 3,
            'status' => 'active',
            'language' => 'english',
        ], $attrs));
    }

    private function makeChild(User $parent): User
    {
        return $this->makeUser([
            'user_type' => 'child',
            'user_role_id' => 4,
            'parent_id' => $parent->id,
        ]);
    }

    private function addDeviceToken(User $user): void
    {
        DeviceToken::create([
            'user_id' => $user->id,
            'token' => 'tok' . uniqid(),
        ]);
    }

    public function test_deleting_a_parent_also_deletes_linked_children(): void
    {
        $parent = $this->makeUser();
        $child = $this->makeChild($parent);
        $otherChild = $this->makeChild($this->makeUser());
        $this->addDeviceToken($child);

        Passport::actingAs($parent, [], 'api');
        $this->postJson('/api/delete-user')->assertOk();

        $this->assertSoftDeleted('users', ['id' => $parent->id]);
        $this->assertSoftDeleted('users', ['id' => $child->id]);
        $this->assertSame(0, DeviceToken::where('user_id', $child->id)->count());
        // Another family's child is untouched.
        $this->assertNull(User::find($otherChild->id)->deleted_at);
    }

    public function test_school_imported_students_without_a_parent_are_untouched(): void
    {
        $parent = $this->makeUser();
        $student = $this->makeUser([
            'user_type' => 'child',
            'user_role_id' => 4,
            'parent_id' => null,
        ]);

        Passport::actingAs($parent, [], 'api');
        $this->postJson('/api/delete-user')->assertOk();

        $this->assertNull(User::find($student->id)->deleted_at);
    }

    public function test_deleting_a_child_does_not_delete_the_parent(): void
    {
        $parent = $this->makeUser();
        $child = $this->makeChild($parent);

        Passport::actingAs($parent, [], 'api');
        $this->postJson('/api/delete-child', ['child_id' => $child->id, 'user_id' => $child->id]);

        $this->assertNull(User::find($parent->id)->deleted_at);
    }
}
