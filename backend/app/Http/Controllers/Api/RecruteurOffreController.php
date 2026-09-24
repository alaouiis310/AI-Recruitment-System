<?php

namespace App\Http\Controllers\Api;

use App\Http\Concerns\ReponsePaginee;
use App\Http\Controllers\Controller;
use App\Http\Requests\Offre\CreerOffreRequest;
use App\Http\Requests\Offre\ListerOffresRequest;
use App\Http\Requests\Offre\ModifierOffreRequest;
use App\Http\Resources\OffreEmploiResource;
use App\Models\OffreEmploi;
use App\Models\Recruteur;
use App\Services\OffreEmploiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/** Gestion des offres par le recruteur qui les publie (RG12, RG13). */
class RecruteurOffreController extends Controller
{
    use ReponsePaginee;

    public function __construct(private readonly OffreEmploiService $offres) {}

    /** Offres publiées par le recruteur authentifié, tous statuts confondus (RG12). */
    public function index(ListerOffresRequest $request): JsonResponse
    {
        $this->authorize('viewAny', OffreEmploi::class);

        return $this->paginee(
            $this->offres->listerDuRecruteur($this->recruteur($request), $request->validated()),
            'offres',
            OffreEmploiResource::class,
        );
    }

    /** Publication d'une offre au nom du recruteur authentifié (RG12/RG13). */
    public function store(CreerOffreRequest $request): JsonResponse
    {
        $this->authorize('create', OffreEmploi::class);

        return response()->json([
            'message' => 'Offre publiée avec succès.',
            'offre'   => new OffreEmploiResource(
                $this->offres->creer($this->recruteur($request), $request->validated())
            ),
        ], 201);
    }

    /** Modification réservée au recruteur ayant publié l'offre (RG12/RG13). */
    public function update(ModifierOffreRequest $request, OffreEmploi $offre): JsonResponse
    {
        $this->authorize('update', $offre);

        return response()->json([
            'message' => 'Offre mise à jour.',
            'offre'   => new OffreEmploiResource(
                $this->offres->modifier($offre, $request->validated())
            ),
        ]);
    }

    /** Suppression réservée au recruteur ayant publié l'offre (RG12/RG13). */
    public function destroy(OffreEmploi $offre): JsonResponse
    {
        $this->authorize('delete', $offre);

        $this->offres->supprimer($offre);

        return response()->json([], 204);
    }

    /** Le profil recruteur du compte authentifié (RG7). */
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
