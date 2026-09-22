<?php

namespace App\Http\Requests\TestTechnique;

use Illuminate\Foundation\Http\FormRequest;

class SynchroniserTestsOffreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'tests'   => ['present', 'array'],
            'tests.*' => ['integer', 'distinct', 'exists:tests_techniques,id_test'],
        ];
    }

    public function messages(): array
    {
        return [
            'tests.present'  => 'La liste des tests est obligatoire, même vide.',
            'tests.*.exists' => "Un des tests sélectionnés n'existe pas.",
            'tests.*.distinct' => 'Un test ne peut être rattaché qu’une fois à une offre.',
        ];
    }
}
