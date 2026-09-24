<?php

namespace App\Http\Requests\Candidature;

use Illuminate\Foundation\Http\FormRequest;

class PostulerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->estCandidat() ?? false;
    }

    public function rules(): array
    {
        return [
            // L'offre visée (RG29).
            'id_offre'          => ['required', 'integer', 'exists:offres_emploi,id_offre'],
            'lettre_motivation' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        return [
            'id_offre.required'      => "L'offre visée est obligatoire.",
            'id_offre.exists'        => "Cette offre n'existe pas.",
            'lettre_motivation.max'  => 'La lettre de motivation ne doit pas dépasser 5000 caractères.',
        ];
    }
}
