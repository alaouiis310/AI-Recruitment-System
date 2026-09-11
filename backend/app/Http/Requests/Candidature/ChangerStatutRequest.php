<?php

namespace App\Http\Requests\Candidature;

use App\Enums\StatutCandidature;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * RG32 — changement de statut. La validité de la transition elle-même est
 * tranchée par le service, qui connaît le statut de départ.
 */
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
