<?php

namespace App\Policies;

use App\Models\Competence;
use App\Models\User;

class CompetencePolicy
{
    /** Référentiel consultable par tout compte authentifié (RG20, RG25). */
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Competence $competence): bool
    {
        return true;
    }

    /** Le référentiel est maintenu par l'administrateur seul. */
    public function create(User $user): bool
    {
        return $user->estAdministrateur();
    }

    public function update(User $user, Competence $competence): bool
    {
        return $user->estAdministrateur();
    }

    public function delete(User $user, Competence $competence): bool
    {
        return $user->estAdministrateur();
    }
}
