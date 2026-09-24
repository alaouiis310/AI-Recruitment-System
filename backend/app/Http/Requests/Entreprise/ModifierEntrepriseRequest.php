<?php

namespace App\Http\Requests\Entreprise;

use Illuminate\Foundation\Http\FormRequest;

class ModifierEntrepriseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'nom'         => ['sometimes', 'string', 'max:150'],
            'secteur'     => ['sometimes', 'nullable', 'string', 'max:100'],
            'adresse'     => ['sometimes', 'nullable', 'string', 'max:255'],
            'ville'       => ['sometimes', 'nullable', 'string', 'max:100'],
            'site_web'    => ['sometimes', 'nullable', 'url', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'site_web.url' => "L'adresse du site web n'est pas valide.",
        ];
    }
}
