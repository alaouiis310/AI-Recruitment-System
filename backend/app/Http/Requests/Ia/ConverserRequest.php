<?php

namespace App\Http\Requests\Ia;

use Illuminate\Foundation\Http\FormRequest;

class ConverserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'message' => ['required', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'message.required' => 'Le message est obligatoire.',
            'message.max'      => 'Le message ne doit pas dépasser 2000 caractères.',
        ];
    }
}
