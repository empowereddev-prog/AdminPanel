<?php

use App\Http\Controllers\Admin\VideoRequestController;
use App\Http\Controllers\Api\AvtarController;
use App\Http\Controllers\Api\ChildController;
use App\Http\Controllers\Api\FaqController;
use App\Http\Controllers\Api\HomeApiController;
use App\Http\Controllers\Api\KnowledgeBaseController;
use App\Http\Controllers\Api\KnowledgeSessionController;
use App\Http\Controllers\Api\MeetTeamController;
use App\Http\Controllers\Api\PopupLoginController;
use App\Http\Controllers\Api\ProductRecommendationController;
use App\Http\Controllers\Api\UserArticleLikeController;
use App\Http\Controllers\MoodTrackerController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\WebhookController;
use App\Models\Role;
use App\Models\UserArticaleLike;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
  return $request->user();
});

Route::post('webhooks/google', [WebhookController::class, 'google']);
Route::post('webhooks/apple', [WebhookController::class, 'apple']);

Route::post('register', [HomeApiController::class, 'register']);
Route::post('login', [HomeApiController::class, 'login']);
Route::post('forgot-password', [HomeApiController::class, 'forgotPassword']);
Route::get('reset-password/{token}', [HomeApiController::class, 'resetPasswordPage'])->name('reset.password.page');
Route::post('reset-password/{token}', [HomeApiController::class, 'passwordReset'])->name('password-reset');
// Route::post('reset-password', [HomeApiController::class, 'resetPassword']);
Route::post('verify-otp', [HomeApiController::class, 'verifyOtp']);
Route::post('resend-otp', [HomeApiController::class, 'resendOtp']);
Route::post('student-login', [HomeApiController::class, 'studentLogin']);
Route::post('individual-login', [HomeApiController::class, 'individualLogin']);
Route::middleware('auth:api', 'apicheckstatus')->group(function () {
  Route::post('add-child', [ChildController::class, 'addChild']);
  Route::post('edit-child', [ChildController::class, 'editChild']);
  Route::post('logout', [HomeApiController::class, 'logout']);
  Route::post('delete-user', [HomeApiController::class, 'deleteUser']);
  Route::post('delete-child', [ChildController::class, 'deleteChild']);
  Route::post('primary-child', [ChildController::class, 'primaryChild']);

  Route::get('get-profile', [ChildController::class, 'getProfile']);
  Route::post('reset-password', [HomeApiController::class, 'reset']);
  Route::post('avtar-image', [AvtarController::class, 'avtarImage']);
  Route::post('store-avtar', [AvtarController::class, 'storeAvtar']);
  Route::post('get-category', [AvtarController::class, 'videoCategory']);
  Route::post('video-content', [KnowledgeBaseController::class, 'videoContent']);
  Route::post('video-content-quiz', [KnowledgeBaseController::class, 'videoContentQuiz']);
  Route::post('unlock-avtars', [AvtarController::class, 'userUnlockAvatars']);
  Route::post('knowledge-session', [KnowledgeSessionController::class, 'KnowledgeSession']);
  Route::post('user-content-watch-histories', [KnowledgeBaseController::class, 'userContentWatchHistories']);
  Route::post('knowledge-session-details', [KnowledgeSessionController::class, 'KnowledgeSessionDetails']);
  Route::post('video-watch-status', [KnowledgeBaseController::class, 'fetchVideoWatchStatus']);
  Route::post('video-content-details', [KnowledgeBaseController::class, 'videoContentdetails']);
  Route::post('featured-content', [KnowledgeSessionController::class, 'featuredSession']);

  Route::controller(UserArticleLikeController::class)->group(function () {
    Route::post('article-like', 'storeLikedArticle');
  });

  Route::post('quiz-category', [QuizController::class, 'quizCategory']);
  Route::post('quiz', [QuizController::class, 'quiz']);
  Route::post('quiz-question', [QuizController::class, 'quizQuestion']);
  Route::post('user-attempt-questions', [QuizController::class, 'userAttemptQuiz']);

  Route::post('parent-dashboard', [ChildController::class, 'parentDashboard']);

  Route::post('child-percentage', [ChildController::class, 'updateBatteryAndLoyalty']);

  Route::post('video-content-for-parent', [KnowledgeBaseController::class, 'videoContentforparent']);
  Route::post('knowledge-session-for-parent', [KnowledgeSessionController::class, 'KnowledgeSessionforParent']);
  Route::post('update-parent-profile', [HomeApiController::class, 'updateParentProfile']);
  Route::post('get-child-profile', [HomeApiController::class, 'getChildProfile']);
  Route::post('featured-content-for-parent', [KnowledgeSessionController::class, 'featuredSessionforParent']);
  Route::post('update-profile-image', [HomeApiController::class, 'updateProfileImage']);
  Route::post('update-teacher-profile', [HomeApiController::class, 'updateTeacherProfile']);
  Route::post('video-content-for-child', [KnowledgeBaseController::class, 'videoContentforchild']);

  Route::post('subscription', [ChildController::class, 'subscription']);
  Route::post('quiz-completion', [QuizController::class, 'quizCompletionContent']);
  // Route::delete('delete-mood/{id}',[MoodTrackerController::class, 'destroy'])->name('delete-mood');
  Route::post('get-all-mood', [MoodTrackerController::class, 'getAllMood']);
  Route::post('store-child-mood', [MoodTrackerController::class, 'storeChildMood']);
  Route::post('get-child-mood', [MoodTrackerController::class, 'getChildMood']);
  Route::post('activity-list', [MoodTrackerController::class, 'activity']);
  Route::post('store-activity', [MoodTrackerController::class, 'storeChildActivity']);
  Route::post('get-suggested-activity', [MoodTrackerController::class, 'getSuggestedActivity']);

  Route::post('mood-tracker', [MoodTrackerController::class, 'moodTracker']);

  Route::post('child-support', [MoodTrackerController::class, 'childSupport']);
  Route::post('notification-list', [NotificationController::class, 'notificationList']);
  Route::post('manage-notification', [NotificationController::class, 'manageNotification']);
  Route::post('contact-support', [NotificationController::class, 'sendMessage']);
  Route::post('send-notification', [NotificationController::class, 'sendNotification']);
  Route::post('mark-as-read', [NotificationController::class, 'markAsRead']);
  Route::post('delete-notification', [NotificationController::class, 'deleteNotification']);
  Route::post('store-liked-content', [MoodTrackerController::class, 'storeLikedVideoContent']);

  Route::controller(ProductRecommendationController::class)->group(function () {
    Route::post('products', 'index');
  });
  Route::controller(MeetTeamController::class)->group(function () {
    Route::post('meet-team', 'index');
  });
  Route::get('faq', [FaqController::class, 'index']);

  Route::controller(PopupLoginController::class)->group(function () {
    Route::get('popup', 'index');
    Route::post('popup-store', 'store');
    Route::post('test-battery', 'check');
  });

  Route::post('request-video', [VideoRequestController::class, 'requestVideo']);
});
Route::get('get-child-support', [FaqController::class, 'getSupport']);
