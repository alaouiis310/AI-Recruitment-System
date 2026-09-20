<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CandidatureResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id_candidature' => $this->id_candidature,

            // RG33 — date de dépôt.
            'date_candidature'  => $this->date_candidature?->toDateString(),
            'lettre_motivation' => $this->lettre_motivation,

            // RG32 — statut et transitions encore possibles, de quoi construire
            // les actions proposées au recruteur sans les coder côté client.
            'statut'             => $this->statut->value,
            'statut_libelle'     => $this->statut->libelle(),
            'statut_definitif'   => $this->statut->estDefinitif(),
            'statuts_possibles'  => array_map(
                fn ($s) => ['valeur' => $s->value, 'libelle' => $s->libelle()],
                $this->statut->suivantes(),
            ),

            // RG40/RG43 — score retenu pour le classement, nul tant que
            // l'analyse du module 6 n'a pas abouti.
            'score_final' => $this->score_final !== null ? (float) $this->score_final : null,

            'date_decision'         => $this->date_decision?->toDateString(),
            'commentaire_recruteur' => $this->commentaire_recruteur,

            'id_candidat' => $this->id_candidat,
            'id_offre'    => $this->id_offre,

            'offre'    => new OffreEmploiResource($this->whenLoaded('offre')),
            'candidat' => new CandidatResource($this->whenLoaded('candidat')),

            'analyse'          => new AnalyseIaResource($this->whenLoaded('analyse')),
            'entretiens'      => EntretienResource::collection($this->whenLoaded('entretiens')),
            'resultats_tests' => ResultatTestResource::collection($this->whenLoaded('resultatsTests')),
        ];
    }
}
