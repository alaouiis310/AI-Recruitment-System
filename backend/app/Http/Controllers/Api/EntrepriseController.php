<?php

namespace App\Http\Controllers\Api;

use App\Http\Concerns\ReponsePaginee;
use App\Http\Controllers\Controller;
use App\Http\Requests\Entreprise\CreerEntrepriseRequest;
use App\Http\Requests\Entreprise\ListerEntreprisesRequest;
use App\Http\Requests\Entreprise\ModifierEntrepriseRequest;
use App\Http\Resources\EntrepriseResource;
use App\Models\Entreprise;
use App\Services\EntrepriseService;
use Illuminate\Http\JsonResponse;

class EntrepriseController extends Controller
{
    use ReponsePaginee;

    public function __construct(private readonly EntrepriseService $entreprises) {}

    /** RG5 — liste paginée des entreprises, ouverte à tout compte authentifié. */
    public function index(ListerEntreprisesRequest $request): JsonResponse
    {
        $this->authorize('viewAny', Entreprise::class);

        return $this->paginee(
            $this->entreprises->lister($request->validated()),
            'entreprises',
            EntrepriseResource::class,
        );
    }

    /** RG5 — consultation d'une entreprise. */
    public function show(Entreprise $entreprise): JsonResponse
    {
        $this->authorize('view', $entreprise);

        $entreprise->loadCount(['departements', 'recruteurs']);

        return response()->json([
            'entreprise' => new EntrepriseResource($entreprise),
        ]);
    }

    /** RG5 — création d'une entreprise, réservée à l'administrateur. */
    public function store(CreerEntrepriseRequest $request): JsonResponse
    {
        $this->authorize('create', Entreprise::class);

        return response()->json([
            'message'    => 'Entreprise créée avec succès.',
            'entreprise' => new EntrepriseResource(
                $this->entreprises->creer($request->validated())
            ),
        ], 201);
    }

    /** RG6/RG7 — modification par l'administrateur ou par le recruteur employé. */
    public function update(ModifierEntrepriseRequest $request, Entreprise $entreprise): JsonResponse
    {
        $this->authorize('update', $entreprise);

        return response()->json([
            'message'    => 'Entreprise mise à jour.',
            'entreprise' => new EntrepriseResource(
                $this->entreprises->modifier($entreprise, $request->validated())
            ),
        ]);
    }

    /** RG6/RG8 — suppression refusée tant que l'entreprise n'est pas vide. */
    public function destroy(Entreprise $entreprise): JsonResponse
    {
        $this->authorize('delete', $entreprise);

        $this->entreprises->supprimer($entreprise);

        return response()->json([], 204);
    }
}
