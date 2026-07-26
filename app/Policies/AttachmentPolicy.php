<?php

namespace App\Policies;

use App\Models\Attachment;
use App\Models\Task;
use App\Models\User;

class AttachmentPolicy
{
    /**
     * Ajouter une pièce jointe est réservé aux membres du projet de la
     * tâche, cohérent avec CommentPolicy::create.
     */
    public function create(User $user, Task $task): bool
    {
        return $task->project->members()->where('user_id', $user->id)->exists();
    }

    public function delete(User $user, Attachment $attachment): bool
    {
        return $user->id === $attachment->user_id;
    }
}
