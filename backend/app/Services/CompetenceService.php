<?php

namespace App\Services;

use App\Models\Competence;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * Référentiel des compétences (RG20, RG25).
 *
 * Chaque écriture est une instruction unique : aucune transaction n'est
 * nécessaire ici.
 */
class CompetenceService
{
    /** Liste paginée du référentiel, filtrable par nom et par catégorie. */
    public function lister(array $filtres): LengthAwarePaginator
    {
        return Competence::query()
            ->when($filtres['recherche'] ?? null, fn (Builder $q, string $terme) => $q->where('nom', 'like', '%'.$this->echapper($terme).'%'))
            ->when($filtres['categorie'] ?? null, fn (Builder $q, string $categorie) => $q->where('categorie', $categorie))
            ->orderBy('categorie')
            ->orderBy('nom')
            ->paginate(perPage: $filtres['per_page'] ?? 15)
            ->withQueryString();
    }

    public function creer(array $donnees): Competence
    {
        return Competence::create($donnees);
    }

    public function modifier(Competence $competence, array $donnees): Competence
    {
        $competence->update($donnees);

        return $competence->fresh();
    }

    public function supprimer(Competence $competence): void
    {
        // RG19/RG24 — lorsque les pivots requerir et posseder existeront, la
        // suppression d'une compétence encore référencée devra être refusée.
        $competence->delete();
    }

    /** Neutralise les jokers SQL saisis par l'utilisateur. */
    private function echapper(string $terme): string
    {
        return str_replace(['%', '_'], ['\%', '\_'], $terme);
    }
}
