<?php

namespace App\Http\Requests\Ia;

use Illuminate\Foundation\Http\FormRequest;

/** Évaluation d'un CV isolé face à une description de poste. */
class EvaluerCvRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'cv_file'         => ['required', 'file', 'mimes:pdf', 'max:10240'],
            'job_description' => ['required', 'string', 'max:20000'],
            'job_title'       => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'cv_file.required'         => 'Le CV est obligatoire.',
            'cv_file.mimes'            => 'Le CV doit être un fichier PDF.',
            'cv_file.max'              => 'Le CV ne doit pas dépasser 10 Mo.',
            'job_description.required' => 'La description du poste est obligatoire.',
        ];
    }
}
