<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\RequestController;
use App\Http\Controllers\API\CategoryController;
use App\Http\Controllers\API\NotificationController;
use App\Http\Controllers\API\AdminController;

// Public routes (rate limited to slow down password guessing)
Route::middleware('throttle:10,1')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});
Route::get('/categories', [CategoryController::class, 'index']);

// Authenticated routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth endpoints
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Request endpoints (for residents)
    Route::get('/requests', [RequestController::class, 'index']);
    Route::post('/requests', [RequestController::class, 'store']);
    Route::get('/requests/{id}', [RequestController::class, 'show']);
    Route::delete('/requests/{id}', [RequestController::class, 'destroy']);

    // Notification endpoints
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::put('/notifications/read-all', [NotificationController::class, 'markAllRead']);
    Route::put('/notifications/{id}/read', [NotificationController::class, 'markRead']);

    // Admin/Staff only routes
    Route::middleware(['role:admin,staff'])->group(function () {
        Route::get('/admin/requests', [AdminController::class, 'allRequests']);
        Route::put('/admin/requests/{id}/status', [AdminController::class, 'updateStatus']);
        Route::get('/admin/dashboard', [AdminController::class, 'dashboard']);
        Route::get('/admin/users', [AdminController::class, 'getUsers']);
    });

    // Admin only routes
    Route::middleware(['role:admin'])->group(function () {
        Route::post('/admin/users', [AdminController::class, 'createUser']);
        Route::put('/admin/users/{id}', [AdminController::class, 'updateUser']);
        Route::delete('/admin/users/{id}', [AdminController::class, 'deleteUser']);
    });
});
