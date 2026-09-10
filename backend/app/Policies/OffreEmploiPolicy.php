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
     * RG17/RG18 — une offre fermée, suspendue ou expirée reste visible de son
     * auteur et de l'administrateur, mais pas des candidats.
     */
    public function view(User $user, OffreEmploi $offre): bool
    {
        return $user->estAdministrateur()
            || $this->estSonOffre($user, $offre)
            || $offre->accepteCandidatures();
    }

    /** RG12 — la publication d'une offre est le fait d'un recruteur. */
    public function create(User $user): bool
    {
        return $user->estAdministrateur() || $user->estRecruteur();
    }

    /** RG12/RG13 — un recruteur ne modifie que les offres qu'il a publiées. */
    public function update(User $user, OffreEmploi $offre): bool
    {
        return $user->estAdministrateur() || $this->estSonOffre($user, $offre);
    }

    /** RG12/RG13 — mêmes droits que la modification. */
    public function delete(User $user, OffreEmploi $offre): bool
    {
        return $this->update($user, $offre);
    }

    /**
     * RG13 — chaque offre est publiée par un seul recruteur. La comparaison
     * porte sur la clé étrangère de l'offre, sans charger la relation.
     */
    private function estSonOffre(User $user, OffreEmploi $offre): bool
    {
        return $user->estRecruteur()
            && $user->recruteur?->id_recruteur === $offre->id_recruteur;
    }
}
