<?php

use App\Http\Controllers\Api\EmailSettingsController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::get('/test', function () {
    return response()->json(['message' => 'API route test successful!']);
});

// This single line creates all the necessary CRUD routes:
// GET /api/users (index)
// POST /api/users (store)
// GET /api/users/{user} (show)
// PUT/PATCH /api/users/{user} (update)
// DELETE /api/users/{user} (destroy)
Route::apiResource('users', UserController::class);

// --- Authentication Routes ---

// Login (Publicly accessible)
Route::post('/v1/login', [LoginController::class, 'login']);

// Logout (Requires authentication)
Route::middleware('auth:sanctum')->post('/logout', [LoginController::class, 'logout']);

// Auth-protected routes (e.g., for logged-in users)
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// --- Dynamic Email Settings Routes (for Admin Dashboard) ---
// These should be protected by an admin middleware in a real app
Route::middleware(['auth:sanctum'])->prefix('settings')->group(function () {
    Route::get('/email', [EmailSettingsController::class, 'getSettings']);
    Route::post('/email', [EmailSettingsController::class, 'saveSettings']);
});

// --- Guest-accessible Password Reset Routes ---
Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])
    ->middleware('guest')
    ->name('password.email');

Route::post('/reset-password', [NewPasswordController::class, 'store'])
    ->middleware('guest')
    ->name('password.store');
