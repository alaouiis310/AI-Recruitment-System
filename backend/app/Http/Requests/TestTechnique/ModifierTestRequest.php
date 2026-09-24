<?php

namespace App\Http\Requests\TestTechnique;

class ModifierTestRequest extends CreerTestRequest
{
    public function rules(): array
    {
        return [
            'titre'       => ['sometimes', 'string', 'max:150'],
            'description' => ['sometimes', 'nullable', 'string'],
            'duree'       => ['sometimes', 'integer', 'min:1', 'max:480'],
            'score_max'   => ['sometimes', 'integer', 'min:1', 'max:1000'],
        ];
    }
}
