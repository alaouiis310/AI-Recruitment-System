<?php

namespace App\Policies;

use App\Models\Candidature;
use App\Models\Entretien;
use App\Models\User;

/** La propriété d'un entretien découle de celle de sa candidature (RG14/RG35). */
class EntretienPolicy
{
    public function view(User $user, Entretien $entretien): bool
    {
        return $user->estAdministrateur()
            || $this->estSonEntretien($user, $entretien)
            || $this->estSaCandidature($user, $entretien->candidature);
    }

    /** La planification est le fait du recruteur de l'offre (RG34). */
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

    /** L'offre visée a été publiée par ce recruteur (RG13/RG14). */
    private function estSonOffre(User $user, ?Candidature $candidature): bool
    {
        if (! $user->estRecruteur() || $candidature === null) {
            return false;
        }

        return $user->recruteur?->id_recruteur === $candidature->offre?->id_recruteur;
    }

    /** La candidature a été déposée par ce candidat (RG28). */
    private function estSaCandidature(User $user, ?Candidature $candidature): bool
    {
        return $user->estCandidat()
            && $candidature !== null
            && $user->candidat?->id_candidat === $candidature->id_candidat;
    }
}
