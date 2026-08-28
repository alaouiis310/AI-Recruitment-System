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
        ];
    }
}
