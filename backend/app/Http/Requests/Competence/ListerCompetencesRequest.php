<?php

namespace App\Http\Requests\Competence;

use App\Enums\CategorieCompetence;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListerCompetencesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'page'      => ['sometimes', 'integer', 'min:1'],
            'per_page'  => ['sometimes', 'integer', 'min:1', 'max:100'],
            'recherche' => ['sometimes', 'string', 'max:100'],
            'categorie' => ['sometimes', Rule::enum(CategorieCompetence::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'per_page.max'   => 'Le nombre de résultats par page ne peut pas dépasser 100.',
            'categorie.enum' => 'Cette catégorie de compétence n\'existe pas.',
        ];
    }
}
