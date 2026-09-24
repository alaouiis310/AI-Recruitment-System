<?php

namespace App\Http\Requests\ProfilCandidat;

use App\Enums\NiveauCompetence;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Remplacement complet des compétences déclarées (RG24/RG26). */
class SynchroniserCompetencesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->estCandidat() ?? false;
    }

    public function rules(): array
    {
        return [
            'competences'                     => ['present', 'array'],
            'competences.*.id_competence'     => ['required', 'integer', 'distinct', 'exists:competences,id_competence'],
            'competences.*.niveau'            => ['required', Rule::enum(NiveauCompetence::class)],
            'competences.*.annees_experience' => ['nullable', 'numeric', 'min:0', 'max:60'],
        ];
    }

    public function messages(): array
    {
        return [
            'competences.present'                  => 'La liste des compétences est obligatoire, même vide.',
            'competences.*.id_competence.exists'   => "Une des compétences sélectionnées n'existe pas.",
            'competences.*.id_competence.distinct' => 'Une compétence ne peut être déclarée qu\'une fois.',
            'competences.*.niveau.required'        => 'Chaque compétence exige un niveau de maîtrise.',
        ];
    }
}
