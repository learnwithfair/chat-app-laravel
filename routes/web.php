<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Route::get('/debug-session', function () {
//     return response()->json([
//         'authenticated' => auth()->check(),
//         'user_id'       => auth()->id(),
//         'user'          => auth()->user(),
//         'session_id'    => session()->getId(),
//         'guards'        => config('auth.guards'),
//         'default_guard' => config('auth.defaults.guard'),
//     ]);
// })->middleware('web');

// // Custom broadcast auth with FULL debugging
// Route::post('/broadcasting/auth', function (Request $request) {
//     $logData = [
//         'authenticated' => auth()->check(),
//         'user_id'       => auth()->id(),
//         'user_name'     => auth()->user()?->name,
//         'session_id'    => session()->getId(),
//         'socket_id'     => $request->input('socket_id'),
//         'channel_name'  => $request->input('channel_name'),
//         'all_input'     => $request->all(),
//     ];

//     Log::info('=== Broadcasting Auth Attempt ===', $logData);

//     if (! auth()->check()) {
//         Log::error('Broadcasting Auth Failed: NOT AUTHENTICATED');
//         return response()->json(['error' => 'Unauthenticated'], 401);
//     }

//     try {
//         $response = Broadcast::auth($request);
//         Log::info('Broadcasting Auth SUCCESS', ['response' => $response->getContent()]);
//         return $response;
//     } catch (\Exception $e) {
//         Log::error('Broadcasting Auth EXCEPTION', [
//             'error' => $e->getMessage(),
//             'file'  => $e->getFile(),
//             'line'  => $e->getLine(),
//             'trace' => $e->getTraceAsString(),
//         ]);
//         return response()->json(['error' => $e->getMessage()], 403);
//     }
// })->middleware(['web']);

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/', function () {
        return Inertia::render('Chat/Index');
    })->name('chat.index');
});

require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
