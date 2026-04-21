<?php

use App\Http\Controllers\BookmarkController;
use App\Http\Controllers\CategoryController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::inertia('/', 'welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');

    Route::resource('categories', CategoryController::class)->except(['show', 'edit', 'create']);
    Route::get('bookmarks/{bookmark}/read', [BookmarkController::class, 'read'])->name('bookmarks.read');
    Route::patch('bookmarks/{bookmark}/update-progress', [BookmarkController::class, 'updateProgress'])->name('bookmarks.updateProgress');
    Route::resource('bookmarks', BookmarkController::class)->only(['index', 'store', 'destroy']);

});

require __DIR__.'/settings.php';
