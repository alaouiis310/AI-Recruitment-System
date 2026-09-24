<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class InscriptionRecruteurRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nom'      => ['required', 'string', 'max:100'],
            'prenom'   => ['required', 'string', 'max:100'],
            'email'    => ['required', 'email', 'max:150', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],

            'telephone' => ['nullable', 'string', 'max:20'],
            'poste'     => ['nullable', 'string', 'max:100'],

            'id_entreprise' => [
                'required_without:entreprise',
                'nullable',
                'integer',
                'exists:entreprises,id_entreprise',
            ],

            'entreprise'             => ['required_without:id_entreprise', 'nullable', 'array'],
            'entreprise.nom'         => ['required_with:entreprise', 'string', 'max:150'],
            'entreprise.secteur'     => ['nullable', 'string', 'max:100'],
            'entreprise.adresse'     => ['nullable', 'string', 'max:255'],
            'entreprise.ville'       => ['nullable', 'string', 'max:100'],
            'entreprise.site_web'    => ['nullable', 'url', 'max:255'],
            'entreprise.description' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'Cette adresse e-mail est déjà utilisée.',
            'id_entreprise.required_without' => 'Indiquez une entreprise existante ou renseignez les informations d\'une nouvelle entreprise.',
            'id_entreprise.exists' => 'L\'entreprise sélectionnée n\'existe pas.',
        ];
    }
}
