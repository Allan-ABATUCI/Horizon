<?php

namespace App\Http\Requests;

use App\Models\Task;
use Illuminate\Foundation\Http\FormRequest;

class StoreTaskRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', Task::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
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
}
