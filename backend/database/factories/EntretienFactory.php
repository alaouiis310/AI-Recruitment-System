<?php

namespace Database\Factories;

use App\Enums\ModeEntretien;
use App\Enums\ResultatEntretien;
use App\Models\Candidature;
use App\Models\Entretien;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Entretien> */
class EntretienFactory extends Factory
{
    public function definition(): array
    {
        return [
            'id_candidature' => Candidature::factory(),
            'date'           => now()->addWeek()->toDateString(),
            'heure'          => '10:00',
            'mode'           => ModeEntretien::Visio,
            'lien_si_online' => 'https://meet.example.ma/'.fake()->uuid(),
            'commentaire'    => null,
            'resultat'       => ResultatEntretien::EnAttente,
        ];
    }

    /** Entretien portant sur une candidature donnée (RG35). */
    public function pour(Candidature $candidature): static
    {
        return $this->state(fn () => ['id_candidature' => $candidature->id_candidature]);
    }

    public function presentiel(): static
    {
        return $this->state(fn () => [
            'mode'           => ModeEntretien::Presentiel,
            'lien_si_online' => null,
        ]);
    }

    /** Entretien déjà tenu, avec son issue (RG36). */
    public function tenu(ResultatEntretien $resultat = ResultatEntretien::Favorable): static
    {
        return $this->state(fn () => [
            'date'     => now()->subWeek()->toDateString(),
            'resultat' => $resultat,
        ]);
    }
}
