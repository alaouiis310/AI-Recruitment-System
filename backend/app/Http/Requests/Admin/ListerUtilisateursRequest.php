<?php

namespace App\Http\Requests\Admin;

use App\Enums\EtatCompte;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListerUtilisateursRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->estAdministrateur() === true;
    }

    public function rules(): array
    {
        return [
            'page'        => ['sometimes', 'integer', 'min:1'],
            'per_page'    => ['sometimes', 'integer', 'min:1', 'max:100'],
            'recherche'   => ['sometimes', 'string', 'max:150'],
            'etat_compte' => ['sometimes', Rule::enum(EtatCompte::class)],
        ];
    }
}
