<?php

namespace App\Policies;

use App\Models\Candidature;
use App\Models\Entretien;
use App\Models\User;

/**
 * RG14/RG35 — la propriété d'un entretien découle de celle de sa
 * candidature : c'est le recruteur ayant publié l'offre qui le planifie et
 * le renseigne. Le candidat concerné le consulte sans pouvoir le modifier.
 */
class EntretienPolicy
{
    public function view(User $user, Entretien $entretien): bool
    {
        return $user->estAdministrateur()
            || $this->estSonEntretien($user, $entretien)
            || $this->estSaCandidature($user, $entretien->candidature);
    }

    /** RG34 — la planification est le fait du recruteur de l'offre. */
    public function create(User $user, ?Candidature $candidature = null): bool
    {
        if ($user->estAdministrateur()) {
            return true;
        }

        return $candidature !== null && $this->estSonOffre($user, $candidature);
    }

    public function update(User $user, Entretien $entretien): bool
    {
        return $user->estAdministrateur() || $this->estSonEntretien($user, $entretien);
    }

    public function delete(User $user, Entretien $entretien): bool
    {
        return $this->update($user, $entretien);
    }

    private function estSonEntretien(User $user, Entretien $entretien): bool
    {
        return $this->estSonOffre($user, $entretien->candidature);
    }

    /** RG13/RG14 — l'offre visée a été publiée par ce recruteur. */
    private function estSonOffre(User $user, ?Candidature $candidature): bool
    {
        if (! $user->estRecruteur() || $candidature === null) {
            return false;
        }

        return $user->recruteur?->id_recruteur === $candidature->offre?->id_recruteur;
    }

    /** RG28 — la candidature a été déposée par ce candidat. */
    private function estSaCandidature(User $user, ?Candidature $candidature): bool
    {
        return $user->estCandidat()
            && $candidature !== null
            && $user->candidat?->id_candidat === $candidature->id_candidat;
    }
}
