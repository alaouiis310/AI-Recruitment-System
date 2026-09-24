<?php

namespace App\Http\Resources;

use App\Enums\NiveauCompetence;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Compétence déclarée par un candidat, vue à travers le pivot posseder (RG26). */
class CompetenceDeclareeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $niveau = NiveauCompetence::from($this->pivot->niveau);

        return [
            'id_competence' => $this->id_competence,
            'nom'           => $this->nom,
            'categorie'     => $this->categorie->value,

            'niveau'            => $niveau->value,
            'niveau_libelle'    => $niveau->libelle(),
            'annees_experience' => (float) $this->pivot->annees_experience,
        ];
    }
}
