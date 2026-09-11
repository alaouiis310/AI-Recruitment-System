<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AnalyseIaResource;
use App\Jobs\AnalyseCandidatureJob;
use App\Models\Candidature;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Analyse d'une candidature (RG37 à RG42).
 *
 * La consultation suit la même règle de propriété que la candidature
 * elle-même : c'est CandidaturePolicy qui tranche (RG14).
 */
class AnalyseIaController extends Controller
{
    /** RG37/RG38 — analyse de la candidature, si elle a déjà été produite. */
    public function show(Request $request, Candidature $candidature): JsonResponse
    {
        $this->authorize('view', $candidature);

        $analyse = $candidature->analyse;

        if ($analyse === null) {
            return response()->json([
                'message' => "Cette candidature n'a pas encore été analysée.",
                'analyse' => null,
            ], 404);
        }

        return response()->json([
            'analyse' => new AnalyseIaResource($analyse),
        ]);
    }

    /**
     * Relance l'analyse. Utile après une mise à jour du CV ou des compétences
     * déclarées, le score reflétant l'état du profil au moment du calcul.
     */
    public function relancer(Request $request, Candidature $candidature): JsonResponse
    {
        $this->authorize('traiter', $candidature);

        AnalyseCandidatureJob::dispatch($candidature->id_candidature);

        return response()->json([
            'message' => 'Analyse relancée. Le résultat sera disponible sous peu.',
        ], 202);
    }
}
