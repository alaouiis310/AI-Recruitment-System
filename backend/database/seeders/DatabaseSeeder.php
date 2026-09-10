<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            UtilisateurSeeder::class,
            // Dépend des entreprises créées par le seeder précédent.
            DepartementSeeder::class,
            CompetenceSeeder::class,
            // Dépend des départements et du référentiel de compétences.
            OffreEmploiSeeder::class,
            // Dépend du référentiel de compétences.
            ProfilCandidatSeeder::class,
        ]);
    }
}
