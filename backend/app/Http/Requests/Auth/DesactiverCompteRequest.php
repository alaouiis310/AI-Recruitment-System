<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Désactivation de son propre compte, confirmée par le mot de passe.
 *
 * Un administrateur ne peut pas désactiver son propre compte : la plateforme
 * risquerait de se retrouver sans administrateur actif.
 */
class DesactiverCompteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && ! $this->user()->estAdministrateur();
    }

    public function rules(): array
    {
        return [
            'password' => ['required', 'string', 'current_password'],
        ];
    }

    public function messages(): array
    {
        return [
            'password.required'         => 'Confirmez avec votre mot de passe.',
            'password.current_password' => 'Le mot de passe est incorrect.',
        ];
    }
}
