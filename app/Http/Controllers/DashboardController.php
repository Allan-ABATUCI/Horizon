<?php

namespace App\Http\Controllers;

use App\Http\Resources\TaskResource;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $userId = auth()->id();

        $tasks = Task::query()
            ->where(fn ($q) => $q->where('assigned_user_id', $userId)->orWhere('created_by', $userId))
            ->with(['project', 'assignedUser', 'creator', 'editor'])
            ->orderBy('end_date')
            ->get();

        return Inertia::render('dashboard', [
            'tasks' => TaskResource::collection($tasks),
            'projects' => Project::query()->orderBy('name')->get(['id', 'name']),
            'users' => User::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
