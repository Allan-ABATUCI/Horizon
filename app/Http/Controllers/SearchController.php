<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function index(Request $request)
    {
        $term = trim((string) $request->query('q', ''));
        $userId = auth()->id();

        if (mb_strlen($term) < 2) {
            return response()->json(['projects' => [], 'tasks' => []]);
        }

        $projects = Project::query()
            ->whereHas('members', fn ($q) => $q->where('user_id', $userId))
            ->where(fn ($q) => $q->where('name', 'like', "%{$term}%")->orWhere('description', 'like', "%{$term}%"))
            ->orderBy('name')
            ->limit(5)
            ->get(['id', 'name']);

        $tasks = Task::query()
            ->whereHas('project.members', fn ($q) => $q->where('user_id', $userId))
            ->where(fn ($q) => $q->where('name', 'like', "%{$term}%")->orWhere('description', 'like', "%{$term}%"))
            ->orderBy('name')
            ->limit(8)
            ->get(['id', 'name', 'project_id']);

        return response()->json(['projects' => $projects, 'tasks' => $tasks]);
    }
}
