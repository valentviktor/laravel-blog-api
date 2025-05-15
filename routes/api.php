<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\BookmarkController;
use App\Http\Controllers\Api\PostCategoryController;

Route::controller(AuthController::class)->middleware('api')->group(function() {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login'])->name('login');
    Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('reset-password', [AuthController::class, 'resetPassword']);
});

Route::middleware(['auth:sanctum'])->group(function () {
    Route::apiResource('posts', PostController::class)->except(['update']);
    Route::apiResource('post-categories', PostCategoryController::class);
    Route::apiResource('users', UserController::class);

    Route::get('/bookmarks', [BookmarkController::class, 'index']);
    Route::post('/bookmarks/{post}', [BookmarkController::class, 'store']);
    Route::post('posts/{post}/update', [PostController::class, 'update']);
    Route::post('logout', [AuthController::class, 'logout']);
});
