<?php

use Ctrlweb\BadgeFactor2\Http\Controllers\Api\BadgePageController;
use Ctrlweb\BadgeFactor2\Http\Controllers\Api\Badgr\AssertionController;
use Ctrlweb\BadgeFactor2\Http\Controllers\Api\Badgr\BadgeController;
use Ctrlweb\BadgeFactor2\Http\Controllers\Api\Badgr\IssuerController;
use Ctrlweb\BadgeFactor2\Http\Controllers\Api\CourseCategoryController;
use Ctrlweb\BadgeFactor2\Http\Controllers\Api\CourseGroupCategoryController;
use Ctrlweb\BadgeFactor2\Http\Controllers\Api\CourseGroupController;
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
    Route::apiResource('badges', BadgeController::class)->only(['index', 'show']);

    // Badge pages.
    Route::apiResource('badge-pages', BadgePageController::class)->only(['index', 'show']);
    Route::get('badge-pages-by-course-group/{courseGroup}', [BadgePageController::class, 'badgePageByCourseGroup']);

    // Course Groups.
    Route::apiResource('course-groups', CourseGroupController::class)
    ->only(['index', 'show']);

    // Course Group Categories.
    Route::get('course-group-categories', [CourseGroupCategoryController::class, 'index']);
    Route::get('course-group-categories/{courseGroupCategory}', [CourseGroupCategoryController::class, 'show']);

    // Course Categories.
    Route::apiResource('course-categories', CourseCategoryController::class)
        ->only(['index', 'show']);

    // Assertions.
    Route::apiResource('assertions', AssertionController::class)
        ->only(['index', 'show']);
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
    Route::get('/bf2/auth', [BadgeFactor2Controller::class, 'getAccessTokenFromAuthCode'])
        ->middleware('auth')
        ->name('bf2.auth');
});
