<?php

namespace Database\Factories;

use App\Enums\StatutCandidature;
use App\Models\Candidat;
use App\Models\Candidature;
use App\Models\OffreEmploi;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Candidature> */
class CandidatureFactory extends Factory
{
    public function definition(): array
    {
        return [
            'id_candidat'       => Candidat::factory(),
            'id_offre'          => OffreEmploi::factory(),
            'date_candidature'  => now()->toDateString(),
            'lettre_motivation' => fake()->paragraph(3),
            'statut'            => StatutCandidature::EnAttente,
            'score_final'       => null,
        ];
    }

    /** RG28/RG29 — candidature d'un candidat donné sur une offre donnée. */
    public function pour(Candidat $candidat, OffreEmploi $offre): static
    {
        return $this->state(fn () => [
            'id_candidat' => $candidat->id_candidat,
            'id_offre'    => $offre->id_offre,
        ]);
    }

    /** RG32 — candidature à un statut donné. */
    public function statut(StatutCandidature $statut): static
    {
        return $this->state(fn () => ['statut' => $statut]);
    }

    /** RG43 — candidature déjà analysée, avec son score de compatibilité. */
    public function score(float $score): static
    {
        return $this->state(fn () => ['score_final' => $score]);
    }
}
