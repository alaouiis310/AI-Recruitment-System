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
            // RG22 — le CV est un PDF. La limite de 10 Mo correspond à
            // upload_max_filesize du conteneur ; au-delà, PHP rejette la
            // requête avant d'atteindre la validation.
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
