<?php

use App\Http\Controllers\Chat\ConversationController;
use App\Http\Controllers\Chat\MessageController;
use App\Http\Controllers\Chat\UserSearchController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/products', [ProductController::class, 'index'])->name('products.index');
    Route::get('/products/create', [ProductController::class, 'create'])->name('products.create');
    Route::post('/products', [ProductController::class, 'store'])->name('products.store');

});

// Route::middleware(['auth', 'verified'])->group(function () {
//     Route::get('/chat', [ConversationController::class, 'index'])->name('chat.index');
//     Route::get('/chat/{conversation}', [ConversationController::class, 'show'])->name('chat.show');

//     Route::get('/chat/{conversation}/messages', [MessageController::class, 'index'])->name('chat.messages.index');
//     Route::post('/chat/{conversation}/messages', [MessageController::class, 'store'])->name('chat.messages.store');
//     Route::post('/chat/{conversation}/messages/{message}/read', [MessageController::class, 'read'])->name('chat.messages.read');
// });

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/chat', [ConversationController::class, 'index'])->name('chat.index');
    Route::get('/chat/{conversation}', [ConversationController::class, 'show'])->name('chat.show');

    // NEW: create/find a conversation
    Route::post('/chat', [ConversationController::class, 'store'])->name('chat.store');

    // NEW: simple user search (JSON)
    Route::get('/chat-users', UserSearchController::class)->name('chat.users.search');

    // messages
    Route::get('/chat/{conversation}/messages', [MessageController::class, 'index'])->name('chat.messages.index');
    Route::post('/chat/{conversation}/messages', [MessageController::class, 'store'])->name('chat.messages.store');
    Route::post('/chat/{conversation}/messages/{message}/read', [MessageController::class, 'read'])->name('chat.messages.read');

    Route::post('/chat/{conversation}/read-all', [MessageController::class, 'readAll'])->name('chat.messages.read_all');

});

require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
