<?php

namespace Database\Factories;

use App\Enums\NiveauEtude;
use App\Enums\StatutOffre;
use App\Enums\TypeContrat;
use App\Models\Departement;
use App\Models\OffreEmploi;
use App\Models\Recruteur;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<OffreEmploi> */
class OffreEmploiFactory extends Factory
{
    public function definition(): array
    {
        return [
            'titre'            => fake()->jobTitle(),
            'description'      => fake()->paragraph(4),
            'type_contrat'     => fake()->randomElement(TypeContrat::cases()),
            'localisation'     => fake()->randomElement(['Tanger', 'Casablanca', 'Rabat', 'Marrakech']),
            'salaire'          => fake()->randomElement([null, fake()->numberBetween(8000, 30000)]),
            'experience_min'   => fake()->randomElement([0, 1, 2, 3, 5]),
            'niveau_etude'     => fake()->randomElement(NiveauEtude::cases()),
            'date_publication' => now()->toDateString(),
            'date_expiration'  => now()->addMonth()->toDateString(),
            'statut'           => StatutOffre::Ouverte,
            'id_recruteur'     => Recruteur::factory(),
            'id_departement'   => Departement::factory(),
        ];
    }

    /** RG12/RG13 — offre publiée par un recruteur donné, dans un de ses départements. */
    public function publieePar(Recruteur $recruteur, Departement $departement): static
    {
        return $this->state(fn () => [
            'id_recruteur'   => $recruteur->id_recruteur,
            'id_departement' => $departement->id_departement,
        ]);
    }

    /** RG18 — offre fermée : invisible des candidats. */
    public function fermee(): static
    {
        return $this->state(fn () => ['statut' => StatutOffre::Fermee]);
    }

    /** RG17 — offre dont la date d'expiration est dépassée. */
    public function expiree(): static
    {
        return $this->state(fn () => [
            'date_publication' => now()->subMonths(2)->toDateString(),
            'date_expiration'  => now()->subDay()->toDateString(),
        ]);
    }
}
