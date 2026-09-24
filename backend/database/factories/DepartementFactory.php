<?php

namespace Database\Factories;

use App\Models\Departement;
use App\Models\Entreprise;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Departement> */
class DepartementFactory extends Factory
{
    /** Compteur de noms : RG8 impose l'unicité du nom au sein d'une entreprise. */
    private static int $compteur = 0;

    public function definition(): array
    {
        return [
            'id_entreprise' => Entreprise::factory(),
            'nom'           => $this->nomUnique(),
            'description'   => fake()->sentence(10),
        ];
    }

    /** Rattache le département à une entreprise donnée (RG9). */
    public function pour(Entreprise $entreprise): static
    {
        return $this->state(fn () => ['id_entreprise' => $entreprise->id_entreprise]);
    }

    /**
     * Parcourt une liste de noms réalistes puis les suffixe, de façon à rester lisible tout en
     * restant unique quel que soit le nombre d'instances.
     */
    private function nomUnique(): string
    {
        $noms = [
            'Ressources humaines',
            'Développement logiciel',
            'Infrastructure et réseaux',
            'Commercial',
            'Direction générale',
            'Marketing digital',
            'Data et intelligence artificielle',
            'Qualité',
            'Finance',
            'Support client',
        ];

        $index = self::$compteur++;
        $nom   = $noms[$index % count($noms)];
        $tour  = intdiv($index, count($noms));

        return $tour === 0 ? $nom : $nom.' '.($tour + 1);
    }
}
