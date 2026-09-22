<?php

namespace App\Http\Requests\Admin;

use App\Enums\EtatCompte;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChangerEtatCompteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->estAdministrateur() === true;
    }

    public function rules(): array
    {
        return ['etat_compte' => ['required', Rule::enum(EtatCompte::class)]];
    }

    public function messages(): array
    {
        return ['etat_compte.enum' => "Cet état de compte n'existe pas."];
    }
}
