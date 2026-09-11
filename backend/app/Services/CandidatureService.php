<?php

namespace App\Services;

use App\Enums\StatutCandidature;
use App\Exceptions\CandidatureImpossibleException;
use App\Exceptions\TransitionInterditeException;
use App\Models\Candidat;
use App\Models\Candidature;
use App\Models\OffreEmploi;
use App\Models\Recruteur;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;

/**
 * Logique métier des candidatures (RG27 à RG33, RG43).
 */
class CandidatureService
{
    /**
     * RG27/RG31 — dépôt d'une candidature.
     *
     * L'unicité (candidat, offre) est garantie par un index de la base : on la
     * laisse échouer puis on traduit la violation, plutôt que de tester
     * l'existence au préalable, ce qui laisserait passer deux requêtes
     * simultanées.
     */
    public function postuler(Candidat $candidat, OffreEmploi $offre, array $donnees): Candidature
    {
        // RG17/RG18 — une offre fermée, suspendue ou expirée n'accepte plus rien.
        if (! $offre->accepteCandidatures()) {
            throw new CandidatureImpossibleException(
                "Cette offre n'accepte plus de candidatures.",
                ['id_offre' => ["L'offre est ".$offre->statut->libelle().($offre->estExpiree() ? ' et expirée' : '').'.']],
            );
        }

        try {
            $candidature = Candidature::create([
                'id_candidat'       => $candidat->id_candidat,
                'id_offre'          => $offre->id_offre,
                'lettre_motivation' => $donnees['lettre_motivation'] ?? null,

                // RG33 — la date de dépôt est celle du jour, jamais fournie
                // par le client.
                'date_candidature'  => now()->toDateString(),
                'statut'            => StatutCandidature::EnAttente,
            ]);
        } catch (QueryException $e) {
            if ($this->estViolationUnicite($e)) {
                throw new CandidatureImpossibleException(
                    'Vous avez déjà postulé à cette offre.',
                    ['id_offre' => ['Une seule candidature est permise par offre.']],
                );
            }

            throw $e;
        }

        // Le module 6 déclenchera ici l'analyse IA de la candidature (RG37).

        return $candidature->load(['offre.departement', 'offre.recruteur.entreprise']);
    }

    /** RG27 — candidatures déposées par un candidat. */
    public function listerDuCandidat(Candidat $candidat, array $filtres): LengthAwarePaginator
    {
        return $this->filtrer($candidat->candidatures()->getQuery(), $filtres)
            ->with(['offre.departement.entreprise'])
            ->orderByDesc('date_candidature')
            ->orderByDesc('id_candidature')
            ->paginate(perPage: $filtres['per_page'] ?? 15)
            ->withQueryString();
    }

    /**
     * RG14/RG43 — candidatures reçues par un recruteur, restreintes à ses
     * propres offres et classées par score décroissant.
     */
    public function listerDuRecruteur(Recruteur $recruteur, array $filtres): LengthAwarePaginator
    {
        $requete = $this->filtrer(
            Candidature::query()->duRecruteur($recruteur->id_recruteur),
            $filtres,
        );

        return $requete
            ->with(['candidat.user', 'offre.departement'])
            ->when($filtres['id_offre'] ?? null, fn (Builder $q, $v) => $q->where('id_offre', $v))
            ->classeeParScore()
            ->paginate(perPage: $filtres['per_page'] ?? 15)
            ->withQueryString();
    }

    /**
     * RG32 — avancement du dossier. La transition demandée doit suivre le
     * cycle de vie ; une candidature acceptée ou refusée est définitive.
     */
    public function changerStatut(Candidature $candidature, StatutCandidature $cible, ?string $commentaire = null): Candidature
    {
        if (! $candidature->statut->peutDevenir($cible)) {
            throw new TransitionInterditeException($candidature->statut, $cible);
        }

        $candidature->update(array_filter([
            'statut'                => $cible,
            'commentaire_recruteur' => $commentaire,

            // La décision est datée dès qu'elle est définitive.
            'date_decision'         => $cible->estDefinitif() ? now()->toDateString() : null,
        ], fn ($valeur) => $valeur !== null));

        return $candidature->fresh(['candidat.user', 'offre']);
    }

    /** Retrait d'une candidature par son auteur. */
    public function retirer(Candidature $candidature): void
    {
        $candidature->delete();
    }

    /** Filtre commun aux deux listes : statut de la candidature (RG32). */
    private function filtrer(Builder $requete, array $filtres): Builder
    {
        return $requete->when(
            $filtres['statut'] ?? null,
            fn (Builder $q, string $statut) => $q->where('statut', $statut),
        );
    }

    /** Reconnaît une violation d'index unique, quel que soit le pilote. */
    private function estViolationUnicite(QueryException $e): bool
    {
        // 23000 / 23505 : classe SQLSTATE des violations de contrainte
        // d'intégrité, commune à MySQL, PostgreSQL et SQLite.
        return in_array($e->getCode(), ['23000', '23505'], true);
    }
}
