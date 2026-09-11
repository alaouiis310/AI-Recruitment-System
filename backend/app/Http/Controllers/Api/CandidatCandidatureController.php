<?php

namespace App\Http\Controllers\Api;

use App\Http\Concerns\ReponsePaginee;
use App\Http\Controllers\Controller;
use App\Http\Requests\Candidature\ListerCandidaturesRequest;
use App\Http\Requests\Candidature\PostulerRequest;
use App\Http\Resources\CandidatureResource;
use App\Models\Candidat;
use App\Models\Candidature;
use App\Models\OffreEmploi;
use App\Services\CandidatureService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Candidatures vues du côté candidat (RG27, RG31, RG33).
 */
class CandidatCandidatureController extends Controller
{
    use ReponsePaginee;

    public function __construct(private readonly CandidatureService $candidatures) {}

    /** RG27 — candidatures déposées par le candidat authentifié. */
    public function index(ListerCandidaturesRequest $request): JsonResponse
    {
        return $this->paginee(
            $this->candidatures->listerDuCandidat($this->candidat($request), $request->validated()),
            'candidatures',
            CandidatureResource::class,
        );
    }

    /** RG27/RG31 — dépôt d'une candidature sur une offre ouverte. */
    public function store(PostulerRequest $request): JsonResponse
    {
        $this->authorize('create', Candidature::class);

        $offre = OffreEmploi::findOrFail($request->validated()['id_offre']);

        return response()->json([
            'message'     => 'Candidature envoyée.',
            'candidature' => new CandidatureResource(
                $this->candidatures->postuler($this->candidat($request), $offre, $request->validated())
            ),
        ], 201);
    }

    /** Consultation d'une de ses candidatures. */
    public function show(Request $request, Candidature $candidature): JsonResponse
    {
        $this->authorize('view', $candidature);

        $candidature->load(['offre.departement.entreprise', 'offre.competences']);

        return response()->json([
            'candidature' => new CandidatureResource($candidature),
        ]);
    }

    /** Retrait d'une candidature, tant qu'aucune décision définitive n'a été prise. */
    public function destroy(Request $request, Candidature $candidature): JsonResponse
    {
        $this->authorize('delete', $candidature);

        $this->candidatures->retirer($candidature);

        return response()->json([], 204);
    }

    /** Le profil candidat du compte authentifié. */
    private function candidat(Request $request): Candidat
    {
        return $request->user()->candidat;
    }
}
