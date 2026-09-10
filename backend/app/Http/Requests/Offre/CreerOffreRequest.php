<?php

namespace App\Http\Requests\Offre;

use App\Enums\ImportanceCompetence;
use App\Enums\NiveauCompetence;
use App\Enums\NiveauEtude;
use App\Enums\StatutOffre;
use App\Enums\TypeContrat;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

class CreerOffreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            // RG15 — titre, description, type de contrat, localisation, statut.
            'titre'          => ['required', 'string', 'max:150'],
            'description'    => ['required', 'string'],
            'type_contrat'   => ['required', Rule::enum(TypeContrat::class)],
            'localisation'   => ['required', 'string', 'max:100'],
            'salaire'        => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'experience_min' => ['nullable', 'numeric', 'min:0', 'max:60'],
            'niveau_etude'   => ['nullable', Rule::enum(NiveauEtude::class)],
            'statut'         => ['sometimes', Rule::enum(StatutOffre::class)],

            // RG16/RG17 — dates de publication et d'expiration.
            'date_publication' => ['sometimes', 'date'],
            'date_expiration'  => ['nullable', 'date', 'after_or_equal:date_publication'],

            // RG11 — le département doit relever de l'entreprise du recruteur.
            'id_departement' => ['required', 'integer', $this->regleDepartement()],

            // RG19/RG21 — compétences requises et leurs attributs de pivot.
            'competences'                 => ['sometimes', 'array'],
            'competences.*.id_competence' => ['required', 'integer', 'distinct', 'exists:competences,id_competence'],
            'competences.*.niveau_requis' => ['required', Rule::enum(NiveauCompetence::class)],
            'competences.*.importance'    => ['required', Rule::enum(ImportanceCompetence::class)],
        ];
    }

    /**
     * RG9/RG11 — un recruteur ne publie que dans les départements de son
     * entreprise ; l'administrateur n'est pas restreint.
     */
    protected function regleDepartement(): Exists
    {
        $regle = Rule::exists('departements', 'id_departement');

        $recruteur = $this->user()?->recruteur;

        if ($recruteur !== null && ! $this->user()->estAdministrateur()) {
            $regle->where('id_entreprise', $recruteur->id_entreprise);
        }

        return $regle;
    }

    public function messages(): array
    {
        return [
            'titre.required'          => "L'intitulé de l'offre est obligatoire.",
            'description.required'    => 'La description de l\'offre est obligatoire.',
            'localisation.required'   => 'La localisation est obligatoire.',
            'type_contrat.required'   => 'Le type de contrat est obligatoire.',
            'type_contrat.enum'       => "Ce type de contrat n'existe pas.",
            'niveau_etude.enum'       => "Ce niveau d'études n'existe pas.",
            'statut.enum'             => "Ce statut d'offre n'existe pas.",
            'id_departement.required' => 'Le département de rattachement est obligatoire.',
            'id_departement.exists'   => "Ce département n'appartient pas à votre entreprise.",
            'date_expiration.after_or_equal' => "La date d'expiration doit suivre la date de publication.",
            'competences.*.id_competence.exists'   => "Une des compétences sélectionnées n'existe pas.",
            'competences.*.id_competence.distinct' => 'Une compétence ne peut être exigée qu\'une fois par offre.',
            'competences.*.niveau_requis.required' => 'Chaque compétence exige un niveau minimal.',
            'competences.*.importance.required'    => 'Chaque compétence exige une importance.',
        ];
    }
}
