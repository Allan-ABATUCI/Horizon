<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreChecklistItemRequest;
use App\Http\Requests\UpdateChecklistItemRequest;
use App\Http\Resources\ChecklistItemResource;
use App\Models\ChecklistItem;
use App\Models\Task;

class ChecklistItemController extends Controller
{
    public function store(StoreChecklistItemRequest $request, Task $task)
    {
        $item = $task->checklistItems()->create(['label' => $request->validated('label')]);

        return new ChecklistItemResource($item);
    }

    public function update(UpdateChecklistItemRequest $request, ChecklistItem $checklistItem)
    {
        $checklistItem->update(['is_done' => $request->validated('is_done')]);

        return new ChecklistItemResource($checklistItem);
    }

    public function destroy(ChecklistItem $checklistItem)
    {
        $this->authorize('delete', $checklistItem);

        $checklistItem->delete();

        return response()->noContent();
    }
}
