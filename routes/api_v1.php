<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\KnowledgeArticleController;
use App\Http\Controllers\Api\V1\TicketCategoryController;
use App\Http\Controllers\Api\V1\TicketController;
use App\Http\Controllers\Api\V1\TicketReplyController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);

    Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])
        ->name('verification.verify');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/profile', [AuthController::class, 'profile']);
        Route::post('/email/verification-notification', [AuthController::class, 'resendVerificationEmail'])
            ->name('verification.send');
    });
});

Route::get('/categories', [TicketCategoryController::class, 'index']);
Route::get('/categories/{id}', [TicketCategoryController::class, 'show']);

Route::get('/knowledge-base', [KnowledgeArticleController::class, 'index']);
Route::get('/knowledge-base/{id}', [KnowledgeArticleController::class, 'show']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/tickets/stats', [TicketController::class, 'stats']);

    Route::get('/tickets', [TicketController::class, 'index']);
    Route::post('/tickets', [TicketController::class, 'store']);
    Route::get('/tickets/{id}', [TicketController::class, 'show']);
    Route::put('/tickets/{id}', [TicketController::class, 'update']);
    Route::delete('/tickets/{id}', [TicketController::class, 'destroy']);

    Route::post('/tickets/{id}/assign', [TicketController::class, 'assign']);
    Route::patch('/tickets/{id}/status', [TicketController::class, 'changeStatus']);
    Route::patch('/tickets/{id}/priority', [TicketController::class, 'changePriority']);

    Route::get('/tickets/{ticketId}/replies', [TicketReplyController::class, 'index']);
    Route::post('/tickets/{ticketId}/replies', [TicketReplyController::class, 'store']);
});
