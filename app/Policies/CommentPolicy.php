<?php

namespace App\Policies;

use App\Models\Comment;
use App\Models\Task;
use App\Models\User;

class CommentPolicy
{
    /**
     * Commenter une tâche est réservé aux membres du projet de cette tâche,
     * cohérent avec TaskPolicy::view (voir une tâche est déjà réservé aux membres).
     */
    public function create(User $user, Task $task): bool
    {
        return $task->project->members()->where('user_id', $user->id)->exists();
    }

    public function delete(User $user, Comment $comment): bool
    {
        return $user->id === $comment->user_id;
    }
}
