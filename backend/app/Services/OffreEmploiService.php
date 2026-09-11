<?php

namespace App\Services;

use App\Models\OffreEmploi;
use App\Models\Recruteur;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Logique métier des offres d'emploi (RG10 à RG21).
 */
class OffreEmploiService
{
    /**
     * RG17/RG18 — offres visibles des candidats : ouvertes et non expirées.
     */
    public function listerPubliques(array $filtres): LengthAwarePaginator
    {
        return $this->appliquerFiltres(OffreEmploi::query()->publiable(), $filtres);
    }

    /**
     * RG12 — offres publiées par un recruteur donné, tous statuts confondus.
     */
    public function listerDuRecruteur(Recruteur $recruteur, array $filtres): LengthAwarePaginator
    {
        return $this->appliquerFiltres($recruteur->offres()->getQuery(), $filtres);
    }

    /**
     * RG12/RG13 — publication d'une offre par le recruteur authentifié.
     *
     * L'offre et ses compétences requises forment une seule opération :
     * l'écriture porte sur deux tables, elle est donc transactionnelle.
     */
    public function creer(Recruteur $recruteur, array $donnees): OffreEmploi
    {
        return DB::transaction(function () use ($recruteur, $donnees) {
            $competences = $donnees['competences'] ?? [];
            unset($donnees['competences']);

            // RG16 — à défaut de date fournie, l'offre est publiée ce jour.
            $donnees['date_publication'] ??= now()->toDateString();

            $offre = $recruteur->offres()->create($donnees);

            if ($competences) {
                $offre->competences()->sync($this->pivot($competences));
            }

            // fresh() et non load() : statut et experience_min proviennent de
            // valeurs par défaut de la base, absentes du modèle en mémoire.
            return $offre->fresh(['departement', 'competences']);
        });
    }

    /** RG12/RG13 — mise à jour d'une offre et, le cas échéant, de ses compétences. */
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
        // RG30 — lorsque la table candidatures existera, la suppression d'une
        // offre ayant reçu des candidatures devra être refusée.
        $offre->delete();
    }

    /**
     * RG21 — met le pivot requerir en forme attendue par sync() :
     * la clé est l'identifiant de la compétence, la valeur ses attributs.
     */
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
            ->with(['departement.entreprise', 'competences'])
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
