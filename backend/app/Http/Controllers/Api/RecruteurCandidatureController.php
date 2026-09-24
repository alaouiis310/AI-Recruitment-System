<?php

namespace App\Http\Controllers\Api;

use App\Enums\StatutCandidature;
use App\Http\Concerns\ReponsePaginee;
use App\Http\Controllers\Controller;
use App\Http\Requests\Candidature\ChangerStatutRequest;
use App\Http\Requests\Candidature\ListerCandidaturesRequest;
use App\Http\Resources\CandidatureResource;
use App\Models\Candidature;
use App\Models\Recruteur;
use App\Services\CandidatureService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/** Candidatures reçues par le recruteur (RG14, RG30, RG32, RG43). */
class RecruteurCandidatureController extends Controller
{
    use ReponsePaginee;

    public function __construct(private readonly CandidatureService $candidatures) {}

    /**
     * Candidatures portant sur les offres publiées par le recruteur authentifié, classées par score
     * décroissant (RG14/RG43).
     */
    public function index(ListerCandidaturesRequest $request): JsonResponse
    {
        return $this->paginee(
            $this->candidatures->listerDuRecruteur($this->recruteur($request), $request->validated()),
            'candidatures',
            CandidatureResource::class,
        );
    }

    /** Consultation d'une candidature reçue sur une de ses offres (RG14). */
    public function show(Request $request, Candidature $candidature): JsonResponse
    {
        $this->authorize('view', $candidature);

        $candidature->load([
            'candidat.user',
            'candidat.competences',
            'offre.departement',
            'offre.competences',
        ]);

        return response()->json([
            'candidature' => new CandidatureResource($candidature),
        ]);
    }

    /** Avancement du dossier par le recruteur qui a publié l'offre (RG32). */
    public function changerStatut(ChangerStatutRequest $request, Candidature $candidature): JsonResponse
    {
        $this->authorize('traiter', $candidature);

        $donnees = $request->validated();

        $candidature = $this->candidatures->changerStatut(
            $candidature,
            StatutCandidature::from($donnees['statut']),
            $donnees['commentaire_recruteur'] ?? null,
        );

        return response()->json([
            'message'     => 'Statut de la candidature mis à jour.',
            'candidature' => new CandidatureResource($candidature),
        ]);
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
