<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Task $task): bool
    {
        return $task->project->members()->where('user_id', $user->id)->exists();
    }

    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Autorise le créateur (accès complet) et l'utilisateur assigné (statut
     * uniquement — restriction appliquée dans UpdateTaskRequest::rules()).
     */
    public function update(User $user, Task $task): bool
    {
        return $user->id === $task->created_by || $user->id === $task->assigned_user_id;
    }

    /**
     * Le créateur de la tâche, ou le créateur du projet (modération de son
     * propre projet), peut la supprimer.
     */
    public function delete(User $user, Task $task): bool
    {
        return $user->id === $task->created_by || $user->id === $task->project->created_by;
    }
}
