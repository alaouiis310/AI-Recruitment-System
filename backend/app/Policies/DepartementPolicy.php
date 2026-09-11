<?php

namespace App\Policies;

use App\Models\Departement;
use App\Models\Entreprise;
use App\Models\User;

class DepartementPolicy
{
    /** Tout utilisateur authentifié peut consulter les départements d'une entreprise. */
    public function viewAny(User $user, ?Entreprise $entreprise = null): bool
    {
        return true;
    }

    /** Tout utilisateur authentifié peut consulter un département. */
    public function view(User $user, Departement $departement): bool
    {
        return true;
    }

    /**
     * RG9 — le département est créé dans l'entreprise désignée par l'URL :
     * administrateur, ou recruteur employé par cette entreprise.
     */
    public function create(User $user, ?Entreprise $entreprise = null): bool
    {
        return $user->estAdministrateur()
            || ($entreprise !== null && $this->estSonEntreprise($user, $entreprise->id_entreprise));
    }

    /** RG8/RG9 — un recruteur ne gère que les départements de son entreprise. */
    public function update(User $user, Departement $departement): bool
    {
        return $user->estAdministrateur()
            || $this->estSonEntreprise($user, $departement->id_entreprise);
    }

    /** RG8/RG9 — mêmes droits que la modification. */
    public function delete(User $user, Departement $departement): bool
    {
        return $this->update($user, $departement);
    }

    /** RG7 — un recruteur appartient à exactement une entreprise. */
    private function estSonEntreprise(User $user, int $idEntreprise): bool
    {
        return $user->estRecruteur()
            && $user->recruteur?->id_entreprise === $idEntreprise;
    }
}
