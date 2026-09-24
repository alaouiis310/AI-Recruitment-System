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

    /** Candidature d'un candidat donné sur une offre donnée (RG28/RG29). */
    public function pour(Candidat $candidat, OffreEmploi $offre): static
    {
        return $this->state(fn () => [
            'id_candidat' => $candidat->id_candidat,
            'id_offre'    => $offre->id_offre,
        ]);
    }

    /** Candidature à un statut donné (RG32). */
    public function statut(StatutCandidature $statut): static
    {
        return $this->state(fn () => ['statut' => $statut]);
    }

    /** Candidature déjà analysée, avec son score de compatibilité (RG43). */
    public function score(float $score): static
    {
        return $this->state(fn () => ['score_final' => $score]);
    }
}
