<?php

use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\RegisterController;
use App\Http\Controllers\Api\SocialAuthController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;


Route::post('/social-login', [SocialAuthController::class, 'socialLogin'])->middleware('throttle:5,1');

//Register API
Route::middleware('throttle:5,1')->controller(RegisterController::class)->prefix('users/register')->group(function () {
    // User Register
    Route::post('/', 'userRegister');

    // Verify OTP
    Route::post('/otp-verify', 'otpVerify');

    // Resend OTP
    Route::post('/otp-resend', 'otpResend');
});

//Login API
Route::middleware('throttle:5,1')->controller(LoginController::class)->prefix('users/login')->group(function () {

    // User Login
    Route::post('/', 'userLogin');

    // Verify Email
    Route::post('/email-verify', 'emailVerify');

    // Resend OTP
    Route::post('/otp-resend', 'otpResend');

    // Verify OTP
    Route::post('/otp-verify', 'otpVerify');

    //Reset Password
    Route::post('/reset-password', 'resetPassword');
});

Route::group(['middleware' => ['auth:sanctum']], function () {

    Route::controller(UserController::class)->prefix('users')->group(function () {
        Route::get('/data', 'userData');
        Route::post('/data/update', 'userUpdate');
        Route::post('/logout', 'logoutUser');
        Route::post('/delete', 'deleteUser');
        Route::post('/delete-firebase-token', 'deleteFirebaseToken');
    });
});
