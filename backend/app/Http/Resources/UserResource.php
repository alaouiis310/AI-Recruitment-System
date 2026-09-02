<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id_user'       => $this->id,
            'nom'           => $this->name,
            'prenom'        => $this->prenom,
            'nom_complet'   => $this->nomComplet(),
            'email'         => $this->email,
            'role'          => $this->role->value,
            'role_libelle'  => $this->role->libelle(),
            'etat_compte'   => $this->etat_compte->value,
            'date_creation' => $this->created_at?->toDateString(),

            'profil_candidat'  => new CandidatResource($this->whenLoaded('candidat')),
            'profil_recruteur' => new RecruteurResource($this->whenLoaded('recruteur')),
        ];
    }
}
