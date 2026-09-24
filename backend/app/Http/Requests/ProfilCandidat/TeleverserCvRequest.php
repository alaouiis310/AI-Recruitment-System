<?php

namespace App\Http\Requests\ProfilCandidat;

use Illuminate\Foundation\Http\FormRequest;

class TeleverserCvRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->estCandidat() ?? false;
    }

    public function rules(): array
    {
        return [
            // Le CV est un PDF (RG22).
            'cv' => ['required', 'file', 'mimes:pdf', 'max:10240'],
        ];
    }

    public function messages(): array
    {
        return [
            'cv.required' => 'Aucun fichier reçu.',
            'cv.mimes'    => 'Le CV doit être un fichier PDF.',
            'cv.max'      => 'Le CV ne doit pas dépasser 10 Mo.',
        ];
    }

    public function attributes(): array
    {
        return ['cv' => 'CV'];
    }
}
