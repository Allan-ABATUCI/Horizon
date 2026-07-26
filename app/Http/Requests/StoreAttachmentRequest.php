<?php

namespace App\Http\Requests;

use App\Models\Attachment;
use Illuminate\Foundation\Http\FormRequest;

class StoreAttachmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', [Attachment::class, $this->route('task')]) ?? false;
    }

    private const ALLOWED_EXTENSIONS = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'zip', 'png', 'jpg', 'jpeg', 'gif', 'webp', 'txt', 'csv'];

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'max:2048',
                // "mimes" seul se base sur le contenu détecté (sniffing), pas sur le
                // nom donné par le client : un fichier texte renommé "malware.exe"
                // passerait ce filtre seul. On vérifie donc aussi explicitement
                // l'extension du nom original, en plus du contenu détecté.
                'mimes:'.implode(',', self::ALLOWED_EXTENSIONS),
                function (string $attribute, $value, \Closure $fail) {
                    $extension = strtolower($value->getClientOriginalExtension());
                    if (! in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
                        $fail("Ce type de fichier n'est pas autorisé.");
                    }
                },
            ],
        ];
    }
}
