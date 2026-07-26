<?php

namespace App\Http\Requests;

use App\Rules\ValidDependency;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTaskDependencyRequest extends FormRequest
{
    /**
     * Comme pour les autres champs structurels (assigné, dates, priorité),
     * seul le créateur de la tâche gère ses dépendances.
     */
    public function authorize(): bool
    {
        return $this->user()?->id === $this->route('task')->created_by;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $task = $this->route('task');

        return [
            'depends_on_id' => [
                'required',
                'integer',
                'exists:tasks,id',
                Rule::unique('task_dependencies', 'depends_on_id')->where('task_id', $task->id),
                new ValidDependency($task),
            ],
        ];
    }
}
