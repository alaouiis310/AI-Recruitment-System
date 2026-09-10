<?php

namespace Database\Factories;

use App\Models\Entreprise;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Entreprise> */
class EntrepriseFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nom'         => fake()->unique()->company(),
            'secteur'     => fake()->randomElement([
                "Technologies de l'information",
                'Conseil et transformation digitale',
                'Industrie',
                'Télécommunications',
                'Services financiers',
            ]),
            'adresse'     => fake()->streetAddress(),
            'ville'       => fake()->randomElement(['Tanger', 'Casablanca', 'Rabat', 'Marrakech', 'Fès']),
            'site_web'    => 'https://'.fake()->unique()->domainName(),
            'description' => fake()->sentence(12),
        ];
    }
}
