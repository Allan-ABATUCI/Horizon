<?php

use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\ChecklistItemController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TaskDependencyController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::resource('project', ProjectController::class);
    Route::post('/project/{project}/members', [ProjectController::class, 'addMember'])->name('project.members.store');
    Route::delete('/project/{project}/members/{user}', [ProjectController::class, 'removeMember'])->name('project.members.destroy');
    Route::post('/task', [TaskController::class, 'store'])->name('task.store');
    Route::put('/task/{task}', [TaskController::class, 'update'])->name('task.update');
    Route::delete('/task/{task}', [TaskController::class, 'destroy'])->name('task.destroy');
    Route::patch('/task/{task}/status', [TaskController::class, 'updateStatus'])->name('task.updateStatus');
    Route::resource('user', UserController::class);

    // Endpoints JSON "widgets" (fetch autonome, pas de navigation Inertia) —
    // throttlés séparément puisqu'ils sont appelables bien plus fréquemment
    // qu'une navigation de page classique.
    Route::middleware('throttle:60,1')->group(function () {
        Route::get('/search', [SearchController::class, 'index'])->name('search');
        Route::get('/task/{task}/comments', [CommentController::class, 'index'])->name('task.comments.index');
        Route::post('/task/{task}/comments', [CommentController::class, 'store'])->name('comments.store');
        Route::delete('/comments/{comment}', [CommentController::class, 'destroy'])->name('comments.destroy');
        Route::get('/task/{task}/attachments', [AttachmentController::class, 'index'])->name('task.attachments.index');
        Route::get('/attachments/{attachment}/download', [AttachmentController::class, 'download'])->name('attachments.download');
        Route::delete('/attachments/{attachment}', [AttachmentController::class, 'destroy'])->name('attachments.destroy');
        Route::get('/task/{task}/dependencies/candidates', [TaskDependencyController::class, 'candidates'])->name('task.dependencies.candidates');
        Route::post('/task/{task}/dependencies', [TaskDependencyController::class, 'store'])->name('task.dependencies.store');
        Route::delete('/task/{task}/dependencies/{dependsOn}', [TaskDependencyController::class, 'destroy'])->name('task.dependencies.destroy');
        Route::post('/task/{task}/checklist-items', [ChecklistItemController::class, 'store'])->name('checklistItems.store');
        Route::patch('/checklist-items/{checklistItem}', [ChecklistItemController::class, 'update'])->name('checklistItems.update');
        Route::delete('/checklist-items/{checklistItem}', [ChecklistItemController::class, 'destroy'])->name('checklistItems.destroy');
        Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
        Route::post('/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
        Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.readAll');
    });

    // Upload de fichiers : plus coûteux (écriture disque), plafonné plus bas.
    Route::post('/task/{task}/attachments', [AttachmentController::class, 'store'])
        ->middleware('throttle:20,1')
        ->name('attachments.store');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
