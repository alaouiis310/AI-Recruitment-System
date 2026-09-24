<?php

namespace App\Http\Requests\Departement;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ModifierDepartementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $departement = $this->route('departement');

        return [
            'nom' => [
                'sometimes',
                'string',
                'max:100',
                Rule::unique('departements', 'nom')
                    ->where('id_entreprise', $departement?->id_entreprise)
                    ->ignore($departement?->id_departement, 'id_departement'),
            ],
            'description' => ['sometimes', 'nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'nom.unique' => 'Cette entreprise possède déjà un département portant ce nom.',
        ];
    }
}
