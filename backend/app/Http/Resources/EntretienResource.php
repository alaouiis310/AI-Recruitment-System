<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EntretienResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id_entretien' => $this->id_entretien,

            // Date, heure, mode et résultat (RG36).
            'date'  => $this->date?->toDateString(),
            'heure' => $this->heure,

            'mode'         => $this->mode->value,
            'mode_libelle' => $this->mode->libelle(),

            'lien_si_online' => $this->lien_si_online,
            'commentaire'    => $this->commentaire,

            'resultat'         => $this->resultat->value,
            'resultat_libelle' => $this->resultat->libelle(),
            'a_venir'          => $this->estAVenir(),

            'id_candidature' => $this->id_candidature,

            'candidature' => new CandidatureResource($this->whenLoaded('candidature')),
        ];
    }
}
