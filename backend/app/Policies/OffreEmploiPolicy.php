<?php

namespace App\Policies;

use App\Models\OffreEmploi;
use App\Models\User;

class OffreEmploiPolicy
{
    /** Tout compte authentifié peut parcourir les offres. */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Une offre fermée, suspendue ou expirée reste visible de son auteur et de l'administrateur,
     * mais pas des candidats (RG17/RG18).
     */
    public function view(User $user, OffreEmploi $offre): bool
    {
        return $user->estAdministrateur()
            || $this->estSonOffre($user, $offre)
            || $offre->accepteCandidatures();
    }

    /** La publication d'une offre est le fait d'un recruteur (RG12). */
    public function create(User $user): bool
    {
        return $user->estAdministrateur() || $user->estRecruteur();
    }

    /** Un recruteur ne modifie que les offres qu'il a publiées (RG12/RG13). */
    public function update(User $user, OffreEmploi $offre): bool
    {
        return $user->estAdministrateur() || $this->estSonOffre($user, $offre);
    }

    /** Mêmes droits que la modification (RG12/RG13). */
    public function delete(User $user, OffreEmploi $offre): bool
    {
        return $this->update($user, $offre);
    }

    /** Chaque offre est publiée par un seul recruteur (RG13). */
    private function estSonOffre(User $user, OffreEmploi $offre): bool
    {
        return $user->estRecruteur()
            && $user->recruteur?->id_recruteur === $offre->id_recruteur;
    }
}
