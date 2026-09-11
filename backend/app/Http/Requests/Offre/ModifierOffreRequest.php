<?php

namespace App\Http\Requests\Offre;

use App\Enums\ImportanceCompetence;
use App\Enums\NiveauCompetence;
use App\Enums\NiveauEtude;
use App\Enums\StatutOffre;
use App\Enums\TypeContrat;
use Illuminate\Validation\Rule;

/**
 * Mise à jour partielle : les règles de la création reprises en « sometimes ».
 * La règle sur le département est héritée, un recruteur ne pouvant pas
 * déplacer une offre hors des départements de son entreprise (RG9, RG11).
 */
class ModifierOffreRequest extends CreerOffreRequest
{
    /**
     * RG16/RG17 — sur une mise à jour partielle, la date d'expiration se
     * compare à la date de publication déjà enregistrée lorsque celle-ci
     * n'est pas renvoyée : sans cela, la règle comparerait à un champ absent.
     */
    protected function prepareForValidation(): void
    {
        $offre = $this->route('offre');

        if ($offre !== null && $this->has('date_expiration') && ! $this->has('date_publication')) {
            $this->merge([
                'date_publication' => $offre->date_publication?->toDateString(),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'titre'          => ['sometimes', 'string', 'max:150'],
            'description'    => ['sometimes', 'string'],
            'type_contrat'   => ['sometimes', Rule::enum(TypeContrat::class)],
            'localisation'   => ['sometimes', 'string', 'max:100'],
            'salaire'        => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'experience_min' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:60'],
            'niveau_etude'   => ['sometimes', 'nullable', Rule::enum(NiveauEtude::class)],
            'statut'         => ['sometimes', Rule::enum(StatutOffre::class)],

            'date_publication' => ['sometimes', 'date'],
            'date_expiration'  => ['sometimes', 'nullable', 'date', 'after_or_equal:date_publication'],

            'id_departement' => ['sometimes', 'integer', $this->regleDepartement()],

            // Absent : compétences inchangées. Présent, même vide : remplacement.
            'competences'                 => ['sometimes', 'array'],
            'competences.*.id_competence' => ['required', 'integer', 'distinct', 'exists:competences,id_competence'],
            'competences.*.niveau_requis' => ['required', Rule::enum(NiveauCompetence::class)],
            'competences.*.importance'    => ['required', Rule::enum(ImportanceCompetence::class)],
        ];
    }
}
