<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTaskRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('task')) ?? false;
    }

    /**
     * Le créateur peut modifier tous les champs ; l'utilisateur assigné
     * (autorisé par TaskPolicy::update) ne peut modifier que le statut.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $task = $this->route('task');

        if ($this->user()?->id === $task->created_by) {
            return [
                'name' => ['required', 'string', 'max:255'],
                'description' => ['nullable', 'string'],
                'status' => ['required', 'in:en attente,en cours,terminé'],
                'priority' => ['required', 'in:basse,moyenne,haute'],
                'end_date' => ['nullable', 'date'],
                'assigned_user_id' => ['required', 'exists:users,id'],
                'project_id' => ['required', 'exists:projects,id'],
                'image' => ['nullable', 'image', 'max:2048'],
            ];
        }

        return [
            'status' => ['required', 'in:en attente,en cours,terminé'],
        ];
    }
}
