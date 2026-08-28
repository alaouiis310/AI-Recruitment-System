<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ModifierProfilRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $idUser = $this->user()->id;

        $regles = [
            'nom'    => ['sometimes', 'string', 'max:100'],
            'prenom' => ['sometimes', 'string', 'max:100'],
            'email'  => [
                'sometimes',
                'email',
                'max:150',
                Rule::unique('users', 'email')->ignore($idUser),
            ],
        ];

        if ($this->user()->estCandidat()) {
            $regles += [
                'telephone'         => ['sometimes', 'nullable', 'string', 'max:20'],
                'telephone2'        => ['sometimes', 'nullable', 'string', 'max:20'],
                'adresse'           => ['sometimes', 'nullable', 'string', 'max:255'],
                'date_naissance'    => ['sometimes', 'nullable', 'date', 'before:-16 years'],
                'diplome'           => ['sometimes', 'nullable', 'string', 'max:150'],
                'github'            => ['sometimes', 'nullable', 'url', 'max:255'],
                'linkedin'          => ['sometimes', 'nullable', 'url', 'max:255'],
                'experience_totale' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:60'],
            ];
        }

        if ($this->user()->estRecruteur()) {
            $regles += [
                'telephone' => ['sometimes', 'nullable', 'string', 'max:20'],
                'poste'     => ['sometimes', 'nullable', 'string', 'max:100'],
            ];
        }

        return $regles;
    }
}
