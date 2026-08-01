<?php

use BmsCore\Packages\Ai\Http\Controllers\ChatController;
use BmsCore\Packages\Ai\Http\Controllers\SummaryController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'verified'])->group(function () {
    Route::get('bookmarks/{bookmark}/summary', [SummaryController::class, 'show'])->name('ai.summary');
    Route::post('bookmarks/{bookmark}/summary', [SummaryController::class, 'store'])->name('ai.generate-summary');

    Route::get('bookmarks/{bookmark}/chat', [ChatController::class, 'index'])->name('ai.chat.index');
    Route::post('bookmarks/{bookmark}/chat', [ChatController::class, 'store'])->name('ai.chat.store');
    Route::delete('bookmarks/{bookmark}/chat', [ChatController::class, 'destroy'])->name('ai.chat.destroy');
});
