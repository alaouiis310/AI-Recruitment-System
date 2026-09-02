<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class InscriptionCandidatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nom'        => ['required', 'string', 'max:100'],
            'prenom'     => ['required', 'string', 'max:100'],
            'email'      => ['required', 'email', 'max:150', 'unique:users,email'],
            'password'   => ['required', 'confirmed', Password::min(8)->letters()->numbers()],

            'telephone'  => ['nullable', 'string', 'max:20'],
            'telephone2' => ['nullable', 'string', 'max:20'],
            'adresse'    => ['nullable', 'string', 'max:255'],
            'date_naissance' => ['nullable', 'date', 'before:-16 years'],
            'diplome'    => ['nullable', 'string', 'max:150'],
            'github'     => ['nullable', 'url', 'max:255'],
            'linkedin'   => ['nullable', 'url', 'max:255'],
            'experience_totale' => ['nullable', 'numeric', 'min:0', 'max:60'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique'          => 'Cette adresse e-mail est déjà utilisée.',
            'password.confirmed'    => 'La confirmation du mot de passe ne correspond pas.',
            'date_naissance.before' => 'Le candidat doit être âgé d\'au moins 16 ans.',
        ];
    }

    public function attributes(): array
    {
        return [
            'nom'      => 'nom',
            'prenom'   => 'prénom',
            'email'    => 'adresse e-mail',
            'password' => 'mot de passe',
        ];
    }
}
