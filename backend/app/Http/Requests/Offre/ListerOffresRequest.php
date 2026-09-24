<?php

namespace App\Http\Requests\Offre;

use App\Enums\StatutOffre;
use App\Enums\TypeContrat;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListerOffresRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'page'           => ['sometimes', 'integer', 'min:1'],
            'per_page'       => ['sometimes', 'integer', 'min:1', 'max:100'],
            'mots_cles'      => ['sometimes', 'string', 'max:150'],
            'localisation'   => ['sometimes', 'string', 'max:100'],
            'type_contrat'   => ['sometimes', Rule::enum(TypeContrat::class)],
            'id_departement' => ['sometimes', 'integer', 'exists:departements,id_departement'],

            // Le filtre par statut n'a de sens que sur la liste du recruteur (RG18).
            'statut'         => ['sometimes', Rule::enum(StatutOffre::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'per_page.max'          => 'Le nombre de résultats par page ne peut pas dépasser 100.',
            'type_contrat.enum'     => "Ce type de contrat n'existe pas.",
            'statut.enum'           => "Ce statut d'offre n'existe pas.",
            'id_departement.exists' => "Le département sélectionné n'existe pas.",
        ];
    }
}
