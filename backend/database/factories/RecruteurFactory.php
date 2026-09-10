<?php

namespace Database\Factories;

use App\Models\Entreprise;
use App\Models\Recruteur;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Recruteur> */
class RecruteurFactory extends Factory
{
    public function definition(): array
    {
        return [
            'id_user'       => User::factory()->recruteur(),
            'id_entreprise' => Entreprise::factory(),
            'telephone'     => fake()->numerify('05########'),
            'poste'         => fake()->randomElement([
                'Responsable des ressources humaines',
                'Chargé de recrutement',
                'Talent acquisition manager',
            ]),
        ];
    }
}
