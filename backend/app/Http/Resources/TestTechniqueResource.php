<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TestTechniqueResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id_test'     => $this->id_test,
            'titre'       => $this->titre,
            'description' => $this->description,
            'duree'       => $this->duree,
            'score_max'   => $this->score_max,
        ];
    }
}
