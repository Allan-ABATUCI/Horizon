<?php

namespace App\Http\Requests;

use App\Models\Task;
use App\Rules\AssignableUser;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'description' => ['nullable', 'string', 'max:10000'],
            'status' => ['required', 'in:en attente,en cours,terminé'],
            'priority' => ['required', 'in:basse,moyenne,haute'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'assigned_user_id' => ['required', 'integer', 'exists:users,id', new AssignableUser($this->integer('project_id'), $this->user()->id)],
            'project_id' => ['required', 'integer', Rule::exists('project_user', 'project_id')->where('user_id', $this->user()->id)],
            'image' => ['nullable', 'image', 'max:2048'],
        ];
    }
}
