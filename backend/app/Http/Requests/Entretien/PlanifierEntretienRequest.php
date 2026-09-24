<?php

namespace App\Http\Requests\Entretien;

use App\Enums\ModeEntretien;
use App\Enums\ResultatEntretien;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PlanifierEntretienRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            // Date, heure et mode sont constitutifs de l'entretien (RG36).
            'date'  => ['required', 'date', 'after_or_equal:today'],
            'heure' => ['required', 'date_format:H:i'],
            'mode'  => ['required', Rule::enum(ModeEntretien::class)],

            // Un entretien en visioconférence suppose un lien de connexion.
            'lien_si_online' => [
                Rule::requiredIf(fn () => $this->input('mode') === ModeEntretien::Visio->value),
                'nullable', 'url', 'max:255',
            ],

            'commentaire' => ['nullable', 'string', 'max:2000'],
            'resultat'    => ['sometimes', Rule::enum(ResultatEntretien::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'date.required'           => "La date de l'entretien est obligatoire.",
            'date.after_or_equal'     => 'Un entretien ne peut pas être planifié dans le passé.',
            'heure.required'          => "L'heure de l'entretien est obligatoire.",
            'heure.date_format'       => "L'heure doit être au format HH:MM.",
            'mode.required'           => 'Le mode de déroulement est obligatoire.',
            'mode.enum'               => "Ce mode d'entretien n'existe pas.",
            'lien_si_online.required' => 'Un entretien en visioconférence exige un lien de connexion.',
            'lien_si_online.url'      => "Le lien de connexion n'est pas une adresse valide.",
        ];
    }
}
