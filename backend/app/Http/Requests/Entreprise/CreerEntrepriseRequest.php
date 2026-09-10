<?php

namespace App\Http\Requests\Entreprise;

use Illuminate\Foundation\Http\FormRequest;

class CreerEntrepriseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'nom'         => ['required', 'string', 'max:150'],
            'secteur'     => ['nullable', 'string', 'max:100'],
            'adresse'     => ['nullable', 'string', 'max:255'],
            'ville'       => ['nullable', 'string', 'max:100'],
            'site_web'    => ['nullable', 'url', 'max:255'],
            'description' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'nom.required'  => "Le nom de l'entreprise est obligatoire.",
            'site_web.url'  => "L'adresse du site web n'est pas valide.",
        ];
    }

    public function attributes(): array
    {
        return [
            'nom'         => 'nom',
            'secteur'     => "secteur d'activité",
            'ville'       => 'ville',
            'site_web'    => 'site web',
            'description' => 'description',
        ];
    }
}
