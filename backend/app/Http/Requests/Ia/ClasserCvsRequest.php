<?php

namespace App\Http\Requests\Ia;

use Illuminate\Foundation\Http\FormRequest;

/** Classement de plusieurs CV face à une même description de poste. */
class ClasserCvsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'cv_files'        => ['required', 'array', 'min:1', 'max:20'],
            'cv_files.*'      => ['file', 'mimes:pdf', 'max:10240'],
            'job_description' => ['required', 'string', 'max:20000'],
            'job_title'       => ['nullable', 'string', 'max:255'],
            'top'             => ['nullable', 'integer', 'min:1', 'max:20'],
        ];
    }

    public function messages(): array
    {
        return [
            'cv_files.required'        => 'Au moins un CV est obligatoire.',
            'cv_files.max'             => 'Vingt CV au maximum par classement.',
            'cv_files.*.mimes'         => 'Chaque CV doit être un fichier PDF.',
            'cv_files.*.max'           => 'Chaque CV ne doit pas dépasser 10 Mo.',
            'job_description.required' => 'La description du poste est obligatoire.',
        ];
    }
}
