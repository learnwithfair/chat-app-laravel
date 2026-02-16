<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware(['auth', 'verified'])->name('chat.')->group(function () {
    Route::get('/', function () {
        return Inertia::render('Chat/Index');
    })->name('index');
});
