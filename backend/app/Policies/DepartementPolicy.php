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

    /** Le département est créé dans l'entreprise désignée par l'URL (RG9). */
    public function create(User $user, ?Entreprise $entreprise = null): bool
    {
        return $user->estAdministrateur()
            || ($entreprise !== null && $this->estSonEntreprise($user, $entreprise->id_entreprise));
    }

    /** Un recruteur ne gère que les départements de son entreprise (RG8/RG9). */
    public function update(User $user, Departement $departement): bool
    {
        return $user->estAdministrateur()
            || $this->estSonEntreprise($user, $departement->id_entreprise);
    }

    /** Mêmes droits que la modification (RG8/RG9). */
    public function delete(User $user, Departement $departement): bool
    {
        return $this->update($user, $departement);
    }

    /** Un recruteur appartient à exactement une entreprise (RG7). */
    private function estSonEntreprise(User $user, int $idEntreprise): bool
    {
        return $user->estRecruteur()
            && $user->recruteur?->id_entreprise === $idEntreprise;
    }
}
