<?php

namespace App\Http\Requests\TestTechnique;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * RG45 — le score obtenu ne peut pas dépasser le barème du test.
 */
class EnregistrerScoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'score_obtenu' => ['required', 'integer', 'min:0'],
            'date_passage' => ['sometimes', 'date', 'before_or_equal:today'],
            'commentaire'  => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validateur) {
                $resultat = $this->route('resultat');
                $maximum  = $resultat?->test?->score_max;

                if ($maximum !== null && (int) $this->input('score_obtenu') > $maximum) {
                    $validateur->errors()->add(
                        'score_obtenu',
                        "Le score ne peut pas dépasser le barème du test ({$maximum}).",
                    );
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'score_obtenu.required' => 'Le score obtenu est obligatoire.',
            'date_passage.before_or_equal' => 'La date de passage ne peut pas être dans le futur.',
        ];
    }
}
