<?php

namespace App\Http\Controllers\Api;

use App\Http\Concerns\ReponsePaginee;
use App\Http\Controllers\Controller;
use App\Http\Requests\Offre\ListerOffresRequest;
use App\Http\Resources\OffreEmploiResource;
use App\Models\OffreEmploi;
use App\Services\OffreEmploiService;
use Illuminate\Http\JsonResponse;

/**
 * Consultation des offres ouverte à tout compte authentifié (RG15 à RG18).
 * La gestion par le recruteur publiant relève de RecruteurOffreController.
 */
class OffreEmploiController extends Controller
{
    use ReponsePaginee;

    public function __construct(private readonly OffreEmploiService $offres) {}

    /** RG17/RG18 — seules les offres ouvertes et non expirées sont listées. */
    public function index(ListerOffresRequest $request): JsonResponse
    {
        $this->authorize('viewAny', OffreEmploi::class);

        return $this->paginee(
            $this->offres->listerPubliques($request->validated()),
            'offres',
            OffreEmploiResource::class,
        );
    }

    /** Consultation d'une offre ; une offre non publiable reste réservée à son auteur. */
    public function show(OffreEmploi $offre): JsonResponse
    {
        $this->authorize('view', $offre);

        $offre->load(['departement.entreprise', 'recruteur.entreprise', 'competences']);

        return response()->json([
            'offre' => new OffreEmploiResource($offre),
        ]);
    }
}
