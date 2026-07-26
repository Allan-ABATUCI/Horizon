<?php

use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/search', [SearchController::class, 'index'])->name('search');
    Route::resource('project', ProjectController::class);
    Route::post('/project/{project}/members', [ProjectController::class, 'addMember'])->name('project.members.store');
    Route::delete('/project/{project}/members/{user}', [ProjectController::class, 'removeMember'])->name('project.members.destroy');
    Route::post('/task', [TaskController::class, 'store'])->name('task.store');
    Route::put('/task/{task}', [TaskController::class, 'update'])->name('task.update');
    Route::delete('/task/{task}', [TaskController::class, 'destroy'])->name('task.destroy');
    Route::patch('/task/{task}/status', [TaskController::class, 'updateStatus'])->name('task.updateStatus');
    Route::get('/task/{task}/comments', [CommentController::class, 'index'])->name('task.comments.index');
    Route::post('/task/{task}/comments', [CommentController::class, 'store'])->name('comments.store');
    Route::delete('/comments/{comment}', [CommentController::class, 'destroy'])->name('comments.destroy');
    Route::get('/task/{task}/attachments', [AttachmentController::class, 'index'])->name('task.attachments.index');
    Route::post('/task/{task}/attachments', [AttachmentController::class, 'store'])->name('attachments.store');
    Route::get('/attachments/{attachment}/download', [AttachmentController::class, 'download'])->name('attachments.download');
    Route::delete('/attachments/{attachment}', [AttachmentController::class, 'destroy'])->name('attachments.destroy');
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.readAll');
    Route::resource('user', UserController::class);
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
