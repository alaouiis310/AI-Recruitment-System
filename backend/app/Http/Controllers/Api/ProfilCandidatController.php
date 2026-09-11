<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProfilCandidat\DeclarerCompetenceRequest;
use App\Http\Requests\ProfilCandidat\SynchroniserCompetencesRequest;
use App\Http\Requests\ProfilCandidat\TeleverserCvRequest;
use App\Http\Requests\ProfilCandidat\TeleverserPhotoRequest;
use App\Http\Resources\CandidatResource;
use App\Http\Resources\CompetenceDeclareeResource;
use App\Models\Candidat;
use App\Services\ProfilCandidatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Profil du candidat authentifié (RG22 à RG26).
 *
 * Tous ces points d'accès portent sur le profil du compte connecté : il n'y a
 * pas de propriété à trancher, la ressource est toujours la sienne (RG3).
 */
class ProfilCandidatController extends Controller
{
    public function __construct(private readonly ProfilCandidatService $profils) {}

    /** RG22/RG23 — dépôt ou remplacement du CV. */
    public function televerserCv(TeleverserCvRequest $request): JsonResponse
    {
        $candidat = $this->profils->remplacerCv($this->candidat($request), $request->file('cv'));

        return response()->json([
            'message' => 'CV enregistré.',
            'candidat' => new CandidatResource($candidat),
        ]);
    }

    /** RG22 — retrait du CV. */
    public function supprimerCv(Request $request): JsonResponse
    {
        return response()->json([
            'message'  => 'CV supprimé.',
            'candidat' => new CandidatResource($this->profils->supprimerCv($this->candidat($request))),
        ]);
    }

    /** Dépôt ou remplacement de la photo de profil. */
    public function televerserPhoto(TeleverserPhotoRequest $request): JsonResponse
    {
        $candidat = $this->profils->remplacerPhoto($this->candidat($request), $request->file('photo'));

        return response()->json([
            'message'  => 'Photo enregistrée.',
            'candidat' => new CandidatResource($candidat),
        ]);
    }

    /** Retrait de la photo de profil. */
    public function supprimerPhoto(Request $request): JsonResponse
    {
        return response()->json([
            'message'  => 'Photo supprimée.',
            'candidat' => new CandidatResource($this->profils->supprimerPhoto($this->candidat($request))),
        ]);
    }

    /** RG24/RG26 — compétences déclarées par le candidat. */
    public function competences(Request $request): JsonResponse
    {
        $candidat = $this->candidat($request)->load('competences');

        return response()->json([
            'competences' => CompetenceDeclareeResource::collection($candidat->competences),
        ]);
    }

    /** RG24/RG26 — remplacement complet de la liste des compétences. */
    public function synchroniserCompetences(SynchroniserCompetencesRequest $request): JsonResponse
    {
        $candidat = $this->profils->synchroniserCompetences(
            $this->candidat($request),
            $request->validated()['competences'],
        );

        return response()->json([
            'message'     => 'Compétences mises à jour.',
            'competences' => CompetenceDeclareeResource::collection($candidat->competences),
        ]);
    }

    /** RG24/RG26 — déclaration ou mise à jour d'une seule compétence. */
    public function declarerCompetence(DeclarerCompetenceRequest $request): JsonResponse
    {
        $candidat = $this->profils->declarerCompetence($this->candidat($request), $request->validated());

        return response()->json([
            'message'     => 'Compétence déclarée.',
            'competences' => CompetenceDeclareeResource::collection($candidat->competences),
        ], 201);
    }

    /** Retrait d'une compétence déclarée. */
    public function retirerCompetence(Request $request, int $competence): JsonResponse
    {
        $this->profils->retirerCompetence($this->candidat($request), $competence);

        return response()->json([], 204);
    }

    /** RG7-like : le profil candidat du compte authentifié. */
    private function candidat(Request $request): Candidat
    {
        return $request->user()->candidat;
    }
}
