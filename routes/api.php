<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\FlowerController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\KnowledgeController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\SiteSettingController;
use App\Http\Controllers\Api\UploadController;

// Public auth routes (rate-limited to prevent brute force)
Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
Route::post('/auth/register', [AuthController::class, 'register'])->middleware('throttle:10,1');

// Public data routes (read-only) - organized with apiResource
Route::apiResource('flowers', FlowerController::class)->only(['index', 'show']);
Route::apiResource('categories', CategoryController::class)->only(['index', 'show']);
Route::apiResource('knowledge', KnowledgeController::class)->only(['index', 'show']);

// Public chat routes (rate-limited: 30 req/min per IP to prevent abuse)
Route::middleware('throttle:30,1')->group(function () {
    Route::post('/chat', [ChatController::class, 'chat']);
    Route::get('/chat/knowledge', [ChatController::class, 'knowledge']);
});

// Public settings routes (read-only)
Route::get('/settings', [SiteSettingController::class, 'index']);

// Protected routes (require authentication)
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::get('/auth/user', [AuthController::class, 'user']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/is-admin', [AuthController::class, 'isAdmin']);
});

// Admin routes - require admin + auth (for CRUD operations)
Route::middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::apiResource('flowers', FlowerController::class)->only(['store', 'update', 'destroy']);
    Route::apiResource('categories', CategoryController::class)->only(['store', 'update', 'destroy']);
    Route::apiResource('knowledge', KnowledgeController::class)->only(['store', 'update', 'destroy']);

    // Settings
    Route::put('/settings', [SiteSettingController::class, 'update']);
    Route::post('/settings/batch', [SiteSettingController::class, 'batchUpdate']);

    // Upload
    Route::post('/upload', [UploadController::class, 'upload']);
    Route::delete('/upload', [UploadController::class, 'delete']);
});
