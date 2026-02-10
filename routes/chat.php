<?php

use Illuminate\Support\Facades\Route;

Route::prefix('chat')->middleware(['auth', 'last_seen'])->name('chat.')->group(function () {

});
