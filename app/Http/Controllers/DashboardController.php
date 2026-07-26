<?php

namespace App\Http\Controllers;

use App\Http\Resources\TaskResource;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $userId = auth()->id();
        $scope = $request->input('scope') === 'all' ? 'all' : 'mine';

        $tasks = Task::query()
            ->whereHas('project.members', fn ($q) => $q->where('user_id', $userId))
            ->when($scope === 'mine', fn ($q) => $q->where(fn ($q2) => $q2->where('assigned_user_id', $userId)->orWhere('created_by', $userId)))
            ->when($request->filled('project_id'), fn ($q) => $q->where('project_id', $request->integer('project_id')))
            ->with(['project', 'assignedUser', 'creator', 'editor', 'dependsOn', 'blocks'])
            ->orderBy('end_date')
            ->get();

        $projects = Project::query()
            ->whereHas('members', fn ($q) => $q->where('user_id', $userId))
            ->with('members:id,name')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn ($project) => [
                'id' => $project->id,
                'name' => $project->name,
                'members' => $project->members->map(fn ($member) => ['id' => $member->id, 'name' => $member->name])->values(),
            ]);

        return Inertia::render('dashboard', [
            'tasks' => TaskResource::collection($tasks),
            'projects' => $projects,
            'filters' => [
                'scope' => $scope,
                'project_id' => $request->integer('project_id') ?: null,
            ],
        ]);
    }
}
