<?php

namespace Database\Seeders;

use App\Enums\ModeEntretien;
use App\Enums\ResultatEntretien;
use App\Models\Candidature;
use App\Models\Entretien;
use Illuminate\Database\Seeder;

/** Entretiens de démonstration — RG34 à RG36. */
class EntretienSeeder extends Seeder
{
    public function run(): void
    {
        $aPlanifier = Candidature::where('statut', 'preselectionnee')->first();

        if ($aPlanifier) {
            Entretien::create([
                'id_candidature' => $aPlanifier->id_candidature,
                'date' => now()->addDays(5)->toDateString(),
                'heure' => '10:30',
                'mode' => ModeEntretien::Visio,
                'lien_si_online' => 'https://meet.example.ma/airs-demo',
                'commentaire' => 'Entretien technique avec le responsable d’équipe.',
                'resultat' => ResultatEntretien::EnAttente,
            ]);
        }

        $terminee = Candidature::whereIn('statut', ['acceptee', 'refusee'])->first();

        if ($terminee) {
            Entretien::create([
                'id_candidature' => $terminee->id_candidature,
                'date' => now()->subDays(7)->toDateString(),
                'heure' => '14:00',
                'mode' => ModeEntretien::Presentiel,
                'commentaire' => 'Bonne maîtrise technique et communication claire.',
                'resultat' => $terminee->statut->value === 'acceptee'
                    ? ResultatEntretien::Favorable
                    : ResultatEntretien::Defavorable,
            ]);
        }
    }
}
