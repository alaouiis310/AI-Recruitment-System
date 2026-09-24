<?php

namespace App\Http\Requests\Departement;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreerDepartementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        // L'entreprise est celle de l'URL (RG9).
        $idEntreprise = $this->route('entreprise')?->id_entreprise;

        return [
            'nom' => [
                'required',
                'string',
                'max:100',
                Rule::unique('departements', 'nom')->where('id_entreprise', $idEntreprise),
            ],
            'description' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'nom.required' => 'Le nom du département est obligatoire.',
            'nom.unique'   => 'Cette entreprise possède déjà un département portant ce nom.',
        ];
    }
}
