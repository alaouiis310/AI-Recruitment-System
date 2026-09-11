<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DepartementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id_departement' => $this->id_departement,
            'nom'            => $this->nom,
            'description'    => $this->description,
            'id_entreprise'  => $this->id_entreprise,
            'date_creation'  => $this->created_at?->toDateString(),

            'entreprise' => new EntrepriseResource($this->whenLoaded('entreprise')),
        ];
    }
}
