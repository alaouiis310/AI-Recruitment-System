<?php

namespace App\Http\Requests\Candidature;

use App\Enums\StatutCandidature;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Changement de statut (RG32). */
class ChangerStatutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'statut'                => ['required', Rule::enum(StatutCandidature::class)],
            'commentaire_recruteur' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'statut.required' => 'Le nouveau statut est obligatoire.',
            'statut.enum'     => "Ce statut de candidature n'existe pas.",
        ];
    }
}
