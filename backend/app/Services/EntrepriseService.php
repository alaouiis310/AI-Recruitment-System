<?php

namespace App\Services;

use App\Exceptions\SuppressionImpossibleException;
use App\Models\Entreprise;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/** Logique métier des entreprises (RG5, RG6, RG8). */
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

    /** Création d'une entreprise (RG5). */
    public function creer(array $donnees): Entreprise
    {
        return Entreprise::create($donnees);
    }

    /** Mise à jour des informations d'une entreprise (RG6/RG7). */
    public function modifier(Entreprise $entreprise, array $donnees): Entreprise
    {
        $entreprise->update($donnees);

        return $entreprise->fresh();
    }

    /**
     * La suppression est refusée tant que l'entreprise emploie des recruteurs ou possède des
     * départements (RG6/RG8).
     */
    public function supprimer(Entreprise $entreprise): void
    {
        $recruteurs   = $entreprise->recruteurs()->count();
        $departements = $entreprise->departements()->count();

        if ($recruteurs > 0 || $departements > 0) {
            throw new SuppressionImpossibleException(
                "Cette entreprise ne peut pas être supprimée : elle compte encore {$recruteurs} recruteur(s) et {$departements} département(s).",
                ['entreprise' => ['Détachez les recruteurs et supprimez les départements au préalable.']],
            );
        }

        $entreprise->delete();
    }

    /** Neutralise les jokers SQL saisis par l'utilisateur. */
    private function echapper(string $terme): string
    {
        return str_replace(['%', '_'], ['\%', '\_'], $terme);
    }
}
