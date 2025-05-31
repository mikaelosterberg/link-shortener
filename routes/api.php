<?php

use App\Http\Controllers\Api\LinkController;
use Illuminate\Support\Facades\Route;

// Links API routes with permission-based middleware
Route::middleware(['api.key:links:read'])->group(function () {
    Route::get('/links', [LinkController::class, 'index']);
    Route::get('/links/{id}', [LinkController::class, 'show']);
});

Route::middleware(['api.key:links:create'])->group(function () {
    Route::post('/links', [LinkController::class, 'store']);
});

Route::middleware(['api.key:links:update'])->group(function () {
    Route::put('/links/{id}', [LinkController::class, 'update']);
    Route::patch('/links/{id}', [LinkController::class, 'update']);
});

Route::middleware(['api.key:links:delete'])->group(function () {
    Route::delete('/links/{id}', [LinkController::class, 'destroy']);
});

Route::middleware(['api.key:stats:read'])->group(function () {
    Route::get('/links/{id}/stats', [LinkController::class, 'stats']);
});