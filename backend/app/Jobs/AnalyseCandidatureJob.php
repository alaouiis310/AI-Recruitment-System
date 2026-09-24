<?php

namespace App\Jobs;

use App\Models\Candidature;
use App\Services\AnalyseIaService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/** Analyse d'une candidature, exécutée en file d'attente (RG37). */
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

        // La candidature a pu être retirée entre-temps.
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
