<?php

namespace Database\Factories;

use App\Models\Candidat;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Candidat> */
class CandidatFactory extends Factory
{
    public function definition(): array
    {
        return [
            'id_user'           => User::factory(),
            'telephone'         => fake()->numerify('06########'),
            'adresse'           => fake()->streetAddress(),
            'date_naissance'    => fake()->dateTimeBetween('-40 years', '-18 years')->format('Y-m-d'),
            'diplome'           => fake()->randomElement([
                "Diplôme d'ingénieur en génie informatique",
                'Master en systèmes d\'information',
                'Licence en développement web',
            ]),
            'github'            => 'https://github.com/'.fake()->userName(),
            'linkedin'          => 'https://linkedin.com/in/'.fake()->userName(),
            'experience_totale' => fake()->randomElement([0, 1, 2.5, 4, 6]),
        ];
    }

    /** Candidat disposant déjà d'un CV enregistré (RG22). */
    public function avecCv(string $chemin = 'cv/exemple.pdf'): static
    {
        return $this->state(fn () => ['cv_pdf' => $chemin]);
    }
}
