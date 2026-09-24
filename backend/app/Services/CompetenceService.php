<?php

namespace App\Services;

use App\Exceptions\SuppressionImpossibleException;
use App\Models\Competence;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/** Référentiel des compétences (RG20, RG25). */
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
        $nombreOffres = $competence->offres()->count();
        $nombreCandidats = $competence->candidats()->count();

        if ($nombreOffres > 0 || $nombreCandidats > 0) {
            throw new SuppressionImpossibleException(
                "Cette compétence ne peut pas être supprimée : elle est utilisée par {$nombreOffres} offre(s) et {$nombreCandidats} candidat(s).",
                ['competence' => ['Retirez cette compétence des offres et profils au préalable.']],
            );
        }

        $competence->delete();
    }

    /** Neutralise les jokers SQL saisis par l'utilisateur. */
    private function echapper(string $terme): string
    {
        return str_replace(['%', '_'], ['\%', '\_'], $terme);
    }
}
