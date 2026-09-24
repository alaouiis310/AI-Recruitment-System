<?php

namespace Database\Seeders;

use App\Models\Departement;
use App\Models\Entreprise;
use Illuminate\Database\Seeder;

/** Départements de démonstration (RG8, RG9). */
class DepartementSeeder extends Seeder
{
    public function run(): void
    {
        $technoMaroc = Entreprise::where('nom', 'TechnoMaroc')->firstOrFail();
        $atlas       = Entreprise::where('nom', 'Atlas Digital')->firstOrFail();

        $departements = [
            $technoMaroc->id_entreprise => [
                ['nom' => 'Ressources humaines',       'description' => 'Recrutement, paie et gestion des carrières.'],
                ['nom' => 'Développement logiciel',    'description' => 'Conception et réalisation des applications métier.'],
                ['nom' => 'Infrastructure et réseaux', 'description' => 'Exploitation des serveurs et de la sécurité réseau.'],
                ['nom' => 'Commercial',                'description' => 'Prospection et suivi de la relation client.'],
                ['nom' => 'Direction générale',        'description' => 'Pilotage stratégique de la société.'],
            ],
            $atlas->id_entreprise => [
                ['nom' => 'Ressources humaines',                'description' => 'Gestion des talents et du recrutement.'],
                ['nom' => 'Data et intelligence artificielle',  'description' => 'Valorisation des données et modèles prédictifs.'],
                ['nom' => 'Marketing digital',                  'description' => 'Acquisition et communication en ligne.'],
            ],
        ];

        foreach ($departements as $idEntreprise => $lignes) {
            foreach ($lignes as $ligne) {
                Departement::create($ligne + ['id_entreprise' => $idEntreprise]);
            }
        }
    }
}
