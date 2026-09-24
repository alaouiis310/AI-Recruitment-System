<?php

namespace App\Http\Requests\Entretien;

use App\Enums\ModeEntretien;
use App\Enums\ResultatEntretien;
use Illuminate\Validation\Rule;

/** Mise à jour partielle : mêmes règles qu'à la planification, en « sometimes ». */
class ModifierEntretienRequest extends PlanifierEntretienRequest
{
    public function rules(): array
    {
        return [
            'date'  => ['sometimes', 'date'],
            'heure' => ['sometimes', 'date_format:H:i'],
            'mode'  => ['sometimes', Rule::enum(ModeEntretien::class)],

            'lien_si_online' => ['sometimes', 'nullable', 'url', 'max:255'],
            'commentaire'    => ['sometimes', 'nullable', 'string', 'max:2000'],
            'resultat'       => ['sometimes', Rule::enum(ResultatEntretien::class)],
        ];
    }
}
