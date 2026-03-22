<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\RateLimiter;
use App\Http\Controllers\Api\FlowerController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\KnowledgeController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\SiteSettingController;
use App\Http\Controllers\Api\UploadController;

// Rate limiter: general API (60 req/min)
RateLimiter::for('api', function ($request) {
    return \Illuminate\Cache\RateLimiting\Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
});
// Rate limiter: auth endpoints (10 req/min, stricter)
RateLimiter::for('auth-api', function ($request) {
    return \Illuminate\Cache\RateLimiting\Limit::perMinute(10)->by($request->ip());
});
// Rate limiter: chat endpoints (30 req/min)
RateLimiter::for('chat-api', function ($request) {
    return \Illuminate\Cache\RateLimiting\Limit::perMinute(30)->by($request->user()?->id ?: $request->ip());
});
// Rate limiter: upload endpoints (20 req/min)
RateLimiter::for('upload-api', function ($request) {
    return \Illuminate\Cache\RateLimiting\Limit::perMinute(20)->by($request->user()?->id ?: $request->ip());
});

// Public auth routes (rate limited: 10 req/min)
Route::middleware('throttle:auth-api')->group(function () {
    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::post('/auth/register', [AuthController::class, 'register']);
});

// Public data routes (read-only, rate limited: 60 req/min)
Route::middleware('throttle:api')->group(function () {
    Route::get('/flowers', [FlowerController::class, 'index']);
    Route::get('/flowers/{flower}', [FlowerController::class, 'show']);
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/categories/{category}', [CategoryController::class, 'show']);
    Route::get('/knowledge', [KnowledgeController::class, 'index']);
    Route::get('/knowledge/{knowledge}', [KnowledgeController::class, 'show']);
});

// Public chat routes (rate limited: 30 req/min)
Route::middleware('throttle:chat-api')->group(function () {
    Route::post('/chat', [ChatController::class, 'chat']);
    Route::get('/chat/knowledge', [ChatController::class, 'knowledge']);
});

// Public settings routes (read-only)
Route::middleware('throttle:api')->group(function () {
    Route::get('/settings', [SiteSettingController::class, 'index']);
});

// Protected routes (require authentication + rate limit)
Route::middleware(['throttle:api', 'auth:sanctum'])->group(function () {
    Route::get('/auth/user', [AuthController::class, 'user']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/is-admin', [AuthController::class, 'isAdmin']);
});

// Admin routes - require admin + auth (for CRUD operations)
Route::middleware(['throttle:api', 'auth:sanctum', 'admin'])->group(function () {
    Route::post('/flowers', [FlowerController::class, 'store']);
    Route::put('/flowers/{flower}', [FlowerController::class, 'update']);
    Route::delete('/flowers/{flower}', [FlowerController::class, 'destroy']);
    Route::post('/categories', [CategoryController::class, 'store']);
    Route::put('/categories/{category}', [CategoryController::class, 'update']);
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy']);
    Route::post('/knowledge', [KnowledgeController::class, 'store']);
    Route::put('/knowledge/{knowledge}', [KnowledgeController::class, 'update']);
    Route::delete('/knowledge/{knowledge}', [KnowledgeController::class, 'destroy']);

    Route::put('/settings', [SiteSettingController::class, 'update']);
    Route::post('/settings/batch', [SiteSettingController::class, 'batchUpdate']);

    // Upload (separate stricter limit: 20 req/min)
    Route::middleware('throttle:upload-api')->group(function () {
        Route::post('/upload', [UploadController::class, 'upload']);
        Route::delete('/upload', [UploadController::class, 'delete']);
    });
});
