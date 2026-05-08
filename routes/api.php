<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\RequestController;
use App\Http\Controllers\API\CategoryController;
use App\Http\Controllers\API\NotificationController;
use App\Http\Controllers\API\AdminController;

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/categories', [CategoryController::class, 'index']);

// Authenticated routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth endpoints
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::put('/me', [AuthController::class, 'update']);
    Route::put('/me/password', [AuthController::class, 'changePassword']);
    
    // Request endpoints (for residents)
    Route::get('/requests', [RequestController::class, 'index']);
    Route::post('/requests', [RequestController::class, 'store']);
    Route::get('/requests/{id}', [RequestController::class, 'show']);
    Route::delete('/requests/{id}', [RequestController::class, 'destroy']);
    
    // Notification endpoints
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::put('/notifications/read-all', [NotificationController::class, 'markAllRead']);
    
    // Admin/Staff only routes
    Route::middleware('role:admin,staff')->group(function () {
        Route::get('/admin/requests', [AdminController::class, 'allRequests']);
        Route::put('/admin/requests/{id}/status', [AdminController::class, 'updateStatus']);
        Route::get('/admin/dashboard', [AdminController::class, 'dashboard']);
        
        // User Management Routes (Admin only for write operations)
        Route::get('/admin/users', [AdminController::class, 'getUsers']);
        Route::post('/admin/users', [AdminController::class, 'createUser'])->middleware('role:admin');
        Route::put('/admin/users/{id}', [AdminController::class, 'updateUser'])->middleware('role:admin');
        Route::delete('/admin/users/{id}', [AdminController::class, 'deleteUser'])->middleware('role:admin');
    });
});