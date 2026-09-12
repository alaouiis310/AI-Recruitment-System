<?php

namespace App\Http\Requests\TestTechnique;

use Illuminate\Foundation\Http\FormRequest;

class CreerTestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'titre'       => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'duree'       => ['required', 'integer', 'min:1', 'max:480'],
            'score_max'   => ['sometimes', 'integer', 'min:1', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'titre.required' => 'Le titre du test est obligatoire.',
            'duree.required' => 'La durée du test est obligatoire.',
            'duree.max'      => 'La durée ne peut pas dépasser 480 minutes.',
        ];
    }
}
