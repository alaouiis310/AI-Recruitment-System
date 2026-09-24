<?php

namespace App\Http\Controllers\Api;

use App\Enums\CategorieCompetence;
use App\Http\Concerns\ReponsePaginee;
use App\Http\Controllers\Controller;
use App\Http\Requests\Competence\CreerCompetenceRequest;
use App\Http\Requests\Competence\ListerCompetencesRequest;
use App\Http\Requests\Competence\ModifierCompetenceRequest;
use App\Http\Resources\CompetenceResource;
use App\Models\Competence;
use App\Services\CompetenceService;
use Illuminate\Http\JsonResponse;

class CompetenceController extends Controller
{
    use ReponsePaginee;

    public function __construct(private readonly CompetenceService $competences) {}

    /** Référentiel consultable par tout compte authentifié (RG20/RG25). */
    public function index(ListerCompetencesRequest $request): JsonResponse
    {
        $this->authorize('viewAny', Competence::class);

        return $this->paginee(
            $this->competences->lister($request->validated()),
            'competences',
            CompetenceResource::class,
        );
    }

    /** Catégories disponibles, pour alimenter les listes déroulantes du client. */
    public function categories(): JsonResponse
    {
        $this->authorize('viewAny', Competence::class);

        return response()->json([
            'categories' => array_map(
                fn (CategorieCompetence $c) => ['valeur' => $c->value, 'libelle' => $c->libelle()],
                CategorieCompetence::cases(),
            ),
        ]);
    }

    public function show(Competence $competence): JsonResponse
    {
        $this->authorize('view', $competence);

        return response()->json([
            'competence' => new CompetenceResource($competence),
        ]);
    }

    /** Le référentiel est maintenu par l'administrateur seul. */
    public function store(CreerCompetenceRequest $request): JsonResponse
    {
        $this->authorize('create', Competence::class);

        return response()->json([
            'message'    => 'Compétence créée avec succès.',
            'competence' => new CompetenceResource(
                $this->competences->creer($request->validated())
            ),
        ], 201);
    }

    public function update(ModifierCompetenceRequest $request, Competence $competence): JsonResponse
    {
        $this->authorize('update', $competence);

        return response()->json([
            'message'    => 'Compétence mise à jour.',
            'competence' => new CompetenceResource(
                $this->competences->modifier($competence, $request->validated())
            ),
        ]);
    }

    public function destroy(Competence $competence): JsonResponse
    {
        $this->authorize('delete', $competence);

        $this->competences->supprimer($competence);

        return response()->json([], 204);
    }
}
