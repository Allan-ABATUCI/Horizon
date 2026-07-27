<?php

namespace App\Rules;

use App\Models\Task;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class DependenciesResolved implements ValidationRule
{
    public function __construct(private readonly Task $task) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value !== 'terminé') {
            return;
        }

        $unresolved = $this->task->dependsOn()->where('status', '!=', 'terminé')->pluck('name');

        if ($unresolved->isNotEmpty()) {
            $fail("Cette tâche dépend de tâches non terminées : {$unresolved->implode(', ')}.");
        }
    }
}
