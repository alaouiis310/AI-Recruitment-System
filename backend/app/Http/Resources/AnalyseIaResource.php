<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AnalyseIaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id_analyse' => $this->id_analyse,

            // RG39/RG40 — scores calculés en PHP, jamais produits par le modèle.
            'score_matching'   => (float) $this->score_matching,
            'score_competence' => (float) $this->score_competence,
            'score_experience' => (float) $this->score_experience,
            'score_diplome'    => (float) $this->score_diplome,

            // RG41 — compétences exigées non couvertes au niveau demandé.
            'competences_manquantes' => $this->competences_manquantes ?? [],

            'resume_cv' => $this->resume_cv,

            // RG42 — déduite du score par des seuils explicites.
            'recommandation'         => $this->recommandation->value,
            'recommandation_libelle' => $this->recommandation->libelle(),

            'date_analyse'   => $this->date_analyse?->toDateString(),
            'id_candidature' => $this->id_candidature,
        ];
    }
}
