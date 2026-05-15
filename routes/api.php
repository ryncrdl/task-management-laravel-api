<?php

use App\Http\Controllers\Api\ActivityLogController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\NotificationJobController;
use App\Http\Controllers\Api\TaskCommentController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\TaskFilterPresetController;
use App\Http\Controllers\Api\TeamController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public routes
Route::prefix('auth')->middleware('throttle:5,1')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});

// Health check
Route::get('/health', fn () => response()->json(['status' => 'ok', 'service' => 'laravel-api']));

// Protected routes — JWT required, throttle: 120 req/min
Route::middleware(['auth:api', 'active', 'throttle:120,1'])->group(function () {

    // Auth management
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/refresh', [AuthController::class, 'refresh']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::patch('/password', [UserController::class, 'changePassword']);
    });

    // ─── User Management ──────────────────────────────────────────────────────
    Route::get('/users/directory', [UserController::class, 'directory']); // all roles
    Route::middleware('role:admin,manager')->group(function () {
        Route::get('/users', [UserController::class, 'index']);
        Route::post('/users', [UserController::class, 'store']);
        Route::get('/users/{user}', [UserController::class, 'show']);
        Route::patch('/users/{user}', [UserController::class, 'update']);
        Route::patch('/users/{user}/status', [UserController::class, 'toggleStatus']);
        Route::patch('/users/{user}/password', [UserController::class, 'resetPassword']);
    });

    // ─── Team Management ─────────────────────────────────────────────────────
    Route::get('/teams', [TeamController::class, 'index']);
    Route::get('/teams/{team}', [TeamController::class, 'show']);

    Route::middleware('role:admin,manager')->group(function () {
        Route::post('/teams', [TeamController::class, 'store']);
        Route::post('/teams/{team}/members', [TeamController::class, 'addMember']);
        Route::delete('/teams/{team}/members/{user}', [TeamController::class, 'removeMember']);
    });

    // ─── Task Management ─────────────────────────────────────────────────────
    Route::get('/teams/{team}/tasks', [TaskController::class, 'index']);
    Route::post('/teams/{team}/tasks', [TaskController::class, 'store'])
        ->middleware('role:admin,manager');

    Route::get('/tasks/mine', [TaskController::class, 'mine']); // all assigned tasks for current user
    Route::get('/tasks/{task}', [TaskController::class, 'show']);
    Route::patch('/tasks/{task}', [TaskController::class, 'update']);
    Route::delete('/tasks/{task}', [TaskController::class, 'destroy']);
    Route::patch('/tasks/{task}/status', [TaskController::class, 'updateStatus']);

    // Batch operations
    Route::post('/tasks/batch', [TaskController::class, 'batch'])
        ->middleware('role:admin,manager');

    // ─── Task Comments ────────────────────────────────────────────────────────
    Route::get('/tasks/{task}/comments', [TaskCommentController::class, 'index']);
    Route::post('/tasks/{task}/comments', [TaskCommentController::class, 'store']);
    Route::delete('/tasks/{task}/comments/{comment}', [TaskCommentController::class, 'destroy']);

    // ─── Activity Log ─────────────────────────────────────────────────────────
    Route::get('/activity-logs', [ActivityLogController::class, 'index']);

    // ─── Filter Presets ───────────────────────────────────────────────────────
    Route::get('/task-filter-presets', [TaskFilterPresetController::class, 'index']);
    Route::post('/task-filter-presets', [TaskFilterPresetController::class, 'store']);
    Route::delete('/task-filter-presets/{preset}', [TaskFilterPresetController::class, 'destroy']);



    // ─── Admin: Notification Jobs (React UI) ──────────────────────────────────
    Route::middleware('role:admin')->prefix('admin')->group(function () {
        Route::get('/notification-jobs/stats', [NotificationJobController::class, 'stats']);
        Route::get('/notification-jobs', [NotificationJobController::class, 'index']);
        Route::post('/notification-jobs/{id}/retry', [NotificationJobController::class, 'retry']);
        Route::delete('/notification-jobs/{id}', [NotificationJobController::class, 'destroy']);
    });
});

// ─── Internal service-to-service routes (Node.js → Laravel, no JWT) ──────────
Route::prefix('internal')->middleware(['throttle:120,1', 'internal.service'])->group(function () {
    Route::post('/jobs',        [NotificationJobController::class, 'store']);
    Route::get('/jobs/pending', [NotificationJobController::class, 'claimPending']);
    Route::patch('/jobs/{id}',  [NotificationJobController::class, 'updateStatus']);

    // ─── Cron job data endpoints (no expiring JWT) ────────────────────────────
    Route::get('/tasks/upcoming-deadlines', [TaskController::class, 'upcomingDeadlines']);
    Route::get('/tasks/incomplete-by-user', [TaskController::class, 'incompleteByUser']);
    Route::get('/teams',                    [TeamController::class, 'indexInternal']);
    Route::get('/teams/{team}/tasks',       [TaskController::class, 'index']);
    Route::delete('/tasks/{task}/archive',  [TaskController::class, 'archive']);
});
