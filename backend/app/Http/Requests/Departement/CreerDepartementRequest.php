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
        // RG9 — l'entreprise est celle de l'URL : le paramètre est déjà résolu
        // en modèle par SubstituteBindings.
        $idEntreprise = $this->route('entreprise')?->id_entreprise;

        return [
            'nom' => [
                'required',
                'string',
                'max:100',
                // RG8 — doublon rattrapé ici pour renvoyer un 422 plutôt que
                // de laisser remonter la violation de contrainte en 500.
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
