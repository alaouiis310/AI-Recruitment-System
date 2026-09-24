<?php

namespace App\Services;

use App\Models\Candidature;
use App\Models\Entretien;
use App\Models\Recruteur;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/** Logique métier des entretiens (RG34, RG35, RG36). */
class EntretienService
{
    /** Entretiens planifiés pour une candidature (RG34). */
    public function listerDeLaCandidature(Candidature $candidature): iterable
    {
        return $candidature->entretiens()
            ->orderBy('date')
            ->orderBy('heure')
            ->get();
    }

    /** Entretiens relevant des offres publiées par un recruteur (RG14). */
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

    /** Planifie un entretien sur une candidature (RG34/RG35). */
    public function planifier(Candidature $candidature, array $donnees): Entretien
    {
        $entretien = $candidature->entretiens()->create($donnees);

        // fresh() et non le modèle retourné.
        return $entretien->fresh();
    }

    /** Met à jour la tenue ou l'issue de l'entretien (RG36). */
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
