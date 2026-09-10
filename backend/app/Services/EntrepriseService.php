<?php

namespace App\Services;

use App\Models\Entreprise;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * Logique métier des entreprises (RG5, RG6, RG8).
 *
 * Aucune opération de ce service n'écrit dans plusieurs tables : les écritures
 * sont des instructions uniques, il n'y a donc pas de DB::transaction() ici.
 */
class EntrepriseService
{
    /** Liste paginée et filtrée des entreprises. */
    public function lister(array $filtres): LengthAwarePaginator
    {
        return Entreprise::query()
            ->withCount(['departements', 'recruteurs'])
            ->when($filtres['recherche'] ?? null, fn (Builder $q, string $terme) => $q->where('nom', 'like', '%'.$this->echapper($terme).'%'))
            ->when($filtres['ville'] ?? null, fn (Builder $q, string $ville) => $q->where('ville', $ville))
            ->when($filtres['secteur'] ?? null, fn (Builder $q, string $secteur) => $q->where('secteur', $secteur))
            ->orderBy('nom')
            ->paginate(perPage: $filtres['per_page'] ?? 15)
            ->withQueryString();
    }

    /** RG5 — création d'une entreprise. */
    public function creer(array $donnees): Entreprise
    {
        return Entreprise::create($donnees);
    }

    /** RG6/RG7 — mise à jour des informations d'une entreprise. */
    public function modifier(Entreprise $entreprise, array $donnees): Entreprise
    {
        $entreprise->update($donnees);

        return $entreprise->fresh();
    }

    /** Suppression d'une entreprise. */
    public function supprimer(Entreprise $entreprise): void
    {
        $entreprise->delete();
    }

    /** Neutralise les jokers SQL saisis par l'utilisateur. */
    private function echapper(string $terme): string
    {
        return str_replace(['%', '_'], ['\%', '\_'], $terme);
    }
}
