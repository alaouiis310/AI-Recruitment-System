<?php

namespace App\Policies;

use App\Models\Candidature;
use App\Models\User;

/**
 * RG14 — un recruteur ne consulte que les candidatures portant sur les offres
 * qu'il a lui-même publiées. Ce n'est pas un contrôle de rôle : le recruteur a
 * le bon rôle, mais pas le droit sur *cet* enregistrement.
 */
class CandidaturePolicy
{
    /** Consultation : le candidat auteur, le recruteur de l'offre, l'administrateur. */
    public function view(User $user, Candidature $candidature): bool
    {
        return $user->estAdministrateur()
            || $this->estSaCandidature($user, $candidature)
            || $this->estSonOffre($user, $candidature);
    }

    /** RG27 — seul un candidat postule, et uniquement pour lui-même. */
    public function create(User $user): bool
    {
        return $user->estCandidat();
    }

    /**
     * RG32 — le suivi du dossier appartient au recruteur qui a publié l'offre.
     * Le candidat ne décide pas de l'avancement de sa propre candidature.
     */
    public function traiter(User $user, Candidature $candidature): bool
    {
        return $user->estAdministrateur() || $this->estSonOffre($user, $candidature);
    }

    /**
     * Retrait de la candidature : le fait du candidat qui l'a déposée, tant
     * qu'aucune décision définitive n'a été prise.
     */
    public function delete(User $user, Candidature $candidature): bool
    {
        if ($user->estAdministrateur()) {
            return true;
        }

        return $this->estSaCandidature($user, $candidature)
            && ! $candidature->statut->estDefinitif();
    }

    /** RG28 — la candidature a été déposée par ce candidat. */
    private function estSaCandidature(User $user, Candidature $candidature): bool
    {
        return $user->estCandidat()
            && $user->candidat?->id_candidat === $candidature->id_candidat;
    }

    /**
     * RG13/RG14 — l'offre visée a été publiée par ce recruteur. La relation
     * est chargée une fois, la comparaison porte ensuite sur la clé étrangère.
     */
    private function estSonOffre(User $user, Candidature $candidature): bool
    {
        if (! $user->estRecruteur()) {
            return false;
        }

        return $user->recruteur?->id_recruteur === $candidature->offre?->id_recruteur;
    }
}
