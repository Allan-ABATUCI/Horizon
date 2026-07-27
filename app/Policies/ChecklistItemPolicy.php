<?php

namespace App\Policies;

use App\Models\ChecklistItem;
use App\Models\Task;
use App\Models\User;

class ChecklistItemPolicy
{
    /**
     * Comme les commentaires : réservé aux membres du projet de la tâche,
     * mais sans notion d'auteur individuel — c'est une liste partagée.
     */
    public function create(User $user, Task $task): bool
    {
        return $task->project->members()->where('user_id', $user->id)->exists();
    }

    public function update(User $user, ChecklistItem $checklistItem): bool
    {
        return $checklistItem->task->project->members()->where('user_id', $user->id)->exists();
    }

    public function delete(User $user, ChecklistItem $checklistItem): bool
    {
        return $checklistItem->task->project->members()->where('user_id', $user->id)->exists();
    }
}
