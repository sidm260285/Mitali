<?php

namespace App\Http\Requests;

use App\Models\TrainerDocument;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTrainerDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $trainer = $this->route('trainer');

        return ($this->user()?->isAdmin() ?? false) && $trainer->isActive();
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(TrainerDocument::uploadableTypes())],
            'file' => ['required', 'file', 'max:'.((int) config('trainer.max_file_size_mb') * 1024)],
        ];
    }
}
