<?php

namespace App\Policies;

use App\Models\Entreprise;
use App\Models\User;

class EntreprisePolicy
{
    /** Tout utilisateur authentifié peut consulter la liste des entreprises. */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /** Tout utilisateur authentifié peut consulter une entreprise. */
    public function view(User $user, Entreprise $entreprise): bool
    {
        return true;
    }

    /** La création d'une entreprise est réservée à l'administrateur (RG5). */
    public function create(User $user): bool
    {
        return $user->estAdministrateur();
    }

    /** Un recruteur ne modifie que l'entreprise qui l'emploie (RG6/RG7). */
    public function update(User $user, Entreprise $entreprise): bool
    {
        return $user->estAdministrateur()
            || $this->estSonEntreprise($user, $entreprise->id_entreprise);
    }

    /** Suppression réservée à l'administrateur. */
    public function delete(User $user, Entreprise $entreprise): bool
    {
        return $user->estAdministrateur();
    }

    /** Un recruteur appartient à exactement une entreprise (RG7). */
    private function estSonEntreprise(User $user, int $idEntreprise): bool
    {
        return $user->estRecruteur()
            && $user->recruteur?->id_entreprise === $idEntreprise;
    }
}
