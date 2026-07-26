<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $notifications = $request->user()->notifications()->latest()->take(20)->get()->map(fn ($notification) => [
            'id' => $notification->id,
            'kind' => $notification->data['kind'],
            'message' => $notification->data['message'],
            'task_id' => $notification->data['task_id'],
            'read' => $notification->read_at !== null,
            'created_at' => $notification->created_at->format('Y-m-d H:i'),
        ]);

        return response()->json(['data' => $notifications]);
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
