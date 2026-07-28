<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $notifications = $request->user()->notifications()->latest()->take(20)->get();

        // Filet de sécurité : la suppression d'une tâche nettoie déjà ses
        // notifications (voir Task::booted()), mais on exclut ici toute
        // notification orpheline qui aurait quand même survécu, plutôt que
        // de proposer un clic qui n'ouvrirait rien.
        $existingTaskIds = Task::whereIn('id', $notifications->pluck('data.task_id'))->pluck('id');

        $data = $notifications
            ->filter(fn ($notification) => $existingTaskIds->contains($notification->data['task_id']))
            ->map(fn ($notification) => [
                'id' => $notification->id,
                'kind' => $notification->data['kind'],
                'message' => $notification->data['message'],
                'task_id' => $notification->data['task_id'],
                'read' => $notification->read_at !== null,
                'created_at' => $notification->created_at->format('Y-m-d H:i'),
            ])
            ->values();

        return response()->json(['data' => $data]);
    }

    public function markAsRead(Request $request, string $notification)
    {
        $request->user()->notifications()->findOrFail($notification)->markAsRead();

        return response()->noContent();
    }

    public function markAllAsRead(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return response()->noContent();
    }
}
