<?php

namespace App\Services;

use App\Enums\EtatCompte;
use App\Enums\RoleUtilisateur;
use App\Models\AnalyseIa;
use App\Models\Candidat;
use App\Models\Candidature;
use App\Models\Entretien;
use App\Models\OffreEmploi;
use App\Models\Recruteur;
use App\Models\ResultatTest;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\LazyCollection;
use Illuminate\Validation\ValidationException;

class AdminService
{
    public function listerCandidats(array $filtres): LengthAwarePaginator
    {
        return Candidat::query()
            ->with(['user', 'competences'])
            ->withCount('candidatures')
            ->withAvg('candidatures', 'score_final')
            ->when($filtres['recherche'] ?? null, function (Builder $query, string $terme) {
                $motif = '%'.$this->echapper($terme).'%';
                $query->whereHas('user', fn (Builder $q) => $q
                    ->where('name', 'like', $motif)
                    ->orWhere('prenom', 'like', $motif)
                    ->orWhere('email', 'like', $motif));
            })
            ->when($filtres['etat_compte'] ?? null, fn (Builder $query, string $etat) => $query
                ->whereHas('user', fn (Builder $q) => $q->where('etat_compte', $etat)))
            ->orderByDesc('id_candidat')
            ->paginate(perPage: $filtres['per_page'] ?? 15)
            ->withQueryString();
    }

    public function listerRecruteurs(array $filtres): LengthAwarePaginator
    {
        return Recruteur::query()
            ->with(['user', 'entreprise'])
            ->withCount('offres')
            ->when($filtres['recherche'] ?? null, function (Builder $query, string $terme) {
                $motif = '%'.$this->echapper($terme).'%';
                $query->where(function (Builder $q) use ($motif) {
                    $q->whereHas('user', fn (Builder $user) => $user
                        ->where('name', 'like', $motif)
                        ->orWhere('prenom', 'like', $motif)
                        ->orWhere('email', 'like', $motif))
                        ->orWhereHas('entreprise', fn (Builder $entreprise) => $entreprise->where('nom', 'like', $motif));
                });
            })
            ->when($filtres['etat_compte'] ?? null, fn (Builder $query, string $etat) => $query
                ->whereHas('user', fn (Builder $q) => $q->where('etat_compte', $etat)))
            ->orderByDesc('id_recruteur')
            ->paginate(perPage: $filtres['per_page'] ?? 15)
            ->withQueryString();
    }

    /** Statistiques globales consommées par le tableau de bord administrateur. */
    public function statistiques(): array
    {
        $totalCandidatures = Candidature::count();
        $acceptees = Candidature::where('statut', 'acceptee')->count();

        return [
            'utilisateurs' => [
                'total'       => User::count(),
                'candidats'   => User::where('role', RoleUtilisateur::Candidat)->count(),
                'recruteurs'  => User::where('role', RoleUtilisateur::Recruteur)->count(),
                'suspendus'   => User::where('etat_compte', 'suspendu')->count(),
                'desactives'  => User::where('etat_compte', 'desactive')->count(),
            ],
            'offres' => [
                'total'      => OffreEmploi::count(),
                'ouvertes'   => OffreEmploi::where('statut', 'ouverte')->count(),
                'fermees'    => OffreEmploi::where('statut', 'fermee')->count(),
                'suspendues' => OffreEmploi::where('statut', 'suspendue')->count(),
            ],
            'candidatures' => [
                'total'             => $totalCandidatures,
                'en_attente'        => Candidature::where('statut', 'en_attente')->count(),
                'en_cours'          => Candidature::where('statut', 'en_cours')->count(),
                'preselectionnees'  => Candidature::where('statut', 'preselectionnee')->count(),
                'acceptees'         => $acceptees,
                'refusees'          => Candidature::where('statut', 'refusee')->count(),
                'taux_conversion'   => $totalCandidatures > 0 ? round($acceptees / $totalCandidatures * 100, 2) : 0,
                'delai_moyen_decision_jours' => $this->delaiMoyenDecision(),
            ],
            'traitements' => [
                'analyses_ia'              => AnalyseIa::count(),
                'score_moyen'              => round((float) (AnalyseIa::avg('score_matching') ?? 0), 2),
                'entretiens_a_venir'       => Entretien::whereDate('date', '>=', now()->toDateString())->count(),
                'resultats_tests_termines' => ResultatTest::where('statut', 'termine')->count(),
            ],
            'evolution_candidatures' => $this->evolutionCandidatures(),
        ];
    }

    /**
     * RG32/RG33 — délai moyen, en jours, entre le dépôt d'une candidature et
     * la décision définitive. Calculé en PHP : les fonctions de date diffèrent
     * entre MySQL et SQLite, qui sert aux tests.
     */
    private function delaiMoyenDecision(): float
    {
        $delais = Candidature::query()
            ->whereNotNull('date_decision')
            ->get(['date_candidature', 'date_decision'])
            ->map(fn (Candidature $c) => $c->date_candidature->diffInDays($c->date_decision));

        return $delais->isEmpty() ? 0.0 : round((float) $delais->avg(), 1);
    }

    public function changerEtat(User $administrateur, User $utilisateur, EtatCompte $etat): User
    {
        if ($administrateur->is($utilisateur) && $etat !== EtatCompte::Actif) {
            throw ValidationException::withMessages([
                'etat_compte' => ['Vous ne pouvez pas suspendre ou désactiver votre propre compte.'],
            ]);
        }

        $utilisateur->update(['etat_compte' => $etat]);

        if (! $etat->peutSeConnecter()) {
            $utilisateur->tokens()->delete();
        }

        return $utilisateur->fresh();
    }

    public function dernieresCandidatures(int $limite = 5)
    {
        return Candidature::with(['candidat.user', 'offre.departement.entreprise'])
            ->orderByDesc('date_candidature')
            ->orderByDesc('id_candidature')
            ->limit($limite)
            ->get();
    }

    public function candidaturesPourExport(array $filtres): LazyCollection
    {
        return Candidature::query()
            ->with(['candidat.user', 'offre'])
            ->when($filtres['statut'] ?? null, fn (Builder $q, string $statut) => $q->where('statut', $statut))
            ->when($filtres['id_offre'] ?? null, fn (Builder $q, $id) => $q->where('id_offre', $id))
            ->orderBy('id_candidature')
            ->lazyById(200, 'id_candidature');
    }

    /** @return array<int, array{mois: string, total: int}> */
    private function evolutionCandidatures(): array
    {
        $debut = CarbonImmutable::now()->startOfMonth()->subMonths(5);

        return collect(range(0, 5))->map(function (int $decalage) use ($debut) {
            $mois = $debut->addMonths($decalage);

            return [
                'mois'  => $mois->format('Y-m'),
                'total' => Candidature::whereBetween('date_candidature', [
                    $mois->startOfMonth()->toDateString(),
                    $mois->endOfMonth()->toDateString(),
                ])->count(),
            ];
        })->all();
    }

    private function echapper(string $terme): string
    {
        return str_replace(['%', '_'], ['\\%', '\\_'], $terme);
    }
}
