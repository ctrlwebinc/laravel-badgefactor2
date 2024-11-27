<?php

use Ctrlweb\BadgeFactor2\Http\Controllers\Api\BadgePageController;
use Ctrlweb\BadgeFactor2\Http\Controllers\Api\Badgr\AssertionController;
use Ctrlweb\BadgeFactor2\Http\Controllers\Api\Badgr\BackpackAssertionController;
use Ctrlweb\BadgeFactor2\Http\Controllers\Api\Badgr\BadgeController;
use Ctrlweb\BadgeFactor2\Http\Controllers\Api\Badgr\IssuerController;
use Ctrlweb\BadgeFactor2\Http\Controllers\Api\CourseController;
use Ctrlweb\BadgeFactor2\Http\Controllers\Api\CourseGroupCategoryController;
use Ctrlweb\BadgeFactor2\Http\Controllers\Api\CourseGroupController;
use Ctrlweb\BadgeFactor2\Http\Controllers\Api\SettingsController;
use Ctrlweb\BadgeFactor2\Http\Controllers\BadgeFactor2Controller;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::group([
    'prefix'     => 'api/{locale}',
    'middleware' => ['api', 'locale'],
    'namespace'  => 'Ctrlweb\BadgeFactor2\Http\Controllers\Api',
    'domain'     => config('badgefactor2.domain', null),
], function () {
    // Issuers.
    Route::apiResource('issuers', IssuerController::class)->only(['index', 'show']);
    Route::get('issuers-count', [IssuerController::class, 'count']);

    // Badges.
    Route::get('badges/{entityId}', [BadgeController::class, 'show']);
    Route::get('badges', [BadgeController::class, 'index']);
    Route::get('badges/{entityId}/validate-access', [BadgeController::class, 'validateAccess']);

    // Badge pages.
    Route::apiResource('badge-pages', BadgePageController::class)->only(['index', 'show']);
    Route::get('badge-pages/{entityId}/issued', [BadgePageController::class, 'showIssued']);
    Route::get('badge-pages-by-course-group/{courseGroup}', [BadgePageController::class, 'badgePageByCourseGroup']);

    // Course Groups.
    Route::apiResource('course-groups', CourseGroupController::class)
    ->only(['index', 'show']);
    Route::get('course-groups-new', [CourseGroupController::class, 'new_index']);

    // Course Group Categories.
    Route::get('course-group-categories', [CourseGroupCategoryController::class, 'index']);
    Route::get('course-group-categories/{courseGroupCategory}', [CourseGroupCategoryController::class, 'show']);

    // Assertions.
    Route::get('assertions/{entityId}/share/linkedin', [AssertionController::class, 'shareToLinkedIn']);
    Route::apiResource('assertions', AssertionController::class)
        ->only(['index', 'show']);
    Route::get('backpack-assertions/{learner:slug}', [BackpackAssertionController::class, 'index']);

    // Settings.
    Route::get('settings', SettingsController::class);

    // Search
    Route::get('search/{string}', config('badgefactor2.search_controller'));
});

Route::group([
    'prefix'     => 'api/{locale}',
    'middleware' => ['api', 'locale'],
    'namespace'  => 'Ctrlweb\BadgeFactor2\Http\Controllers\Api',
    'domain'     => config('badgefactor2.domain', null),
], function () {
    // Courses.
    Route::get('courses/{course}/validate-access', [CourseController::class, 'validateAccess']);
    Route::get('backpack/assertions/{learnerEmail}', [BackpackAssertionController::class, 'indexByEmail']);
});

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::group([
    'middleware' => 'web',
    'namespace'  => 'Ctrlweb\BadgeFactor2\Http\Controllers',
], function () {
    Route::get('/bf2/init', [BadgeFactor2Controller::class, 'initiateAuthCodeRetrieval'])
        ->middleware('auth')
        ->name('bf2.initAuth');
    Route::get('/bf2/auth', [BadgeFactor2Controller::class, 'getAccessTokenFromAuthCode'])
        ->middleware('auth')
        ->name('bf2.auth');
    Route::get('/badgr/assertions/{entityId}', [BadgeFactor2Controller::class, 'getBadgrAssertion'])
        ->name('bf2.badgrAssertion');
    Route::get('/badgr/badges/{entityId}', [BadgeFactor2Controller::class, 'getBadgrBadge'])
        ->name('bf2.badgrBadge');
});
