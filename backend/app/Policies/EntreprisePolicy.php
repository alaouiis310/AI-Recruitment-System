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

    /**
     * RG5 — la création d'une entreprise est réservée à l'administrateur.
     * Le recruteur crée la sienne lors de son inscription et, conformément
     * à RG7, il n'en a qu'une : il n'a donc pas d'autre entreprise à créer.
     */
    public function create(User $user): bool
    {
        return $user->estAdministrateur();
    }

    /** RG6/RG7 — un recruteur ne modifie que l'entreprise qui l'emploie. */
    public function update(User $user, Entreprise $entreprise): bool
    {
        return $user->estAdministrateur()
            || $this->estSonEntreprise($user, $entreprise->id_entreprise);
    }

    /**
     * Suppression réservée à l'administrateur : elle supprime en cascade les
     * recruteurs (RG6) et les départements (RG8) de l'entreprise.
     */
    public function delete(User $user, Entreprise $entreprise): bool
    {
        return $user->estAdministrateur();
    }

    /** RG7 — un recruteur appartient à exactement une entreprise. */
    private function estSonEntreprise(User $user, int $idEntreprise): bool
    {
        return $user->estRecruteur()
            && $user->recruteur?->id_entreprise === $idEntreprise;
    }
}
