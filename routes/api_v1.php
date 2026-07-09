<?php

use App\Http\Controllers\Api\V1\AdminDashboardController;
use App\Http\Controllers\Api\V1\AIDashboardController;
use App\Http\Controllers\Api\V1\AILogController;
use App\Http\Controllers\Api\V1\AdminKnowledgeArticleController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\KnowledgeArticleController;
use App\Http\Controllers\Api\V1\TicketCategoryController;
use App\Http\Controllers\Api\V1\TicketController;
use App\Http\Controllers\Api\V1\TicketReplyController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register'])
        ->middleware('throttle:5,1');
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:10,1');
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])
        ->middleware('throttle:3,1');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])
        ->middleware('throttle:5,1');

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
        Route::post('/{id}/helpful', [KnowledgeArticleController::class, 'helpful'])
            ->middleware('throttle:10,1');
        Route::post('/{id}/not-helpful', [KnowledgeArticleController::class, 'notHelpful'])
            ->middleware('throttle:10,1');
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
    Route::get('/tickets/{id}/rag-answer', [TicketController::class, 'ragAnswer']);

    Route::prefix('ai-dashboard')->group(function () {
        Route::get('/overview', [AIDashboardController::class, 'overview']);
        Route::get('/daily', [AIDashboardController::class, 'dailyUsage']);
        Route::get('/monthly', [AIDashboardController::class, 'monthlyUsage']);
        Route::get('/hourly', [AIDashboardController::class, 'hourlyToday']);
        Route::get('/categories', [AIDashboardController::class, 'topCategories']);
        Route::get('/models', [AIDashboardController::class, 'modelBreakdown']);
        Route::get('/failures', [AIDashboardController::class, 'recentFailures']);
        Route::get('/requests', [AIDashboardController::class, 'recentRequests']);

        Route::get('/logs', [AILogController::class, 'index']);
        Route::get('/logs/summary', [AILogController::class, 'summary']);
        Route::get('/logs/{id}', [AILogController::class, 'show']);
    });

    Route::prefix('admin/dashboard')->group(function () {
        Route::get('/widgets', [AdminDashboardController::class, 'widgets']);
        Route::get('/status', [AdminDashboardController::class, 'statusDistribution']);
        Route::get('/priority', [AdminDashboardController::class, 'priorityDistribution']);
        Route::get('/agents', [AdminDashboardController::class, 'topAgents']);
        Route::get('/trend', [AdminDashboardController::class, 'dailyTrend']);
        Route::get('/ai-trend', [AdminDashboardController::class, 'aiResponseTrend']);
        Route::get('/satisfaction', [AdminDashboardController::class, 'customerSatisfaction']);
    });

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
