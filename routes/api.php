<?php

use App\Http\Controllers\v1\Admin\BlogController;
use App\Http\Controllers\v1\Admin\DashboardController;
use App\Http\Controllers\v1\Admin\EventController;
use App\Http\Controllers\v1\Admin\MemberController;
use App\Http\Controllers\v1\Admin\ResourcesController;
use App\Http\Controllers\v1\Auth\ForgetPasswordController;
use App\Http\Controllers\v1\Auth\LoginController;
use App\Http\Controllers\v1\Auth\RegisterController;
use App\Http\Controllers\v1\GeneralController;
use App\Http\Controllers\v1\Member\DonationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
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

Route::group(["prefix" => "v1"], function () {

    // Clear Cache
    Route::get('/clear-cache', function () {
        Artisan::call('optimize:clear');
        return "Cache Cleared Successfully";
    });

    // All General open routes
    Route::group(['prefix' => 'general'], function () {
        // Events open routes
        Route::group(['prefix' => 'events'], function () {
            Route::get('/types', [GeneralController::class, 'eventTypes']);
            Route::get('/categories', [GeneralController::class, 'eventCategories']);
            Route::get('/all', [GeneralController::class, 'allEvents']);
            Route::get('/show/{id}', [GeneralController::class, 'showEvent']);
            Route::post('/register/attendee', [GeneralController::class, 'registerAttendee']);
        });

        Route::group([], function () {
            Route::apiResource('tests', 'App\Http\Controllers\v1\member\TestController');
            Route::post('overview', 'App\Http\Controllers\v1\member\TestController@overview');
        });

        // Resources open routes
        Route::group(['prefix' => 'resources'], function () {
            Route::get('/categories', [GeneralController::class, 'resourceCategories']);
            Route::get('/all', [GeneralController::class, 'allResources']);
            Route::get('/show/{id}', [GeneralController::class, 'showResources']);
            Route::get('/download/{id}', [GeneralController::class, 'downloadResources']);
        });

        // Blogs open routes
        Route::group(['prefix' => 'blogs'], function () {
            Route::get('/categories', [GeneralController::class, 'blogCategories']);
            Route::get('/all', [GeneralController::class, 'allBlogs']);
            Route::get('/show/{id}', [GeneralController::class, 'showBlog']);
            Route::post('/activity/count', [GeneralController::class, 'activityCount']);
            Route::post('/add/comment', [GeneralController::class, 'addComment']);
            Route::put('/like/comment/{id}', [GeneralController::class, 'likeComment']);
        });

        // Donation open routes
        Route::group(['prefix' => 'donations'], function () {
            Route::post('/checkout', [DonationController::class, 'checkout']);
            Route::post('/payment', [DonationController::class, 'processDonation']);
            Route::post('/stripe/webhook', [DonationController::class, 'handleWebHook']);
            Route::get('/success', [DonationController::class, 'success']);
            Route::get('/failed', [DonationController::class, 'failed']);
        });

        // Member open routes
        Route::group(['prefix' => 'member'], function () {
            Route::post('/create', [GeneralController::class, 'registerMember']);
        });
    });

    // Authentication Route
    Route::group(["prefix" => "auth"], function () {
        Route::post('account/register', [RegisterController::class, 'register']);
        Route::post('account/login', [LoginController::class, 'login']);
        Route::post('account/forgot/password', [ForgetPasswordController::class, 'requestResetPasswordLink']);
        Route::get('account/verify/forgot/password/{token}', [ForgetPasswordController::class, 'verifyResetPassword']);
        Route::post('account/forgot/update/password', [ForgetPasswordController::class, 'adminResetPassword']);
        Route::get('account/logout', [LoginController::class, 'logout']);
    });

    //Admin Controller
    Route::group(['prefix' => 'admin', 'namespace' => 'v1\Admin', 'middleware' => ["auth:api", "admin"]], function () {

        // Dashboard controller
        Route::group(['prefix' => 'dashboard'], function () {
            Route::post('/', [DashboardController::class, 'index']);
        });

        // Events controller
        Route::group(['prefix' => 'events'], function () {
            Route::post('/', [EventController::class, 'index']);
            Route::post('/create', [EventController::class, 'create']);
            Route::get('/show/{id}', [EventController::class, 'show']);
            Route::post('/update', [EventController::class, 'update']);
            Route::post('/register/attendee', [EventController::class, 'registerAttendee']);
            Route::delete('/delete/{id}', [EventController::class, 'delete']);
        });

        // Resources controller
        Route::group(['prefix' => 'resources'], function () {
            Route::post('/', [ResourcesController::class, 'index']);
            Route::post('/create', [ResourcesController::class, 'create']);
            Route::get('/show/{id}', [ResourcesController::class, 'show']);
            Route::get('/download/{id}', [ResourcesController::class, 'download']);
            Route::post('/update', [ResourcesController::class, 'update']);
            Route::delete('/delete/{id}', [ResourcesController::class, 'delete']);
        });

        // Blogs controller
        Route::group(['prefix' => 'blogs'], function () {
            Route::post('/', [BlogController::class, 'index']);
            Route::post('/create', [BlogController::class, 'create']);
            Route::get('/show/{id}', [BlogController::class, 'show']);
            Route::post('/update', [BlogController::class, 'update']);
            Route::delete('/delete/{id}', [BlogController::class, 'delete']);
        });

        // Members controller
        Route::group(['prefix' => 'members'], function () {
            Route::post('/', [MemberController::class, 'index']);
        });
    });

    //Member Controller
    Route::group(['prefix' => 'member', 'namespace' => 'v1\Member', 'middleware' => ["auth:api", "member"]], function () {});
});
