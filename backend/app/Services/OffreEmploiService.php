<?php

namespace App\Services;

use App\Exceptions\SuppressionImpossibleException;
use App\Models\OffreEmploi;
use App\Models\Recruteur;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/** Logique métier des offres d'emploi (RG10 à RG21). */
class OffreEmploiService
{
    /** Offres visibles des candidats : ouvertes et non expirées (RG17/RG18). */
    public function listerPubliques(array $filtres): LengthAwarePaginator
    {
        return $this->appliquerFiltres(OffreEmploi::query()->publiable(), $filtres);
    }

    /** Offres publiées par un recruteur donné, tous statuts confondus (RG12). */
    public function listerDuRecruteur(Recruteur $recruteur, array $filtres): LengthAwarePaginator
    {
        return $this->appliquerFiltres($recruteur->offres()->getQuery(), $filtres);
    }

    /** Vue globale de l'administrateur, tous recruteurs et statuts confondus. */
    public function listerToutes(array $filtres): LengthAwarePaginator
    {
        return $this->appliquerFiltres(OffreEmploi::query(), $filtres);
    }

    /** Publication d'une offre par le recruteur authentifié (RG12/RG13). */
    public function creer(Recruteur $recruteur, array $donnees): OffreEmploi
    {
        return DB::transaction(function () use ($recruteur, $donnees) {
            $competences = $donnees['competences'] ?? [];
            unset($donnees['competences']);

            // À défaut de date fournie, l'offre est publiée ce jour (RG16).
            $donnees['date_publication'] ??= now()->toDateString();

            $offre = $recruteur->offres()->create($donnees);

            if ($competences) {
                $offre->competences()->sync($this->pivot($competences));
            }

            // fresh() et non load().
            return $offre->fresh(['departement', 'competences']);
        });
    }

    /** Mise à jour d'une offre et, le cas échéant, de ses compétences (RG12/RG13). */
    public function modifier(OffreEmploi $offre, array $donnees): OffreEmploi
    {
        return DB::transaction(function () use ($offre, $donnees) {
            $competences = $donnees['competences'] ?? null;
            unset($donnees['competences']);

            if ($donnees) {
                $offre->update($donnees);
            }

            // Absent : compétences inchangées. Présent même vide : remplacement.
            if ($competences !== null) {
                $offre->competences()->sync($this->pivot($competences));
            }

            return $offre->fresh(['departement', 'competences']);
        });
    }

    /** Suppression d'une offre et, en cascade, de ses compétences requises. */
    public function supprimer(OffreEmploi $offre): void
    {
        $nombreCandidatures = $offre->candidatures()->count();

        if ($nombreCandidatures > 0) {
            throw new SuppressionImpossibleException(
                "Cette offre ne peut pas être supprimée : elle a reçu {$nombreCandidatures} candidature(s).",
                ['offre' => ['Fermez l’offre pour préserver l’historique des candidatures.']],
            );
        }

        $offre->delete();
    }

    /** Met le pivot requerir en forme attendue par sync() (RG21). */
    private function pivot(array $competences): array
    {
        return collect($competences)
            ->keyBy('id_competence')
            ->map(fn (array $c) => [
                'niveau_requis' => $c['niveau_requis'],
                'importance'    => $c['importance'],
            ])
            ->all();
    }

    /** Filtres communs aux deux listes (RG15, RG18). */
    private function appliquerFiltres(Builder $requete, array $filtres): LengthAwarePaginator
    {
        return $requete
            ->with(['departement.entreprise', 'recruteur.entreprise', 'competences'])
            ->withCount('candidatures')
            ->when($filtres['mots_cles'] ?? null, function (Builder $q, string $termes) {
                $motif = '%'.$this->echapper($termes).'%';
                $q->where(fn (Builder $sous) => $sous->where('titre', 'like', $motif)
                    ->orWhere('description', 'like', $motif));
            })
            ->when($filtres['localisation'] ?? null, fn (Builder $q, string $v) => $q->where('localisation', $v))
            ->when($filtres['type_contrat'] ?? null, fn (Builder $q, string $v) => $q->where('type_contrat', $v))
            ->when($filtres['id_departement'] ?? null, fn (Builder $q, $v) => $q->where('id_departement', $v))
            ->when($filtres['statut'] ?? null, fn (Builder $q, string $v) => $q->where('statut', $v))
            ->orderByDesc('date_publication')
            ->orderByDesc('id_offre')
            ->paginate(perPage: $filtres['per_page'] ?? 15)
            ->withQueryString();
    }

    /** Neutralise les jokers SQL saisis par l'utilisateur. */
    private function echapper(string $terme): string
    {
        return str_replace(['%', '_'], ['\%', '\_'], $terme);
    }
}
