<?php

use App\Http\Controllers\Api\V1\AdminKnowledgeArticleController;
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

Route::prefix('knowledge-base')->group(function () {
    Route::get('/', [KnowledgeArticleController::class, 'index']);
    Route::get('/{id}', [KnowledgeArticleController::class, 'show']);
    Route::post('/{id}/helpful', [KnowledgeArticleController::class, 'helpful']);
    Route::post('/{id}/not-helpful', [KnowledgeArticleController::class, 'notHelpful']);
});

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

    Route::get('/tickets/{id}/insights', [TicketController::class, 'insights']);
    Route::get('/tickets/{id}/sentiment', [TicketController::class, 'sentiment']);
    Route::get('/tickets/{id}/classification', [TicketController::class, 'classification']);

    Route::prefix('admin/knowledge-base')->group(function () {
        Route::get('/', [AdminKnowledgeArticleController::class, 'index']);
        Route::post('/', [AdminKnowledgeArticleController::class, 'store']);
        Route::get('/{id}', [AdminKnowledgeArticleController::class, 'show']);
        Route::put('/{id}', [AdminKnowledgeArticleController::class, 'update']);
        Route::delete('/{id}', [AdminKnowledgeArticleController::class, 'destroy']);
        Route::post('/{id}/publish', [AdminKnowledgeArticleController::class, 'publish']);
        Route::post('/{id}/unpublish', [AdminKnowledgeArticleController::class, 'unpublish']);
    });
});
