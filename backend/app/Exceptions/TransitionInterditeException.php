<?php

namespace App\Exceptions;

use App\Enums\StatutCandidature;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Levée lorsqu'un changement de statut ne suit pas le cycle de vie d'une candidature (RG32). */
class TransitionInterditeException extends Exception
{
    public function __construct(
        private readonly StatutCandidature $depuis,
        private readonly StatutCandidature $vers,
    ) {
        parent::__construct(sprintf(
            'Une candidature « %s » ne peut pas passer à « %s ».',
            $depuis->libelle(),
            $vers->libelle(),
        ));
    }

    public function render(Request $request): ?JsonResponse
    {
        if (! $request->is('api/*')) {
            return null;
        }

        $autorises = array_map(
            fn (StatutCandidature $s) => $s->value,
            $this->depuis->suivantes(),
        );

        return response()->json([
            'message' => $this->getMessage(),
            'errors'  => [
                'statut' => [
                    $autorises === []
                        ? 'Cette candidature a reçu une décision définitive.'
                        : 'Statuts possibles depuis l\'état actuel : '.implode(', ', $autorises).'.',
                ],
            ],
        ], 422);
    }
}
