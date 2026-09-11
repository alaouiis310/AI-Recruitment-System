<?php

namespace Database\Seeders;

use App\Models\Departement;
use App\Models\Entreprise;
use Illuminate\Database\Seeder;

/**
 * Départements de démonstration — RG8, RG9.
 *
 * Les deux entreprises possèdent un département « Ressources humaines » :
 * la contrainte d'unicité porte sur le couple (id_entreprise, nom), pas sur
 * le nom seul.
 */
class DepartementSeeder extends Seeder
{
    public function run(): void
    {
        // firstOrFail : un ordre d'exécution incorrect doit échouer bruyamment
        // plutôt que de ne rien insérer silencieusement.
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
