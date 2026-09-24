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

    /** Liste paginée des entreprises, ouverte à tout compte authentifié (RG5). */
    public function index(ListerEntreprisesRequest $request): JsonResponse
    {
        $this->authorize('viewAny', Entreprise::class);

        return $this->paginee(
            $this->entreprises->lister($request->validated()),
            'entreprises',
            EntrepriseResource::class,
        );
    }

    /** Consultation d'une entreprise (RG5). */
    public function show(Entreprise $entreprise): JsonResponse
    {
        $this->authorize('view', $entreprise);

        $entreprise->loadCount(['departements', 'recruteurs']);

        return response()->json([
            'entreprise' => new EntrepriseResource($entreprise),
        ]);
    }

    /** Création d'une entreprise, réservée à l'administrateur (RG5). */
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

    /** Modification par l'administrateur ou par le recruteur employé (RG6/RG7). */
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

    /** Suppression refusée tant que l'entreprise n'est pas vide (RG6/RG8). */
    public function destroy(Entreprise $entreprise): JsonResponse
    {
        $this->authorize('delete', $entreprise);

        $this->entreprises->supprimer($entreprise);

        return response()->json([], 204);
    }
}
