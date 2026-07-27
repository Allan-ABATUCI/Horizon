<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateChecklistItemRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('checklistItem')) ?? false;
    }

    /**
     * Seul l'état cochée/non cochée est modifiable après création — un
     * intitulé mal saisi se corrige en supprimant puis recréant l'élément,
     * comme pour les commentaires.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'is_done' => ['required', 'boolean'],
        ];
    }
}
