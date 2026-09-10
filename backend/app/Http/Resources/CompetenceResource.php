<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompetenceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id_competence'    => $this->id_competence,
            'nom'              => $this->nom,
            'categorie'        => $this->categorie->value,
            'categorie_libelle' => $this->categorie->libelle(),
            'description'      => $this->description,
            'date_creation'    => $this->created_at?->toDateString(),
        ];
    }
}
