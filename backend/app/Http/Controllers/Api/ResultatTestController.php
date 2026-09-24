<?php

namespace App\Http\Controllers\Api;

use App\Http\Concerns\ReponsePaginee;
use App\Http\Controllers\Controller;
use App\Http\Requests\TestTechnique\EnregistrerScoreRequest;
use App\Http\Resources\ResultatTestResource;
use App\Models\Candidature;
use App\Models\Recruteur;
use App\Models\ResultatTest;
use App\Models\TestTechnique;
use App\Services\TestTechniqueService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Envoi et suivi des tests techniques (RG44, RG45).
 *
 * La propriété découle de la candidature : RG14 s'applique via
 * CandidaturePolicy, sans règle nouvelle.
 */
class ResultatTestController extends Controller
{
    use ReponsePaginee;

    public function __construct(private readonly TestTechniqueService $tests) {}

    /** RG14 — passages relevant des offres publiées par le recruteur. */
    public function index(Request $request): JsonResponse
    {
        return $this->paginee(
            $this->tests->listerDuRecruteur($this->recruteur($request)->id_recruteur, $request->query()),
            'resultats',
            ResultatTestResource::class,
        );
    }

    /** RG44/RG45 — envoie un test au candidat et l'en avise. */
    public function envoyer(Request $request, Candidature $candidature, TestTechnique $test): JsonResponse
    {
        $this->authorize('traiter', $candidature);

        return response()->json([
            'message'  => 'Test envoyé au candidat.',
            'resultat' => new ResultatTestResource(
                $this->tests->envoyerAuCandidat($candidature, $test)
            ),
        ], 201);
    }

    /** RG45 — enregistre le score obtenu. */
    public function enregistrerScore(EnregistrerScoreRequest $request, ResultatTest $resultat): JsonResponse
    {
        $this->authorize('traiter', $resultat->candidature);

        return response()->json([
            'message'  => 'Score enregistré.',
            'resultat' => new ResultatTestResource(
                $this->tests->enregistrerScore($resultat, $request->validated())
            ),
        ]);
    }

    /** Le candidat consulte les tests reçus sur sa candidature. */
    public function parCandidature(Request $request, Candidature $candidature): JsonResponse
    {
        $this->authorize('view', $candidature);

        return response()->json([
            'resultats' => ResultatTestResource::collection(
                $candidature->resultatsTests()->with('test')->get()
            ),
        ]);
    }

    private function recruteur(Request $request): Recruteur
    {
        $recruteur = $request->user()->recruteur;

        if ($recruteur === null) {
            throw new AccessDeniedHttpException(
                "Ces points d'accès sont réservés aux recruteurs rattachés à une entreprise."
            );
        }

        return $recruteur;
    }
}
