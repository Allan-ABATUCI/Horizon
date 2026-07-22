<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Http\Requests\UpdateTaskStatusRequest;
use App\Http\Resources\TaskResource;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class TaskController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Task::class);

        $tasks = Task::query()
            ->with(['project', 'assignedUser', 'creator', 'editor'])
            ->when($request->filled('project_id'), fn ($q) => $q->where('project_id', $request->integer('project_id')))
            ->paginate(10)
            ->onEachSide(1);

        return Inertia::render('task/Index', [
            'tasks' => TaskResource::collection($tasks),
            'projectId' => $request->integer('project_id') ?: null,
            'projects' => Project::query()->orderBy('name')->get(['id', 'name']),
            'users' => User::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * Non utilisée : la création se fait via une modale sur task/Index.
     */
    public function create()
    {
        return redirect()->route('task.index');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTaskRequest $request)
    {
        $validated = $request->validated();

        if ($request->hasFile('image')) {
            $validated['image_path'] = $request->file('image')->store('tasks', 'public');
        }
        unset($validated['image']);

        $validated['created_by'] = auth()->id();
        $validated['updated_by'] = auth()->id();

        Task::create($validated);

        return redirect()->route('task.index')->with('success', 'Tâche créée.');
    }

    /**
     * Display the specified resource.
     *
     * Non utilisée : pas de page dédiée, tout se fait depuis task/Index.
     */
    public function show(Task $task)
    {
        $this->authorize('view', $task);

        return redirect()->route('task.index');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * Non utilisée : l'édition se fait via une modale sur task/Index.
     */
    public function edit(Task $task)
    {
        $this->authorize('update', $task);

        return redirect()->route('task.index');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTaskRequest $request, Task $task)
    {
        $data = $request->validated();

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('tasks', 'public');
        }
        unset($data['image']);

        $data['updated_by'] = auth()->id();

        $task->update($data);

        return redirect()->route('task.index')->with('success', 'Tâche modifiée.');
    }

    /**
     * Update only the status of a task — utilisé par le Kanban (drag-and-drop).
     */
    public function updateStatus(UpdateTaskStatusRequest $request, Task $task)
    {
        $task->update([
            'status' => $request->validated()['status'],
            'updated_by' => auth()->id(),
        ]);

        return back();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Task $task)
    {
        $this->authorize('delete', $task);

        if ($task->image_path && ! str_starts_with($task->image_path, 'http')) {
            Storage::disk('public')->delete($task->image_path);
        }

        $task->delete();

        return redirect()->route('task.index')->with('success', 'Tâche supprimée.');
    }
}
