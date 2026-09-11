<?php

namespace App\Http\Requests\Competence;

use App\Enums\CategorieCompetence;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ModifierCompetenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $competence = $this->route('competence');

        return [
            'nom' => [
                'sometimes',
                'string',
                'max:100',
                Rule::unique('competences', 'nom')
                    ->ignore($competence?->id_competence, 'id_competence'),
            ],
            'categorie'   => ['sometimes', Rule::enum(CategorieCompetence::class)],
            'description' => ['sometimes', 'nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'nom.unique'     => 'Cette compétence existe déjà dans le référentiel.',
            'categorie.enum' => 'Cette catégorie de compétence n\'existe pas.',
        ];
    }
}
