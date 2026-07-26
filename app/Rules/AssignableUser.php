<?php

namespace App\Rules;

use App\Models\Project;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\DB;

/**
 * Le créateur d'un projet peut assigner une tâche à n'importe qui (ce qui
 * l'ajoute comme membre — c'est le mécanisme d'invitation) ; un simple membre
 * ne peut assigner qu'à quelqu'un déjà membre du projet, pour ne pas pouvoir
 * étendre l'appartenance au projet à son insu.
 */
class AssignableUser implements ValidationRule
{
    public function __construct(
        private readonly ?int $projectId,
        private readonly int $actingUserId,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $project = Project::find($this->projectId);

        if (! $project || $project->created_by === $this->actingUserId) {
            return;
        }

        $isAlreadyMember = DB::table('project_user')
            ->where('project_id', $project->id)
            ->where('user_id', $value)
            ->exists();

        if (! $isAlreadyMember) {
            $fail("Seul le créateur du projet peut assigner une tâche à quelqu'un qui n'en est pas encore membre.");
        }
    }
}
