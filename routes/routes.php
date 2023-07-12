<?php

use Illuminate\Support\Facades\Route;


use Ctrlweb\BadgeFactor2\Http\Controllers\Api\BadgePageController;
use Ctrlweb\BadgeFactor2\Http\Controllers\Api\Badgr\IssuerController;
use Ctrlweb\BadgeFactor2\Http\Controllers\Api\Badgr\BadgeController;
use Ctrlweb\BadgeFactor2\Http\Controllers\BadgeFactor2Controller;

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
    'prefix' => 'api/{locale}',
    'middleware' => ['api', 'locale'],
    'namespace' => 'Ctrlweb\BadgeFactor2\Http\Controllers\Api',
    'domain' => config('badgefactor2.domain', null),
], function () {
    // Issuers.
    Route::apiResource('issuers', IssuerController::class)->only(['index', 'show']);
    Route::get('issuers-count', [IssuerController::class, 'count']);

    // Badges.
    Route::apiResource('badges', BadgeController::class)->only(['index', 'show']);

    // Badge pages.
    Route::apiResource('badge-pages', BadgePageController::class)->only(['index', 'show']);
    Route::get('badge-pages-by-course-group/{courseGroup}', [BadgePageController::class, 'badgePageByCourseGroup']);
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
    'namespace' => 'Ctrlweb\BadgeFactor2\Http\Controllers',
], function () {
    Route::get('/bf2/redirect', [BadgeFactor2Controller::class, 'getAccessTokenFromAuthCode'])
        ->middleware('auth')
        ->name('bf2.redirect');
});

