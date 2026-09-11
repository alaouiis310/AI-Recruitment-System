<?php

namespace App\Http\Requests\Competence;

use App\Enums\CategorieCompetence;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreerCompetenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            // RG20/RG25 — le nom identifie la compétence dans le référentiel.
            'nom'         => ['required', 'string', 'max:100', 'unique:competences,nom'],
            'categorie'   => ['required', Rule::enum(CategorieCompetence::class)],
            'description' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'nom.required'       => 'Le nom de la compétence est obligatoire.',
            'nom.unique'         => 'Cette compétence existe déjà dans le référentiel.',
            'categorie.required' => 'La catégorie est obligatoire.',
            'categorie.enum'     => 'Cette catégorie de compétence n\'existe pas.',
        ];
    }
}
