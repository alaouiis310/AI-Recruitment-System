<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CandidatResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id_candidat'       => $this->id_candidat,
            'telephone'         => $this->telephone,
            'telephone2'        => $this->telephone2,
            'adresse'           => $this->adresse,
            'date_naissance'    => $this->date_naissance?->toDateString(),
            'diplome'           => $this->diplome,
            'cv_pdf'            => $this->cv_pdf ? asset('storage/'.$this->cv_pdf) : null,
            'photo'             => $this->photo ? asset('storage/'.$this->photo) : null,
            'github'            => $this->github,
            'linkedin'          => $this->linkedin,
            'experience_totale' => (float) $this->experience_totale,

            'utilisateur'             => new UserResource($this->whenLoaded('user')),
            'nombre_candidatures'     => $this->whenCounted('candidatures'),
            'score_moyen'             => $this->when(
                array_key_exists('candidatures_avg_score_final', $this->getAttributes()),
                fn () => $this->candidatures_avg_score_final !== null
                    ? round((float) $this->candidatures_avg_score_final, 2)
                    : null,
            ),

            // Compétences déclarées via le pivot posseder (RG24/RG26).
            'competences' => CompetenceDeclareeResource::collection($this->whenLoaded('competences')),
            'candidatures' => CandidatureResource::collection($this->whenLoaded('candidatures')),
        ];
    }
}
