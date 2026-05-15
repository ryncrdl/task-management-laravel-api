<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\TeamController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
| All routes are prefixed with /api automatically by Laravel 11.
| JWT authentication is handled via the 'auth:api' middleware.
*/

// Public routes — no authentication required
// throttle:5,1 = 5 attempts per minute per IP to prevent brute force
Route::prefix('auth')->middleware('throttle:5,1')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});

// Health check endpoint
Route::get('/health', fn () => response()->json(['status' => 'ok', 'service' => 'laravel-api']));

// Protected routes — JWT required
Route::middleware(['auth:api', 'active'])->group(function () {

    // Auth management
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/refresh', [AuthController::class, 'refresh']);
        Route::get('/me', [AuthController::class, 'me']);
    });

    // ─── User Management (Module 2) ───────────────────────────────────────────
    Route::middleware(['role:admin,manager'])->group(function () {
        Route::get('/users', [UserController::class, 'index']);
        Route::post('/users', [UserController::class, 'store']);
        Route::get('/users/{user}', [UserController::class, 'show']);
        Route::patch('/users/{user}', [UserController::class, 'update']);
        Route::patch('/users/{user}/status', [UserController::class, 'toggleStatus']);
    });

    // ─── Team Management (Module 4) ──────────────────────────────────────────
    Route::get('/teams', [TeamController::class, 'index']);
    Route::get('/teams/{team}', [TeamController::class, 'show']);

    Route::middleware(['role:admin,manager'])->group(function () {
        Route::post('/teams', [TeamController::class, 'store']);
        Route::post('/teams/{team}/members', [TeamController::class, 'addMember']);
        Route::delete('/teams/{team}/members/{user}', [TeamController::class, 'removeMember']);
    });

    // ─── Task Management (Module 3) ──────────────────────────────────────────
    Route::get('/teams/{team}/tasks', [TaskController::class, 'index']);
    Route::post('/teams/{team}/tasks', [TaskController::class, 'store'])
        ->middleware('role:admin,manager');

    Route::get('/tasks/{task}', [TaskController::class, 'show']);
    Route::patch('/tasks/{task}', [TaskController::class, 'update']);
    Route::delete('/tasks/{task}', [TaskController::class, 'destroy']);
    Route::patch('/tasks/{task}/status', [TaskController::class, 'updateStatus']);

    // ─── Task Archival endpoint (called by Node.js cron job) ─────────────────
    Route::delete('/tasks/{task}/archive', [TaskController::class, 'archive'])
        ->middleware('role:admin');

    // ─── Internal endpoint for scheduled jobs (Node.js cron) ─────────────────
    Route::prefix('internal')->middleware('role:admin')->group(function () {
        Route::get('/tasks/upcoming-deadlines', [TaskController::class, 'upcomingDeadlines']);
        Route::get('/tasks/incomplete-by-user', [TaskController::class, 'incompleteByUser']);
    });
});
