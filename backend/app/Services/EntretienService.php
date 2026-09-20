<?php

namespace App\Services;

use App\Models\Candidature;
use App\Models\Entretien;
use App\Models\Recruteur;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * Logique métier des entretiens (RG34, RG35, RG36).
 *
 * Chaque écriture est une instruction unique : aucune transaction n'est
 * nécessaire.
 */
class EntretienService
{
    /** RG34 — entretiens planifiés pour une candidature. */
    public function listerDeLaCandidature(Candidature $candidature): iterable
    {
        return $candidature->entretiens()
            ->orderBy('date')
            ->orderBy('heure')
            ->get();
    }

    /** RG14 — entretiens relevant des offres publiées par un recruteur. */
    public function listerDuRecruteur(Recruteur $recruteur, array $filtres): LengthAwarePaginator
    {
        return Entretien::query()
            ->duRecruteur($recruteur->id_recruteur)
            ->with(['candidature.candidat.user', 'candidature.offre'])
            ->when($filtres['resultat'] ?? null, fn (Builder $q, string $v) => $q->where('resultat', $v))
            ->when($filtres['a_venir'] ?? null, fn (Builder $q) => $q->whereDate('date', '>=', now()->toDateString()))
            ->orderBy('date')
            ->orderBy('heure')
            ->paginate(perPage: $filtres['per_page'] ?? 15)
            ->withQueryString();
    }

    /** RG34/RG35 — planifie un entretien sur une candidature. */
    public function planifier(Candidature $candidature, array $donnees): Entretien
    {
        $entretien = $candidature->entretiens()->create($donnees);

        // fresh() et non le modèle retourné : resultat provient d'une valeur
        // par défaut de la base, absente du modèle en mémoire.
        return $entretien->fresh();
    }

    /** RG36 — met à jour la tenue ou l'issue de l'entretien. */
    public function modifier(Entretien $entretien, array $donnees): Entretien
    {
        $entretien->update($donnees);

        return $entretien->fresh(['candidature.candidat.user', 'candidature.offre']);
    }

    public function annuler(Entretien $entretien): void
    {
        $entretien->delete();
    }
}
