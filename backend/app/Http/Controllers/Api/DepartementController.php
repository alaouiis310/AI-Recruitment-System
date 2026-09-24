<?php

namespace App\Http\Controllers\Api;

use App\Http\Concerns\ReponsePaginee;
use App\Http\Controllers\Controller;
use App\Http\Requests\Departement\CreerDepartementRequest;
use App\Http\Requests\Departement\ListerDepartementsRequest;
use App\Http\Requests\Departement\ModifierDepartementRequest;
use App\Http\Resources\DepartementResource;
use App\Models\Departement;
use App\Models\Entreprise;
use App\Services\DepartementService;
use Illuminate\Http\JsonResponse;

class DepartementController extends Controller
{
    use ReponsePaginee;

    public function __construct(private readonly DepartementService $departements) {}

    /** Liste paginée des départements d'une entreprise (RG8). */
    public function index(ListerDepartementsRequest $request, Entreprise $entreprise): JsonResponse
    {
        $this->authorize('viewAny', [Departement::class, $entreprise]);

        return $this->paginee(
            $this->departements->lister($entreprise, $request->validated()),
            'departements',
            DepartementResource::class,
        );
    }

    /** Création d'un département dans l'entreprise désignée par l'URL (RG9). */
    public function store(CreerDepartementRequest $request, Entreprise $entreprise): JsonResponse
    {
        $this->authorize('create', [Departement::class, $entreprise]);

        return response()->json([
            'message'     => 'Département créé avec succès.',
            'departement' => new DepartementResource(
                $this->departements->creer($entreprise, $request->validated())
            ),
        ], 201);
    }

    /** Consultation d'un département (RG8). */
    public function show(Departement $departement): JsonResponse
    {
        $this->authorize('view', $departement);

        return response()->json([
            'departement' => new DepartementResource($departement),
        ]);
    }

    /** Modification par l'administrateur ou le recruteur de l'entreprise (RG8/RG9). */
    public function update(ModifierDepartementRequest $request, Departement $departement): JsonResponse
    {
        $this->authorize('update', $departement);

        return response()->json([
            'message'     => 'Département mis à jour.',
            'departement' => new DepartementResource(
                $this->departements->modifier($departement, $request->validated())
            ),
        ]);
    }

    /** Suppression par l'administrateur ou le recruteur de l'entreprise (RG8/RG9). */
    public function destroy(Departement $departement): JsonResponse
    {
        $this->authorize('delete', $departement);

        $this->departements->supprimer($departement);

        return response()->json([], 204);
    }
}
