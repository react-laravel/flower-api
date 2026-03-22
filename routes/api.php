<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\FlowerController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\KnowledgeController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\SiteSettingController;
use App\Http\Controllers\Api\UploadController;

Route::middleware(['throttle:api'])->group(function () {
    // Public auth routes — login has stricter limit
    Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:auth');
    Route::post('/auth/register', [AuthController::class, 'register']);

    // Public data routes (read-only)
    Route::get('/flowers', [FlowerController::class, 'index']);
    Route::get('/flowers/{flower}', [FlowerController::class, 'show']);
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/categories/{category}', [CategoryController::class, 'show']);
    Route::get('/knowledge', [KnowledgeController::class, 'index']);
    Route::get('/knowledge/{knowledge}', [KnowledgeController::class, 'show']);

    // Public chat routes
    Route::post('/chat', [ChatController::class, 'chat']);
    Route::get('/chat/knowledge', [ChatController::class, 'knowledge']);

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
        Route::post('/flowers', [FlowerController::class, 'store']);
        Route::put('/flowers/{flower}', [FlowerController::class, 'update']);
        Route::delete('/flowers/{flower}', [FlowerController::class, 'destroy']);
        Route::post('/categories', [CategoryController::class, 'store']);
        Route::put('/categories/{category}', [CategoryController::class, 'update']);
        Route::delete('/categories/{category}', [CategoryController::class, 'destroy']);
        Route::post('/knowledge', [KnowledgeController::class, 'store']);
        Route::put('/knowledge/{knowledge}', [KnowledgeController::class, 'update']);
        Route::delete('/knowledge/{knowledge}', [KnowledgeController::class, 'destroy']);

        // Settings
        Route::put('/settings', [SiteSettingController::class, 'update']);
        Route::post('/settings/batch', [SiteSettingController::class, 'batchUpdate']);

        // Upload
        Route::post('/upload', [UploadController::class, 'upload']);
        Route::delete('/upload', [UploadController::class, 'delete']);
    });
});
