<?php

use BmsCore\Packages\Ai\Http\Controllers\SummaryController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'verified'])->group(function () {
    Route::get('bookmarks/{bookmark}/summary', [SummaryController::class, 'show'])->name('ai.summary');
    Route::post('bookmarks/{bookmark}/summary', [SummaryController::class, 'store'])->name('ai.generate-summary');
});
