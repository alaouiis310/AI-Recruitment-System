<?php

namespace App\Policies;

use App\Models\TestTechnique;
use App\Models\User;

/**
 * RG45 — le catalogue des tests est consultable par les recruteurs et
 * maintenu par l'administrateur, à l'image du référentiel de compétences.
 */
class TestTechniquePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->estAdministrateur() || $user->estRecruteur();
    }

    public function view(User $user, TestTechnique $test): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->estAdministrateur();
    }

    public function update(User $user, TestTechnique $test): bool
    {
        return $user->estAdministrateur();
    }

    public function delete(User $user, TestTechnique $test): bool
    {
        return $user->estAdministrateur();
    }
}
