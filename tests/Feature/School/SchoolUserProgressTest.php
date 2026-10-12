<?php

namespace Tests\Feature\School;

use App\Models\{Category, PermissionUser, School, User, UserContentWatchHistory, VideoContent};
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class SchoolUserProgressTest extends TestCase
{
    use DatabaseTransactions;

    public function test_school_details_show_selected_user_progress_for_child_parent_and_teacher(): void
    {
        $admin = User::factory()->create(['user_role_id' => 1, 'status' => 'active']);
        PermissionUser::create(['user_id' => $admin->id, 'menu_id' => 2, 'is_view' => 'yes', 'is_modify' => 'yes']);
        $school = School::factory()->create();
        $category = Category::create(['category_name' => 'Progress test category', 'status' => 'active']);
        foreach (['child' => 4, 'parent' => 3, 'teacher' => 5] as $type => $role) {
            $user = User::factory()->create(['user_role_id' => $role, 'user_type' => $type,
                'school_id' => $school->id, 'parent_id' => null, 'status' => 'active']);
            $video = VideoContent::create(['category_id' => $category->id, 'title' => 'Progress test',
                'description' => 'Test', 'user_type' => $type === 'parent' ? 'parent' : 'child',
                'video_duration' => '10:00', 'status' => 'active']);
            UserContentWatchHistory::create(['child_id' => $user->id, 'video_content_id' => $video->id,
                'last_watched_duration' => '05:00', 'total_video_duration' => '10:00', 'is_completed' => 'no']);
            $response = $this->actingAs($admin, 'admin')->get(route('school-user-child-detail', $user->id));
            $response->assertOk()->assertSee('Video Progress by Category')->assertDontSee('Quiz Progress by Category')
                ->assertSee('Progress test category');
            $response->assertViewHas('data', fn ($data) => $data->categoryProgress[$category->id]['percentage'] > 0);
            $video->delete();
        }
    }
}
