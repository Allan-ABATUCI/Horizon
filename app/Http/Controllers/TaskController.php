<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Http\Requests\UpdateTaskStatusRequest;
use App\Models\Task;
use Illuminate\Support\Facades\Storage;

class TaskController extends Controller
{
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

        return redirect()->route('dashboard')->with('success', 'Tâche créée.');
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

        return redirect()->route('dashboard')->with('success', 'Tâche modifiée.');
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

        return redirect()->route('dashboard')->with('success', 'Tâche supprimée.');
    }
}
