<?php

namespace App\Policies;

use App\Models\Candidature;
use App\Models\User;

/** Un recruteur ne consulte que les candidatures portant sur les offres qu'il a lui-même publiées (RG14). */
class CandidaturePolicy
{
    /** Consultation : le candidat auteur, le recruteur de l'offre, l'administrateur. */
    public function view(User $user, Candidature $candidature): bool
    {
        return $user->estAdministrateur()
            || $this->estSaCandidature($user, $candidature)
            || $this->estSonOffre($user, $candidature);
    }

    /** Seul un candidat postule, et uniquement pour lui-même (RG27). */
    public function create(User $user): bool
    {
        return $user->estCandidat();
    }

    /** Le suivi du dossier appartient au recruteur qui a publié l'offre (RG32). */
    public function traiter(User $user, Candidature $candidature): bool
    {
        return $user->estAdministrateur() || $this->estSonOffre($user, $candidature);
    }

    /** Retrait de la candidature. */
    public function delete(User $user, Candidature $candidature): bool
    {
        if ($user->estAdministrateur()) {
            return true;
        }

        return $this->estSaCandidature($user, $candidature)
            && ! $candidature->statut->estDefinitif();
    }

    /** La candidature a été déposée par ce candidat (RG28). */
    private function estSaCandidature(User $user, Candidature $candidature): bool
    {
        return $user->estCandidat()
            && $user->candidat?->id_candidat === $candidature->id_candidat;
    }

    /** L'offre visée a été publiée par ce recruteur (RG13/RG14). */
    private function estSonOffre(User $user, Candidature $candidature): bool
    {
        if (! $user->estRecruteur()) {
            return false;
        }

        return $user->recruteur?->id_recruteur === $candidature->offre?->id_recruteur;
    }
}
