<?php

use BmsCore\Packages\Ai\Http\Controllers\SummaryController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'verified'])->group(function () {
    Route::get('bookmarks/{bookmark}/summary', SummaryController::class)->name('ai.summary');
});
