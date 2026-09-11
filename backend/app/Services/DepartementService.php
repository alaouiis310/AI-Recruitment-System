<?php

namespace App\Services;

use App\Models\Departement;
use App\Models\Entreprise;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * Logique métier des départements (RG8, RG9).
 *
 * Comme pour les entreprises, chaque écriture est une instruction unique :
 * aucune transaction n'est nécessaire.
 */
class DepartementService
{
    /** RG8 — liste paginée des départements d'une entreprise donnée. */
    public function lister(Entreprise $entreprise, array $filtres): LengthAwarePaginator
    {
        return $entreprise->departements()
            ->when($filtres['recherche'] ?? null, fn (Builder $q, string $terme) => $q->where('nom', 'like', '%'.$this->echapper($terme).'%'))
            ->orderBy('nom')
            ->paginate(perPage: $filtres['per_page'] ?? 15)
            ->withQueryString();
    }

    /**
     * RG9 — le département est rattaché à l'entreprise désignée par l'URL,
     * jamais à celle qui figurerait dans le corps de la requête.
     */
    public function creer(Entreprise $entreprise, array $donnees): Departement
    {
        return $entreprise->departements()->create([
            'nom'         => $donnees['nom'],
            'description' => $donnees['description'] ?? null,
        ]);
    }

    /** RG8 — mise à jour d'un département. */
    public function modifier(Departement $departement, array $donnees): Departement
    {
        $departement->update($donnees);

        return $departement->fresh();
    }

    /** Suppression d'un département. */
    public function supprimer(Departement $departement): void
    {
        // RG10/RG11 — lorsque la table offres_emploi existera, sa clé étrangère
        // id_departement devra interdire la suppression d'un département
        // portant des offres : la garde correspondante viendra ici.
        $departement->delete();
    }

    /** Neutralise les jokers SQL saisis par l'utilisateur. */
    private function echapper(string $terme): string
    {
        return str_replace(['%', '_'], ['\%', '\_'], $terme);
    }
}
