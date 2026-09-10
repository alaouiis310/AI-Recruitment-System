<?php

namespace App\Http\Resources;

use App\Enums\ImportanceCompetence;
use App\Enums\NiveauCompetence;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * RG21 — compétence exigée par une offre, vue à travers le pivot requerir.
 * Les attributs du pivot sont exposés à plat, à côté de la compétence.
 */
class CompetenceRequiseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $niveau     = NiveauCompetence::from($this->pivot->niveau_requis);
        $importance = ImportanceCompetence::from($this->pivot->importance);

        return [
            'id_competence' => $this->id_competence,
            'nom'           => $this->nom,
            'categorie'     => $this->categorie->value,

            'niveau_requis'         => $niveau->value,
            'niveau_requis_libelle' => $niveau->libelle(),
            'importance'            => $importance->value,
            'importance_libelle'    => $importance->libelle(),
        ];
    }
}
