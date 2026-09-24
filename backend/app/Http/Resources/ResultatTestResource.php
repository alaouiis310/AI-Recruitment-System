<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ResultatTestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id_resultat' => $this->id_resultat,

            'date_passage' => $this->date_passage?->toDateString(),
            'score_obtenu' => $this->score_obtenu,

            // Score ramene sur 100, quel que soit le bareme du test.
            'pourcentage' => $this->pourcentage(),

            'statut'         => $this->statut->value,
            'statut_libelle' => $this->statut->libelle(),
            'commentaire'    => $this->commentaire,

            'id_candidature' => $this->id_candidature,
            'id_test'        => $this->id_test,

            'test'        => new TestTechniqueResource($this->whenLoaded('test')),
            'candidature' => new CandidatureResource($this->whenLoaded('candidature')),
        ];
    }
}
