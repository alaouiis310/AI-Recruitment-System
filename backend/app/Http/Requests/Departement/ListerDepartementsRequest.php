<?php

namespace App\Http\Requests\Departement;

use Illuminate\Foundation\Http\FormRequest;

class ListerDepartementsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'page'      => ['sometimes', 'integer', 'min:1'],
            'per_page'  => ['sometimes', 'integer', 'min:1', 'max:100'],
            'recherche' => ['sometimes', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'per_page.max' => 'Le nombre de résultats par page ne peut pas dépasser 100.',
            'per_page.min' => 'Le nombre de résultats par page doit être au moins 1.',
        ];
    }
}
