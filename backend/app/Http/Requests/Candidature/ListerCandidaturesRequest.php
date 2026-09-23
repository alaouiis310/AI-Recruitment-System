<?php

namespace App\Http\Requests\Candidature;

use App\Enums\StatutCandidature;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListerCandidaturesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'page'     => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'statut'   => ['sometimes', Rule::enum(StatutCandidature::class)],
            'recherche' => ['sometimes', 'string', 'max:150'],

            // RG14 — n'a de sens que sur la liste du recruteur : restreindre
            // ses candidatures reçues à une de ses offres.
            'id_offre' => ['sometimes', 'integer', 'exists:offres_emploi,id_offre'],

            // Recherche par nom ou prénom du candidat (liste du recruteur).
            'recherche' => ['sometimes', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'per_page.max' => 'Le nombre de résultats par page ne peut pas dépasser 100.',
            'statut.enum'  => "Ce statut de candidature n'existe pas.",
        ];
    }
}
