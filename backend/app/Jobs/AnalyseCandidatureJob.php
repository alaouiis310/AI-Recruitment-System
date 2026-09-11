<?php

namespace App\Jobs;

use App\Models\Candidature;
use App\Services\AnalyseIaService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * RG37 — analyse d'une candidature, exécutée en file d'attente.
 *
 * L'analyse lit un PDF et peut appeler un modèle de langage : elle est trop
 * lente pour la requête HTTP de dépôt de candidature, qui doit répondre
 * immédiatement.
 */
class AnalyseCandidatureJob implements ShouldQueue
{
    use Queueable;

    /** Trois tentatives, espacées, comme le worker configuré dans Docker. */
    public int $tries = 3;

    public array $backoff = [10, 60];

    public function __construct(public readonly int $idCandidature) {}

    public function handle(AnalyseIaService $analyses): void
    {
        $candidature = Candidature::find($this->idCandidature);

        // La candidature a pu être retirée entre-temps : ce n'est pas une
        // erreur, il n'y a simplement plus rien à analyser.
        if ($candidature === null) {
            return;
        }

        $analyses->analyser($candidature);
    }

    public function failed(\Throwable $e): void
    {
        Log::error("Analyse de la candidature {$this->idCandidature} échouée : {$e->getMessage()}");
    }
}
