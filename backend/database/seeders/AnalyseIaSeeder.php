<?php

namespace Database\Seeders;

use App\Models\Candidature;
use App\Services\AnalyseIaService;
use Illuminate\Database\Seeder;

/**
 * Analyses de démonstration — RG37 à RG43.
 *
 * Le seeder appelle le service réel plutôt que d'inventer des scores : les
 * valeurs obtenues sont donc celles que produira l'application, et le
 * classement de RG43 est démontrable immédiatement.
 *
 * Sans clé d'API, le calcul déterministe s'exécute seul et le résumé est
 * rédigé en PHP — c'est le comportement attendu (RG40).
 */
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
