<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTaskDependencyRequest;
use App\Models\Task;
use Illuminate\Http\Request;

class TaskDependencyController extends Controller
{
    /**
     * Tâches du même projet pouvant être ajoutées comme dépendance — exclut
     * la tâche elle-même et celles déjà en dépendance.
     */
    public function candidates(Request $request, Task $task)
    {
        $this->authorize('view', $task);

        $term = mb_substr(trim((string) $request->query('q', '')), 0, 100);
        $excludedIds = $task->dependsOn()->pluck('tasks.id')->push($task->id);

        $tasks = Task::query()
            ->where('project_id', $task->project_id)
            ->whereNotIn('id', $excludedIds)
            ->when($term !== '', fn ($q) => $q->where('name', 'like', '%'.$term.'%'))
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'name', 'status']);

        return response()->json($tasks);
    }

    public function store(StoreTaskDependencyRequest $request, Task $task)
    {
        $dependsOnId = $request->validated('depends_on_id');
        $task->dependsOn()->attach($dependsOnId);

        return response()->json(Task::find($dependsOnId, ['id', 'name', 'status']));
    }

    public function destroy(Task $task, Task $dependsOn)
    {
        abort_unless(auth()->id() === $task->created_by, 403);

        $task->dependsOn()->detach($dependsOn->id);

        return response()->noContent();
    }
}
