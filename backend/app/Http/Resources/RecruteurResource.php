<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RecruteurResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id_recruteur' => $this->id_recruteur,
            'telephone'    => $this->telephone,
            'poste'        => $this->poste,
            'entreprise'   => new EntrepriseResource($this->whenLoaded('entreprise')),
            'utilisateur'  => new UserResource($this->whenLoaded('user')),
            'nombre_offres' => $this->whenCounted('offres'),
            'offres'        => OffreEmploiResource::collection($this->whenLoaded('offres')),
        ];
    }
}
