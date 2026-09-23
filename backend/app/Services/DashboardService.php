<?php

namespace App\Services;

use App\Models\Candidat;
use App\Models\Candidature;
use App\Models\Entretien;
use App\Models\OffreEmploi;
use App\Models\Recruteur;
use Illuminate\Database\Eloquent\Builder;

class DashboardService
{
    public function candidat(Candidat $candidat): array
    {
        $candidatures = $candidat->candidatures();

        return [
            'statistiques' => [
                'candidatures' => (clone $candidatures)->count(),
                'en_cours' => (clone $candidatures)->whereIn('statut', [
                    'en_attente',
                    'en_cours',
                    'preselectionnee',
                ])->count(),
                'acceptees' => (clone $candidatures)->where('statut', 'acceptee')->count(),
                'refusees' => (clone $candidatures)->where('statut', 'refusee')->count(),
                'entretiens_a_venir' => Entretien::whereHas(
                    'candidature',
                    fn (Builder $q) => $q->where('id_candidat', $candidat->id_candidat),
                )->whereDate('date', '>=', now()->toDateString())->count(),
                'notifications_non_lues' => $candidat->user->notifications()->nonLues()->count(),
            ],
            'candidatures_recentes' => $candidat->candidatures()
                ->with('offre.departement.entreprise')
                ->orderByDesc('date_candidature')
                ->limit(5)
                ->get(),
            'entretiens_a_venir' => Entretien::query()
                ->whereHas('candidature', fn (Builder $q) => $q->where('id_candidat', $candidat->id_candidat))
                ->whereDate('date', '>=', now()->toDateString())
                ->with('candidature.offre.departement.entreprise')
                ->orderBy('date')
                ->orderBy('heure')
                ->limit(5)
                ->get(),
            'offres_recentes' => OffreEmploi::query()
                ->publiable()
                ->whereDoesntHave('candidatures', fn (Builder $q) => $q->where('id_candidat', $candidat->id_candidat))
                ->with('departement.entreprise')
                ->orderByDesc('date_publication')
                ->limit(5)
                ->get(),
        ];
    }

    public function recruteur(Recruteur $recruteur): array
    {
        $offres = $recruteur->offres();
        $candidatures = Candidature::query()->duRecruteur($recruteur->id_recruteur);

        return [
            'statistiques' => [
                'offres' => (clone $offres)->count(),
                'offres_ouvertes' => (clone $offres)->where('statut', 'ouverte')->count(),
                'candidatures' => (clone $candidatures)->count(),
                'en_attente' => (clone $candidatures)->where('statut', 'en_attente')->count(),
                'preselectionnees' => (clone $candidatures)->where('statut', 'preselectionnee')->count(),
                'acceptees' => (clone $candidatures)->where('statut', 'acceptee')->count(),
                'entretiens_a_venir' => Entretien::query()
                    ->duRecruteur($recruteur->id_recruteur)
                    ->whereDate('date', '>=', now()->toDateString())
                    ->count(),
                'notifications_non_lues' => $recruteur->user->notifications()->nonLues()->count(),
            ],
            'candidatures_recentes' => Candidature::query()
                ->duRecruteur($recruteur->id_recruteur)
                ->with(['candidat.user', 'offre.departement'])
                ->orderByDesc('date_candidature')
                ->limit(5)
                ->get(),
            'offres_recentes' => OffreEmploi::query()
                ->where('id_recruteur', $recruteur->id_recruteur)
                ->with('departement')
                ->withCount('candidatures')
                ->orderByDesc('date_publication')
                ->limit(5)
                ->get(),
            'entretiens_a_venir' => Entretien::query()
                ->duRecruteur($recruteur->id_recruteur)
                ->whereDate('date', '>=', now()->toDateString())
                ->with(['candidature.candidat.user', 'candidature.offre'])
                ->orderBy('date')
                ->orderBy('heure')
                ->limit(5)
                ->get(),
        ];
    }
}
