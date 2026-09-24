<?php

namespace App\Services\Ia;

/** Contenu extrait d'un CV par le modèle (RG37). */
class ResultatExtraction
{
    /** @param  array<int, string>  $competencesDetectees */
    public function __construct(
        public readonly array $competencesDetectees = [],
        public readonly ?float $anneesExperience = null,
        public readonly ?string $diplome = null,
        public readonly ?string $resume = null,
    ) {}

    public function estVide(): bool
    {
        return $this->competencesDetectees === []
            && $this->anneesExperience === null
            && $this->diplome === null
            && $this->resume === null;
    }
}
