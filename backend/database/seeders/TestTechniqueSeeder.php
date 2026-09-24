<?php

namespace Database\Seeders;

use App\Enums\StatutResultatTest;
use App\Models\Candidature;
use App\Models\NotificationApp;
use App\Models\OffreEmploi;
use App\Models\ResultatTest;
use App\Models\TestTechnique;
use Illuminate\Database\Seeder;

/** Catalogue, affectations et notifications de démonstration (RG44/RG45). */
class TestTechniqueSeeder extends Seeder
{
    public function run(): void
    {
        $php = TestTechnique::create([
            'titre' => 'PHP / Laravel',
            'description' => 'API REST, Eloquent, validation et tests automatisés.',
            'duree' => 60,
            'score_max' => 100,
        ]);

        $javascript = TestTechnique::create([
            'titre' => 'JavaScript / Vue.js',
            'description' => 'Composition API, état réactif et intégration HTTP.',
            'duree' => 45,
            'score_max' => 100,
        ]);

        $sql = TestTechnique::create([
            'titre' => 'SQL et modélisation',
            'description' => 'Requêtes, index, transactions et conception relationnelle.',
            'duree' => 40,
            'score_max' => 20,
        ]);

        OffreEmploi::where('titre', 'like', '%back-end%')->first()?->tests()->sync([$php->id_test, $sql->id_test]);
        OffreEmploi::where('titre', 'like', '%front-end%')->first()?->tests()->sync([$javascript->id_test]);

        $candidature = Candidature::where('statut', 'preselectionnee')->with('candidat.user')->first();

        if ($candidature) {
            ResultatTest::create([
                'id_candidature' => $candidature->id_candidature,
                'id_test' => $php->id_test,
                'date_passage' => now()->subDay()->toDateString(),
                'score_obtenu' => 82,
                'statut' => StatutResultatTest::Termine,
                'commentaire' => 'Bon niveau général, code clair et structuré.',
            ]);

            NotificationApp::create([
                'id_user' => $candidature->candidat->user->id,
                'message' => 'Votre résultat au test PHP / Laravel est disponible.',
                'date_envoi' => now(),
                'lu' => false,
            ]);
        }
    }
}
