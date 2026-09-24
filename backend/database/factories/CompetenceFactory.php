<?php

namespace Database\Factories;

use App\Enums\CategorieCompetence;
use App\Models\Competence;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Competence> */
class CompetenceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nom'         => fake()->unique()->word(),
            'categorie'   => fake()->randomElement(CategorieCompetence::cases()),
            'description' => fake()->sentence(8),
        ];
    }

    public function categorie(CategorieCompetence $categorie): static
    {
        return $this->state(fn () => ['categorie' => $categorie]);
    }
}
