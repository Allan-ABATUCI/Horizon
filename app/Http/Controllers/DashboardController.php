<?php

namespace App\Http\Controllers;

use App\Http\Resources\TaskResource;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $userId = auth()->id();
        $scope = $request->input('scope') === 'all' ? 'all' : 'mine';

        $tasks = Task::query()
            ->when($scope === 'mine', fn ($q) => $q->where(fn ($q2) => $q2->where('assigned_user_id', $userId)->orWhere('created_by', $userId)))
            ->when($request->filled('project_id'), fn ($q) => $q->where('project_id', $request->integer('project_id')))
            ->with(['project', 'assignedUser', 'creator', 'editor'])
            ->orderBy('end_date')
            ->get();

        return Inertia::render('dashboard', [
            'tasks' => TaskResource::collection($tasks),
            'projects' => Project::query()->orderBy('name')->get(['id', 'name']),
            'users' => User::query()->orderBy('name')->get(['id', 'name']),
            'filters' => [
                'scope' => $scope,
                'project_id' => $request->integer('project_id') ?: null,
            ],
        ]);
    }
}
