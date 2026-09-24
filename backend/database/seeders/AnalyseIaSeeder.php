<?php

namespace Database\Seeders;

use App\Models\Candidature;
use App\Services\AnalyseIaService;
use Illuminate\Database\Seeder;

/** Analyses de démonstration (RG37 à RG43). */
class AnalyseIaSeeder extends Seeder
{
    public function __construct(private readonly AnalyseIaService $analyses) {}

    public function run(): void
    {
        $candidatures = Candidature::with(['candidat.competences', 'offre.competences'])->get();

        foreach ($candidatures as $candidature) {
            $this->analyses->analyser($candidature);
        }

        $this->command?->info("  {$candidatures->count()} candidature(s) analysée(s).");
    }
}
