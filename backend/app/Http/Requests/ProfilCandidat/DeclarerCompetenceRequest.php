<?php

namespace App\Http\Requests\ProfilCandidat;

use App\Enums\NiveauCompetence;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * RG24/RG26 — déclaration ou mise à jour d'une seule compétence.
 */
class DeclarerCompetenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->estCandidat() ?? false;
    }

    public function rules(): array
    {
        return [
            'id_competence'     => ['required', 'integer', 'exists:competences,id_competence'],
            'niveau'            => ['required', Rule::enum(NiveauCompetence::class)],
            'annees_experience' => ['nullable', 'numeric', 'min:0', 'max:60'],
        ];
    }

    public function messages(): array
    {
        return [
            'id_competence.exists' => "Cette compétence n'existe pas dans le référentiel.",
            'niveau.required'      => 'Le niveau de maîtrise est obligatoire.',
        ];
    }
}
