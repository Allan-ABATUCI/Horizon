<?php

namespace App\Http\Requests;

use App\Rules\DependenciesResolved;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTaskStatusRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('task')) ?? false;
    }

    /**
     * Utilisé par le Kanban (drag-and-drop) : seul le statut change, pour le
     * créateur comme pour l'assigné — contrairement à UpdateTaskRequest qui
     * exige tous les champs pour le créateur.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', 'in:en attente,en cours,terminé', new DependenciesResolved($this->route('task'))],
        ];
    }
}
