<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TaskActivityController;
use App\Http\Controllers\Api\TaskController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:register');
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    // Must be registered before apiResource, otherwise GET /tasks/{task} captures "statistics" and "trash".
    Route::get('/tasks/statistics', [TaskController::class, 'statistics'])->name('tasks.statistics');
    Route::get('/tasks/trash', [TaskController::class, 'trash'])->name('tasks.trash');

    Route::patch('/tasks/{task}/toggle-completion', [TaskController::class, 'toggleCompletion'])
        ->name('tasks.toggle-completion');

    // implicit binding skips soft-deleted rows by default; these two need them
    Route::post('/tasks/{task}/restore', [TaskController::class, 'restore'])
        ->withTrashed()
        ->name('tasks.restore');
    Route::delete('/tasks/{task}/force-delete', [TaskController::class, 'forceDelete'])
        ->withTrashed()
        ->name('tasks.force-delete');

    Route::apiResource('tasks', TaskController::class);

    Route::get('/activities', TaskActivityController::class)->name('activities.index');
});
