<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OffreEmploiResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id_offre'     => $this->id_offre,
            'titre'        => $this->titre,
            'description'  => $this->description,
            'localisation' => $this->localisation,

            'type_contrat'         => $this->type_contrat->value,
            'type_contrat_libelle' => $this->type_contrat->libelle(),

            'salaire'        => $this->salaire !== null ? (float) $this->salaire : null,
            'experience_min' => (float) $this->experience_min,

            'niveau_etude'         => $this->niveau_etude?->value,
            'niveau_etude_libelle' => $this->niveau_etude?->libelle(),

            // RG16/RG17 — dates de publication et d'expiration.
            'date_publication' => $this->date_publication?->toDateString(),
            'date_expiration'  => $this->date_expiration?->toDateString(),
            'expiree'          => $this->estExpiree(),

            // RG18 — statut de l'offre.
            'statut'                 => $this->statut->value,
            'statut_libelle'         => $this->statut->libelle(),
            'accepte_candidatures'   => $this->accepteCandidatures(),

            'id_recruteur'   => $this->id_recruteur,
            'id_departement' => $this->id_departement,

            'departement' => new DepartementResource($this->whenLoaded('departement')),
            'recruteur'   => new RecruteurResource($this->whenLoaded('recruteur')),

            // RG19/RG21 — compétences requises et attributs du pivot requerir.
            'competences' => CompetenceRequiseResource::collection($this->whenLoaded('competences')),
            'tests'       => TestTechniqueResource::collection($this->whenLoaded('tests')),
            'nombre_candidatures' => $this->whenCounted('candidatures'),
        ];
    }
}
