<?php

namespace App\Http\Controllers\Api;

use App\Http\Concerns\ReponsePaginee;
use App\Http\Controllers\Controller;
use App\Http\Requests\Entretien\ModifierEntretienRequest;
use App\Http\Requests\Entretien\PlanifierEntretienRequest;
use App\Http\Resources\EntretienResource;
use App\Models\Candidature;
use App\Models\Entretien;
use App\Models\Recruteur;
use App\Services\EntretienService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Entretiens liés aux candidatures (RG34, RG35, RG36).
 *
 * La propriété découle de la candidature : c'est EntretienPolicy qui tranche,
 * RG14 s'applique donc sans règle nouvelle.
 */
class EntretienController extends Controller
{
    use ReponsePaginee;

    public function __construct(private readonly EntretienService $entretiens) {}

    /** RG14 — entretiens relevant des offres publiées par le recruteur. */
    public function index(Request $request): JsonResponse
    {
        return $this->paginee(
            $this->entretiens->listerDuRecruteur($this->recruteur($request), $request->query()),
            'entretiens',
            EntretienResource::class,
        );
    }

    /** RG34 — entretiens d'une candidature donnée. */
    public function parCandidature(Request $request, Candidature $candidature): JsonResponse
    {
        $this->authorize('view', $candidature);

        return response()->json([
            'entretiens' => EntretienResource::collection(
                $this->entretiens->listerDeLaCandidature($candidature)
            ),
        ]);
    }

    /** RG34/RG35 — planification d'un entretien sur une candidature. */
    public function store(PlanifierEntretienRequest $request, Candidature $candidature): JsonResponse
    {
        $this->authorize('create', [Entretien::class, $candidature]);

        return response()->json([
            'message'   => 'Entretien planifié.',
            'entretien' => new EntretienResource(
                $this->entretiens->planifier($candidature, $request->validated())
            ),
        ], 201);
    }

    public function show(Request $request, Entretien $entretien): JsonResponse
    {
        $this->authorize('view', $entretien);

        $entretien->load(['candidature.candidat.user', 'candidature.offre']);

        return response()->json(['entretien' => new EntretienResource($entretien)]);
    }

    /** RG36 — mise à jour de la tenue ou de l'issue de l'entretien. */
    public function update(ModifierEntretienRequest $request, Entretien $entretien): JsonResponse
    {
        $this->authorize('update', $entretien);

        return response()->json([
            'message'   => 'Entretien mis à jour.',
            'entretien' => new EntretienResource(
                $this->entretiens->modifier($entretien, $request->validated())
            ),
        ]);
    }

    public function destroy(Request $request, Entretien $entretien): JsonResponse
    {
        $this->authorize('delete', $entretien);

        $this->entretiens->annuler($entretien);

        return response()->json([], 204);
    }

    /** RG7 — le profil recruteur du compte authentifié. */
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
