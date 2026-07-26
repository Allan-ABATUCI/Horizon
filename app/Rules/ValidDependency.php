<?php

namespace App\Rules;

use App\Models\Task;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\DB;

class ValidDependency implements ValidationRule
{
    public function __construct(private readonly Task $task) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $dependsOnId = (int) $value;

        if ($dependsOnId === $this->task->id) {
            $fail("Une tâche ne peut pas dépendre d'elle-même.");

            return;
        }

        $dependsOnTask = Task::find($dependsOnId);
        if (! $dependsOnTask) {
            return;
        }

        if ($dependsOnTask->project_id !== $this->task->project_id) {
            $fail("Une tâche ne peut dépendre que d'une tâche du même projet.");

            return;
        }

        if ($this->wouldCreateCycle($dependsOnId)) {
            $fail('Cette dépendance créerait un cycle entre les deux tâches.');
        }
    }

    /**
     * Ajouter task -> dependsOnId créerait un cycle si dependsOnId dépend
     * déjà (directement ou transitivement) de task.
     */
    private function wouldCreateCycle(int $dependsOnId): bool
    {
        $visited = [];
        $queue = [$dependsOnId];

        while ($queue !== []) {
            $currentId = array_shift($queue);

            if ($currentId === $this->task->id) {
                return true;
            }

            if (in_array($currentId, $visited, true)) {
                continue;
            }
            $visited[] = $currentId;

            $nextIds = DB::table('task_dependencies')->where('task_id', $currentId)->pluck('depends_on_id')->all();
            array_push($queue, ...$nextIds);
        }

        return false;
    }
}
