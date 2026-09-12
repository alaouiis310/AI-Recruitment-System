<?php

namespace Database\Factories;

use App\Models\TestTechnique;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<TestTechnique> */
class TestTechniqueFactory extends Factory
{
    public function definition(): array
    {
        return [
            'titre'       => fake()->randomElement([
                'Algorithmique et structures de données',
                'PHP et Laravel',
                'SQL avancé',
                'JavaScript moderne',
                'Culture DevOps',
            ]).' '.fake()->unique()->numberBetween(1, 9999),
            'description' => fake()->sentence(12),
            'duree'       => fake()->randomElement([30, 45, 60, 90]),
            'score_max'   => 100,
        ];
    }
}
