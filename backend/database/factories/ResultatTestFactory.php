<?php

namespace Database\Factories;

use App\Enums\StatutResultatTest;
use App\Models\Candidature;
use App\Models\ResultatTest;
use App\Models\TestTechnique;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ResultatTest> */
class ResultatTestFactory extends Factory
{
    public function definition(): array
    {
        return [
            'id_candidature' => Candidature::factory(),
            'id_test'        => TestTechnique::factory(),
            'statut'         => StatutResultatTest::Envoye,
            'date_passage'   => null,
            'score_obtenu'   => null,
        ];
    }

    public function pour(Candidature $candidature, TestTechnique $test): static
    {
        return $this->state(fn () => [
            'id_candidature' => $candidature->id_candidature,
            'id_test'        => $test->id_test,
        ]);
    }

    /** RG45 — test passé, avec son score. */
    public function termine(int $score = 75): static
    {
        return $this->state(fn () => [
            'statut'       => StatutResultatTest::Termine,
            'score_obtenu' => $score,
            'date_passage' => now()->subDay()->toDateString(),
        ]);
    }
}
