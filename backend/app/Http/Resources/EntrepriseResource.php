<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EntrepriseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id_entreprise' => $this->id_entreprise,
            'nom'           => $this->nom,
            'secteur'       => $this->secteur,
            'adresse'       => $this->adresse,
            'ville'         => $this->ville,
            'site_web'      => $this->site_web,
            'description'   => $this->description,
            'date_creation' => $this->created_at?->toDateString(),

            // Décomptes exposés uniquement lorsqu'ils ont été chargés (RG6/RG8).
            'nombre_recruteurs'   => $this->whenCounted('recruteurs'),
            'nombre_departements' => $this->whenCounted('departements'),

            'departements' => DepartementResource::collection($this->whenLoaded('departements')),
        ];
    }
}
