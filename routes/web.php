<?php

use App\Http\Controllers\Web\AdminController;
use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\TicketController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        if (auth()->user()->hasRole(['admin', 'agent'])) {
            return redirect()->route('admin.dashboard');
        }
        return redirect()->route('dashboard');
    }
    return redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/dashboard', [TicketController::class, 'index'])->name('dashboard');
    Route::get('/tickets/create', [TicketController::class, 'create'])->name('tickets.create');
    Route::post('/tickets', [TicketController::class, 'store'])->name('tickets.store');
    Route::get('/tickets/{id}', [TicketController::class, 'show'])->name('tickets.show');
    Route::post('/tickets/{id}/reply', [TicketController::class, 'reply'])->name('tickets.reply');

    Route::middleware('role:admin,agent')->group(function () {
        Route::get('/admin', [AdminController::class, 'index'])->name('admin.dashboard');
        Route::get('/admin/tickets/{id}', [AdminController::class, 'showTicket'])->name('admin.tickets.show');
        Route::patch('/admin/tickets/{id}/status', [AdminController::class, 'updateStatus'])->name('admin.tickets.status');
        Route::patch('/admin/tickets/{id}/priority', [AdminController::class, 'updatePriority'])->name('admin.tickets.priority');
        Route::post('/admin/tickets/{id}/reply', [AdminController::class, 'reply'])->name('admin.tickets.reply');
        Route::delete('/admin/tickets/{id}', [AdminController::class, 'deleteTicket'])->name('admin.tickets.delete');
    });
});
