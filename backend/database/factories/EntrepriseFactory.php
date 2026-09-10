<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<\App\Models\Entreprise> */
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
