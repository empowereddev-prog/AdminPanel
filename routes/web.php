<?php

use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\AvtarController;
use App\Http\Controllers\Admin\BannerImageController;
use App\Http\Controllers\Admin\BatterySettingController;
use App\Http\Controllers\Admin\ColorManagamentController;
use App\Http\Controllers\Admin\ContactUsController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EmailTemplateController;
use App\Http\Controllers\Admin\FaqController;
use App\Http\Controllers\Admin\FeaturesController;
use App\Http\Controllers\Admin\GeneralSettingsController;
use App\Http\Controllers\Admin\KnowleadgeSessionController;
use App\Http\Controllers\Admin\LoginController;
use App\Http\Controllers\Admin\MeetTeamController;
use App\Http\Controllers\Admin\NewQuizController;
use App\Http\Controllers\Admin\NotificationTemplateController;
use App\Http\Controllers\Admin\PaymentManagementController;
use App\Http\Controllers\Admin\PermissionSubAdminController;
use App\Http\Controllers\Admin\PopupContentController;
use App\Http\Controllers\Admin\ProductRecommendationController;
use App\Http\Controllers\Admin\QuizController as AdminQuizController;
use App\Http\Controllers\Admin\SchoolController;
use App\Http\Controllers\Admin\SchoolUserDetailController;
use App\Http\Controllers\Admin\ShowUserLikeDislikeController;
use App\Http\Controllers\Admin\StaticContentController;
use App\Http\Controllers\Admin\SubAdminController;
use App\Http\Controllers\Admin\TestmonialController;
use App\Http\Controllers\Admin\UsersController;
use App\Http\Controllers\Admin\VideoMoreController;
use App\Http\Controllers\Admin\VideoRequestController;
use App\Http\Controllers\AdminNotificationController;
use App\Http\Controllers\AgeGroupController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\KnowledgeBaseController;
use App\Http\Controllers\MoodTrackerController;
use App\Http\Controllers\QuizCategoryController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\VideoUploadChildController;
use Illuminate\Support\Facades\Route;


// Public routes
// Route::get('home', [HomeController::class, 'homepage'])->name('homepage');
// Route::get('all-products', [HomeController::class, 'products'])->name('all-products');

// Route::post('contact', [ContactUsController::class, 'store'])->middleware('throttle:contact_form');
// Route::get('about-us', [HomeController::class, 'aboutUs']);
// Route::get('faq', [HomeController::class, 'faq'])->name('faq');
// Route::get('/team', [HomeController::class, 'termCondition'])->name('terms');
// Route::get('privacy-policy', [HomeController::class, 'privacyPolicy'])->name('privacy-policy');

// Admin routes
Route::middleware('PreventLoginPageAccess')->group(function () {
    Route::get('/', [LoginController::class, 'loginPage'])->name('login');
});
// Route::post('login', [LoginController::class, 'adminLogin']);
// Route::get('otp-verification/{email?}', [LoginController::class, 'otpVerification'])->name('otp_verification');
// Route::post('verify-otp/{email}', [LoginController::class, 'verifyOtp'])->name('admin.verifyOtp');
// Route::get('admin-resend-otp/{email}', [LoginController::class, 'adminResendOtp'])->name('admin.resendOtp');

Route::middleware(['nocache', 'redirIfAuthBack'])->group(function () {
    Route::post('login', [LoginController::class, 'adminLogin']);
    Route::get('otp-verification/{email?}', [LoginController::class, 'otpVerification'])->name('otp_verification');
    Route::post('verify-otp/{email}', [LoginController::class, 'verifyOtp'])->name('admin.verifyOtp');
    Route::get('admin-resend-otp/{email}', [LoginController::class, 'adminResendOtp'])->name('admin.resendOtp');
});
Route::get('delete-account', [LoginController::class, 'showFormDelele'])->name('delete-account-form');
Route::post('delete-account', [LoginController::class, 'deleteAccount'])->name('delete-account');

Route::get('forgot-password', [LoginController::class, 'forgotPage'])->name('forgot-password');
Route::post('reset-password', [LoginController::class, 'resetPasswordLink'])->name('reset-password');
Route::get('reset-password/{token}', [LoginController::class, 'resetPasswordPage'])->name('reset.password.page');
Route::post('reset-password/{token}', [LoginController::class, 'passwordReset'])->name('password-reset');
Route::get('reset/{token}', [LoginController::class, 'reset'])->name('reset');
Route::get('/temp-login', function () {
    return view('temp.temp-login');
})->name('temp-login');
Route::get('/verify/{user_id}/{email}', [UsersController::class, 'verifyUser'])->name('user.verify');
Route::get('/verify-signup', function () {
    return view('verifySignUp');
})->name('verifySignUp');
Route::get('/success', function () {
    return view('admin.success');
})->name('success');

// Route::get('/twilio-test', function () {
//     return ___sms_sender(
//         'Your OTP is 654321',
//         '+917017159715',
//         'english'
//     );
// });


// });
Route::get('/reset-password-message', function () {
    return view('auth.reset-password-msg');
});
// Route::get('/terms-conditions', function () {
//     return view('admin.staticpages.termsCondition');
// });
Route::get('/terms-conditions', [StaticContentController::class, 'termCondition'])->name('terms');
Route::get('/help-support', [StaticContentController::class, 'helpSupport'])->name('helpSupport');
Route::get('/meet-the-team', [StaticContentController::class, 'meetTheTeam'])->name('meetTheTeam');
Route::get('/privacy-policy', [StaticContentController::class, 'privacyPolicy'])->name('privacyPolicy');
Route::get('/about-us', [StaticContentController::class, 'aboutUs'])->name('about-us');
Route::get('/content_protection', [StaticContentController::class, 'contentProtection']);
Route::get('/content_disclaimer', [StaticContentController::class, 'contentDisclaimer']);
Route::middleware('auth:admin', 'checkActive')->group(function () {
    Route::get('logout', [LoginController::class, 'logout'])->name('logout');
    Route::get('profile', [DashboardController::class, 'profile'])->name('profile');
    Route::post('update-profile', [DashboardController::class, 'updateProfile'])->name('update.profile');
    Route::get('dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');
    Route::get('dashboard/data', [DashboardController::class, 'getData'])->name('admin.active_plan');
    Route::get('ordered-plan-details/{id}', [DashboardController::class, 'getActivePlanDetails'])->name('admin.active_plan_details');
    Route::resource('banners', BannerImageController::class);
    Route::post('banner/toggle-status/{id}', [BannerImageController::class, 'toggleStatus'])->name('admin.banner.status');
    Route::resource('testimonial', TestmonialController::class);
    Route::get('change-password', [DashboardController::class, 'changePassword'])->name('change-password');
    Route::post('update-password', [DashboardController::class, 'updatePassword'])->name('update-password');
    Route::resource('static-content', StaticContentController::class);
    Route::resource('faq', FaqController::class);
    Route::resource('features', FeaturesController::class);
    Route::post('feature/toggle-status/{key}', [FeaturesController::class, 'toggleStatus'])->name('admin.feature.status');
    Route::resource('email-template', EmailTemplateController::class);
    Route::controller(NotificationTemplateController::class)->group(function () {
        Route::get('notification-template', 'index')->name('notification-template.index');
        Route::get('notification-template/{id}/edit', 'edit')->name('notification-template.edit');
        Route::put('notification-template/{id}', 'update')->name('notification-template.update');
    });
    // Route::get('review-index', [ReviewRatingController::class, 'indexClientReview'])->name('review-index.indexClientReview');
    // Route::get('review_rating/{id}/edit', [ReviewRatingController::class, 'edit'])->name('review_rating.edit');
    // Route::get('review_rating/{id}/destroy', [ReviewRatingController::class, 'destroy'])->name('review_rating.destroy');
    // Route::post('change-review-status/{id}/status', [ReviewRatingController::class, 'status'])->name('change-review-status.status');
    // Route::post('change-review-status/{id}/status', [ReviewRatingController::class, 'status'])->name('change-review-status.status');
    // Route::put('update-review-rating/{id}', [ReviewRatingController::class, 'update'])->name('review_rating.update');

    Route::get('user', [UsersController::class, 'index'])->name('user.index');
    Route::get('user/data', [UsersController::class, 'getUsers'])->name('user.data');
    Route::get('user/create', [UsersController::class, 'create'])->name('user.create');
    Route::post('store-user', [UsersController::class, 'store'])->name('user.store');
    Route::get('user/{id}/edit', [UsersController::class, 'edit'])->name('user.edit');
    Route::put('update-user/{id}', [UsersController::class, 'update'])->name('user.update');
    Route::delete('delete-user/{id}', [UsersController::class, 'destroy'])->name('user.delete');
    Route::delete('delete-child/{id}', [UsersController::class, 'deleteChild'])->name('child.delete');
    //age group crud apis
    Route::get('age-group', [AgeGroupController::class, 'index'])->name('age-group.index');
    Route::get('age-group/data', [AgeGroupController::class, 'getAgeGroup'])->name('age-group.data');
    Route::get('age-group/create', [AgeGroupController::class, 'create'])->name('age-group.create');
    Route::post('age-group-store', [AgeGroupController::class, 'store'])->name('age-group.store');
    Route::get('age-group/{id}/edit', [AgeGroupController::class, 'edit'])->name('age-group.edit');
    Route::put('update-age-group/{id}', [AgeGroupController::class, 'update'])->name('age-group.update');
    Route::delete('delete-age-group/{id}', [AgeGroupController::class, 'destroy'])->name('age-group.delete');
    Route::get('ageGroup-range', [AgeGroupController::class, 'ageRange'])->name('age-group.ageRange');

    Route::controller(ColorManagamentController::class)->group(function () {
        Route::get('color', 'index')->name('color.index');
        Route::get('color/create', 'create')->name('color.create');
        Route::post('color/store', 'store')->name('color.store');
        Route::get('color/{id}/edit', 'edit')->name('color.edit');
        Route::post('color/{id}/update', 'update')->name('color.update');
        Route::delete('color/delete/{id}', 'delete')->name('color.delete');
    });

    //category crud apis
    Route::get('category', [CategoryController::class, 'index'])->name('category.index');
    Route::get('category/data', [CategoryController::class, 'getCategory'])->name('category.data');
    Route::get('category/create', [CategoryController::class, 'create'])->name('category.create');
    Route::post('category-store', [CategoryController::class, 'store'])->name('category.store');
    Route::get('category/{id}/edit', [CategoryController::class, 'edit'])->name('category.edit');
    Route::put('update-category/{id}', [CategoryController::class, 'update'])->name('category.update');
    Route::delete('delete-category/{id}', [CategoryController::class, 'destroy'])->name('category.delete');
    Route::get('categories', [CategoryController::class, 'categories'])->name('category.categories');
    Route::post('categories/update-priority', [CategoryController::class, 'updatePriority'])->name('categories.updatePriority');

    //category crud apis
    Route::get('knowledge-base', [KnowledgeBaseController::class, 'index'])->name('knowledge-base.index');
    Route::get('knowledge-base/data', [KnowledgeBaseController::class, 'getknowledge'])->name('knowledge-base.data');
    Route::get('knowledge-base/create', [KnowledgeBaseController::class, 'create'])->name('knowledge-base.create');
    Route::post('knowledge-base-store', [KnowledgeBaseController::class, 'store'])->name('knowledge-base.store');
    Route::get('knowledge-base/{id}/edit', [KnowledgeBaseController::class, 'edit'])->name('knowledge-base.edit');
    Route::put('update-knowledge-base/{id}', [KnowledgeBaseController::class, 'update'])->name('knowledge-base.update');
    Route::delete('delete-knowledge-base/{id}', [KnowledgeBaseController::class, 'destroy'])->name('knowledge-base.delete');
    // knowledgeSession
    Route::controller(ShowUserLikeDislikeController::class)->group(function () {
        Route::get('user-like/{video_id}', 'videoUsersIndex')->name('user-like.index');
        Route::get('article-user-like/{article_id}', 'articleUsersIndex')->name('article-user-like.index');
    });


    Route::get('knowledge-base-child', [VideoUploadChildController::class, 'index'])->name('knowledge-base-child.index');
    Route::get('knowledge-base-child/data', [VideoUploadChildController::class, 'getknowledge'])->name('knowledge-base-child.data');
    Route::get('knowledge-base-child/create', [VideoUploadChildController::class, 'create'])->name('knowledge-base-child.create');
    Route::post('knowledge-base-child-store', [VideoUploadChildController::class, 'store'])->name('knowledge-base-child.store');
    Route::get('knowledge-base-child/{id}/edit', [VideoUploadChildController::class, 'edit'])->name('knowledge-base-child.edit');
    Route::put('update-knowledge-base-child/{id}', [VideoUploadChildController::class, 'update'])->name('knowledge-base-child.update');
    Route::delete('delete-knowledge-base-child/{id}', [VideoUploadChildController::class, 'destroy'])->name('knowledge-base-child.delete');
    // knowledgeSession

    // video more
    Route::resource('video-other', VideoMoreController::class);
    Route::get('video-other-data', [VideoMoreController::class, 'getVideoData'])->name('video-other.data');

    Route::get('knowledge-session', [KnowleadgeSessionController::class, 'index'])->name('knowledgeSession.index');
    Route::get('knowledge-session/data', [KnowleadgeSessionController::class, 'getknowledge'])->name('knowledgeSession.data');
    Route::get('knowledge-session/create', [KnowleadgeSessionController::class, 'create'])->name('knowledgeSession.create');
    Route::post('knowledge-session-store', [KnowleadgeSessionController::class, 'store'])->name('knowledgeSession.store');
    Route::get('knowledge-session/{id}/edit', [KnowleadgeSessionController::class, 'edit'])->name('knowledgeSession.edit');
    Route::put('update-knowledge-session/{id}', [KnowleadgeSessionController::class, 'update'])->name('knowledgeSession.update');
    Route::delete('delete-knowledge-session/{id}', [KnowleadgeSessionController::class, 'destroy'])->name('knowledgeSession.delete');
    Route::get('article-model', [KnowleadgeSessionController::class, 'articaleUsersModel'])->name('article-model');

    //avtar image
    Route::get('avtar', [AvtarController::class, 'avtarType'])->name('avtar.type_index');
    Route::get('avtar/typedata', [AvtarController::class, 'getavtarType'])->name('avtar.type_data');
    Route::get('avtar/{type}', [AvtarController::class, 'index'])->name('avtar.index');
    Route::post('avtar/{type}/data', [AvtarController::class, 'getavtar'])->name('avtar.data');
    Route::get('avtar/{type}/create', [AvtarController::class, 'create'])->name('avtar.create');
    Route::post('avtar/{type}/store', [AvtarController::class, 'store'])->name('avtar.store');
    Route::get('avtar/{type}/{id}/edit', [AvtarController::class, 'edit'])->name('avtar.edit');
    Route::put('avtar/{type}/{id}/update', [AvtarController::class, 'update'])->name('avtar.update');
    Route::delete('avtar/{type}/{id}/delete', [AvtarController::class, 'destroy'])->name('avtar.delete');
    Route::post('avtar/change-status/{type}', [AvtarController::class, 'changeStatus'])->name('avtar.changeStatus');

    // School Management
    Route::get('school', [SchoolController::class, 'index'])->name('school.index');
    Route::get('school/data', [SchoolController::class, 'getSchools'])->name('school.data');
    Route::get('school/create', [SchoolController::class, 'create'])->name('school.create');
    Route::post('store-school', [SchoolController::class, 'store'])->name('school.store');
    Route::get('school/{id}/edit', [SchoolController::class, 'edit'])->name('school.edit');
    Route::put('update-school/{id}', [SchoolController::class, 'update'])->name('school.update');
    Route::delete('delete-school/{id}', [SchoolController::class, 'destroy'])->name('school.delete');
    Route::get('view-school-details/{id}', [SchoolController::class, 'show'])->name('school.show');
    Route::get(
        'school/{school}/export-users',
        [SchoolController::class, 'exportSchoolUsers']
    )->name('school.export.users');
    Route::delete('delete-school-user/{id}', [SchoolController::class, 'destroySchoolUser'])->name('students.delete');
    Route::get('settings', [GeneralSettingsController::class, 'editSystemSetting'])->name('settings.edit');
    Route::PUT('settings/update', [GeneralSettingsController::class, 'updateSystemSetting'])->name('settings.update');
    Route::get('/download-sample-excel', [SchoolController::class, 'downloadSampleExcel'])->name('download.sample.excel');
    Route::get('/download-sample-staff-excel', [SchoolController::class, 'downloadSampleStaffExcel'])->name('download.sample.staff.excel');
    Route::get('school/moods-overview', [SchoolController::class, 'moodsOverview'])->name('school.moods.overview');

    Route::controller(SchoolUserDetailController::class)->group(function () {
        Route::get('school/user/child/{user_id}/detail/', 'index')->name('school-user-child-detail');
        Route::get('school/user/{user_id}/children-progress', 'childrenProgress')->name('school-user-children-progress');
        Route::get('school/{id}/children-progress', 'allChildrenProgress')
            ->name('school.children.progress');
    });

    // Route::get('payment-histories', [PaymentManagementController::class, 'historyIndex'])->name('payment.history_index');
    // Route::get('payment-histories/download/{id}', [PaymentManagementController::class, 'historyDownload'])->name('payment.history_download');
    // Route::get('payment-histories/download', [PaymentManagementController::class, 'historyAllDownload'])->name('payment.all_history_download');
    // Route::get('payment-histories/view/{id}', [PaymentManagementController::class, 'historyView'])->name('payment.history_view');
    // role and permission
    // Route::get('role-permission', [SubAdminController::class, 'rolePermission'])->name('role.rolePermission');
    // Route::get('add-role-permission', [SubAdminController::class, 'addRolePermission'])->name('rolePermission.create');
    // Route::post('add-role-permission', [SubAdminController::class, 'storeRolePermission'])->name('rolePermission.store');
    // Route::get('edit-role-permission/{id}', [SubAdminController::class, 'editRolePermission'])->name('rolePermission.edit');
    // Route::put('edit-role-permission/{id}', [SubAdminController::class, 'updateRolePermission'])->name('rolePermission.update');
    // Route::delete('delete-role-permission/{id}', [SubAdminController::class, 'deleteRolePermission'])->name('rolePermission.destroy');
    // Route::post('change-user-status', [SubAdminController::class, 'changeUserStatus']);

    Route::controller(PermissionSubAdminController::class)->group(function () {
        Route::get('assign-permission', 'index')->name('assign-permission.index');
        Route::get('assign-permission/create', 'create')->name('assign-permission.create');
        Route::post('assign-permission/store', 'store')->name('assign-permission.store');
        Route::get('assign-permission/{id}/edit', 'edit')->name('assign-permission.edit');
        Route::put('assign-permission/{id}', 'update')->name('assign-permission.update');
        Route::delete('delete-assign-permission/{id}', 'delete')->name('assign-permission.destroy');
    });


    Route::resource('contact-us', ContactUsController::class);
    Route::post('contact-us/{id}', [ContactUsController::class, 'destroy']);

    // System Log
    Route::get('system-log', [App\Http\Controllers\Admin\SystemLogController::class, 'index'])->name('system-log.index');
    Route::get('system-log/data', [App\Http\Controllers\Admin\SystemLogController::class, 'getUsers'])->name('systemlog.data');
    Route::post('ckeditor-upload', [StaticContentController::class, 'upload'])->name('ckeditor.upload');

    Route::get('/build-upload', [GeneralSettingsController::class, 'uploadbuild']);
    Route::put('upload-file/update', [GeneralSettingsController::class, 'updateBuild']);
    //Quiz Management

    Route::get('quiz-category', [QuizCategoryController::class, 'index'])->name('quizCategory.index');
    Route::get('quiz-category/data', [QuizCategoryController::class, 'getCategory'])->name('quizCategory.data');
    Route::get('quiz-category/create', [QuizCategoryController::class, 'create'])->name('quizCategory.create');
    Route::post('quiz-category-store', [QuizCategoryController::class, 'store'])->name('quizCategory.store');
    Route::get('quiz-category/{id}/edit', [QuizCategoryController::class, 'edit'])->name('quizCategory.edit');
    Route::put('quiz-update-category/{id}', [QuizCategoryController::class, 'update'])->name('quizCategory.update');
    Route::delete('quiz-delete-category/{id}', [QuizCategoryController::class, 'destroy'])->name('quizCategory.delete');
    Route::post('quiz-category/update-priority', [QuizCategoryController::class, 'updatePriority'])->name('quiz-category.updatePriority');

    Route::get('quiz-questions/{id}/{category_id}', [QuizController::class, 'index'])->name('quiz-questions.index');
    Route::get('quiz/data/{id}/{category_id}', [QuizController::class, 'questionList'])->name('quiz-questions.data');
    Route::get('add-questions/{id}/{category_id}', [QuizController::class, 'addEmpathyQuestion'])->name('quiz-questions.create');
    Route::post('add-questions/{id}/{category_id}', [QuizController::class, 'storeEmpathyQuestion'])->name('quiz-questions.store');
    Route::get('edit-question/{id}/{category_id}', [QuizController::class, 'editEmpathyQuestion'])->name('quiz-questions.edit');
    Route::put('edit-question/{id}/{category_id}', [QuizController::class, 'updateEmpathyQuestion'])->name('quiz-questions.update');
    Route::delete('delete-question/{id}', [QuizController::class, 'deleteEmpathyQuestion'])->name('quiz-questions.delete');
    Route::get('question-options/{id}/{category_id}/{quiz_id}', [QuizController::class, 'questionOptionList'])->name('question-options');
    Route::get('add-question-options/{id}/{category_id}/{quiz_id}', [QuizController::class, 'addQuestionOptions'])->name('question-options.create');
    Route::post('add-question-options/{category_id}/{quiz_id}', [QuizController::class, 'storeQuestionOptions'])->name('question-options.store');
    Route::delete('delete-question-options/{id}', [QuizController::class, 'deleteQuestionOption'])->name('question-options.delete');
    Route::get('edit-question-options/{id}/{category_id}/{quiz_id}', [QuizController::class, 'editQuestionOption'])->name('question-options.edit');
    Route::put('update-question-options/{id}/{category_id}/{quiz_id}', [QuizController::class, 'updateQuestionOption'])->name('question-options.update');
    Route::get('count-question-options/{id}', [QuizController::class, 'countQuestionOptions']);
    Route::get('users-attempt-quizzes', [QuizController::class, 'userAttemptQuizList'])->name('users-attempt-quizzes');
    Route::get('users-attempt-quiz-details/{user_id}', [QuizController::class, 'userAttemptQuizDetails'])->name('users-attempt-quizzes.detail');
    Route::get('user/{id}/subscription', [UsersController::class, 'subscription'])->name('user.subscription');
    Route::controller(NewQuizController::class)->group(function () {
        Route::get('quiz-index/{id}', 'index')->name('quiz.index');
        Route::get('quiz/data/{id}', 'questionList')->name('quiz.data');
        Route::get('quiz-create/{id}', 'create')->name('quiz.create');
        Route::post('quiz-store/{id}', 'store')->name('quiz.store');
        Route::get('quiz-edit/{id}', 'edit')->name('quiz.edit');
        Route::post('quiz-update/{id}', 'update')->name('quiz.update');
        Route::delete('quiz-delete/{id}', 'delete')->name('quiz.delete');
    });

    // Payment Management
    Route::get('payment-histories', [PaymentManagementController::class, 'historyIndex'])->name('payment.history_index');
    Route::get('payment-histories/download/{id}', [PaymentManagementController::class, 'historyDownload'])->name('payment.history_download');
    Route::get('payment-histories/download', [PaymentManagementController::class, 'historyAllDownload'])->name('payment.all_history_download');
    Route::get('payment-histories/view/{id}', [PaymentManagementController::class, 'historyView'])->name('payment.history_view');

    //Mood Tracker
    Route::get('mood', [MoodTrackerController::class, 'index'])->name('mood-index');
    Route::get('mood/data', [MoodTrackerController::class, 'getList'])->name('mood-list');
    Route::get('add-mood', [MoodTrackerController::class, 'create'])->name('add-mood');
    Route::post('store-mood', [MoodTrackerController::class, 'store'])->name('store-mood');
    Route::get('edit-mood/{id}', [MoodTrackerController::class, 'edit'])->name('edit-mood');
    Route::put('update-mood/{id}', [MoodTrackerController::class, 'update'])->name('update-mood');
    Route::delete('delete-mood/{id}', [MoodTrackerController::class, 'destroy'])->name('delete-mood');
    //Activity
    Route::get('activity/{id}', [MoodTrackerController::class, 'activityIndex'])->name('activity.index');
    Route::get('activity/data/{id}', [MoodTrackerController::class, 'activityList'])->name('activity.data');
    Route::get('add-activity/{id}', [MoodTrackerController::class, 'addActivity'])->name('activity.create');
    Route::post('store-activity/{id}', [MoodTrackerController::class, 'storeActivity'])->name('activity.store');
    Route::get('edit-activity/{id}', [MoodTrackerController::class, 'editActivity'])->name('activity.edit');
    Route::put('update-activity/{id}', [MoodTrackerController::class, 'updateActivity'])->name('activity.update');
    Route::delete('delete-activity/{id}', [MoodTrackerController::class, 'deleteActivity'])->name('activity.delete');
    // Child Mood
    Route::get('child-mood-tracker', [MoodTrackerController::class, 'childMoodTrackerList'])->name('child-mood-tracker');
    Route::get('child-mood-tracker-details/{user_id}', [MoodTrackerController::class, 'childMoodTrackerDetails'])->name('child-mood-tracker.detail');
    //Performed Activity by child
    Route::get('users-performed-activity', [MoodTrackerController::class, 'userPerformedActivityist'])->name('users-performed-activity');
    Route::get('users-performed-activity-details/{user_id}', [MoodTrackerController::class, 'userPerformedActivityDetails'])->name('users-performed-activity.detail');

    Route::resource('admin/notifications', AdminNotificationController::class)->middleware(['auth', 'isAdmin']);
    // Route::get('video-users/{type}/{videoId}', [KnowledgeBaseController::class, 'showUsers'])->name('video.users');
    Route::get('video-users-popup', [KnowledgeBaseController::class, 'videoUsersPopup'])->name('video.users.popup');
    Route::get('exports/{key}', [GeneralSettingsController::class, 'reportGenrate']);
    Route::post('reportcreate/{key}', [GeneralSettingsController::class, 'downloadExcelFile']);

    Route::controller(ProductRecommendationController::class)->group(function () {
        Route::get('products/', 'index')->name('product.index');
        Route::get('product/create', 'create')->name('product.create');
        Route::post('product/store', 'store')->name('product.store');
        Route::get('product/{id}/show', 'show')->name('product.show');
        Route::get('product/{id}/edit', 'edit')->name('product.edit');
        Route::put('product/{id}', 'update')->name('product.update');
        Route::delete('product/{id}', 'destroy')->name('product.destroy');
        Route::post('product/update-priority',  'updatePriority')->name('product.updatePriority');
    });

    Route::controller(MeetTeamController::class)->group(function () {
        Route::get('meet-team/', 'index')->name('meet-team.index');
        Route::get('meet-team/create', 'create')->name('meet-team.create');
        Route::post('meet-team/store', 'store')->name('meet-team.store');
        Route::get('meet-team/{id}/show', 'show')->name('meet-team.show');
        Route::get('meet-team/{id}/edit', 'edit')->name('meet-team.edit');
        Route::put('meet-team/{id}', 'update')->name('meet-team.update');
        Route::delete('meet-team/{id}', 'destroy')->name('meet-team.destroy');
        Route::post('meet-team/update-priority',  'updatePriority')->name('meet-team.updatePriority');
    });

    Route::controller(AuditLogController::class)->group(function () {
        Route::get('audit-log', 'index')->name('audit-log.index');
    });

    Route::controller(PopupContentController::class)->group(function () {
        Route::get('popup-content', 'index')->name('popup-content.index');
        Route::get('popup-content/create', 'create')->name('popup-content.create');
        Route::post('popup-content', 'store')->name('popup-content.store');
        Route::get('popup-content/{id}/edit', 'edit')->name('popup-content.edit');
        Route::put('popup-content/{id}', 'update')->name('popup-content.update');
        Route::delete('popup-content/{id}', 'destroy')->name('popup-content.destroy');
    });

    Route::controller(BatterySettingController::class)->group(function () {
        Route::get('battery-setting', 'index')->name('battery-setting.index');
        Route::post('battery-setting/update', 'update')->name('battery-setting.update');
    });

    Route::get('/php-limits', function () {
        return [
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
        ];
    });

    Route::resource('video-requests', VideoRequestController::class);
    Route::post('video-requests/{id}', [VideoRequestController::class, 'destroy']);

});
