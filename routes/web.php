<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::resource('project', ProjectController::class);
    Route::post('/task', [TaskController::class, 'store'])->name('task.store');
    Route::put('/task/{task}', [TaskController::class, 'update'])->name('task.update');
    Route::delete('/task/{task}', [TaskController::class, 'destroy'])->name('task.destroy');
    Route::patch('/task/{task}/status', [TaskController::class, 'updateStatus'])->name('task.updateStatus');
    Route::resource('user', UserController::class);
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
