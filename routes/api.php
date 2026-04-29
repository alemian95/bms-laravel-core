<?php

use App\Http\Controllers\Api\V1\Auth\LoginController;
use App\Http\Controllers\Api\V1\Auth\LogoutController;
use App\Http\Controllers\Api\V1\BookmarkController;
use App\Http\Controllers\Api\V1\CategoryController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('login', LoginController::class)
        ->middleware('throttle:login')
        ->name('api.v1.login');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', LogoutController::class)->name('api.v1.logout');

        Route::get('user', function (Request $request) {
            return $request->user();
        })->name('api.v1.user');

        Route::get('categories', [CategoryController::class, 'index'])
            ->middleware('ability:categories:read')
            ->name('api.v1.categories.index');

        Route::post('bookmarks', [BookmarkController::class, 'store'])
            ->middleware('ability:bookmarks:create')
            ->name('api.v1.bookmarks.store');
    });
});
