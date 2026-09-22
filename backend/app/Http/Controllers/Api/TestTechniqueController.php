<?php

namespace App\Http\Controllers\Api;

use App\Http\Concerns\ReponsePaginee;
use App\Http\Controllers\Controller;
use App\Http\Requests\TestTechnique\CreerTestRequest;
use App\Http\Requests\TestTechnique\ModifierTestRequest;
use App\Http\Requests\TestTechnique\SynchroniserTestsOffreRequest;
use App\Http\Resources\OffreEmploiResource;
use App\Http\Resources\TestTechniqueResource;
use App\Models\OffreEmploi;
use App\Models\TestTechnique;
use App\Services\TestTechniqueService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Catalogue des tests techniques (RG45).
 *
 * Consultable par les recruteurs, maintenu par l'administrateur — même
 * partage que le référentiel de compétences du module 2.
 */
class TestTechniqueController extends Controller
{
    use ReponsePaginee;

    public function __construct(private readonly TestTechniqueService $tests) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', TestTechnique::class);

        return $this->paginee(
            $this->tests->lister($request->query()),
            'tests',
            TestTechniqueResource::class,
        );
    }

    public function show(Request $request, TestTechnique $test): JsonResponse
    {
        $this->authorize('view', $test);

        return response()->json(['test' => new TestTechniqueResource($test)]);
    }

    public function store(CreerTestRequest $request): JsonResponse
    {
        $this->authorize('create', TestTechnique::class);

        return response()->json([
            'message' => 'Test technique créé.',
            'test'    => new TestTechniqueResource($this->tests->creer($request->validated())),
        ], 201);
    }

    public function update(ModifierTestRequest $request, TestTechnique $test): JsonResponse
    {
        $this->authorize('update', $test);

        return response()->json([
            'message' => 'Test technique mis à jour.',
            'test'    => new TestTechniqueResource($this->tests->modifier($test, $request->validated())),
        ]);
    }

    public function destroy(Request $request, TestTechnique $test): JsonResponse
    {
        $this->authorize('delete', $test);

        $this->tests->supprimer($test);

        return response()->json([], 204);
    }

    /** RG45 — remplace la liste des tests techniques proposés pour une offre. */
    public function synchroniserOffre(
        SynchroniserTestsOffreRequest $request,
        OffreEmploi $offre,
    ): JsonResponse {
        $this->authorize('update', $offre);

        return response()->json([
            'message' => "Tests techniques de l'offre mis à jour.",
            'offre' => new OffreEmploiResource(
                $this->tests->rattacherAOffre($offre, $request->validated()['tests'])
            ),
        ]);
    }
}
