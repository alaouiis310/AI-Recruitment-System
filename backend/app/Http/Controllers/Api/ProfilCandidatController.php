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

/** Profil du candidat authentifié (RG22 à RG26). */
class ProfilCandidatController extends Controller
{
    public function __construct(private readonly ProfilCandidatService $profils) {}

    /** Dépôt ou remplacement du CV (RG22/RG23). */
    public function televerserCv(TeleverserCvRequest $request): JsonResponse
    {
        $candidat = $this->profils->remplacerCv($this->candidat($request), $request->file('cv'));

        return response()->json([
            'message' => 'CV enregistré.',
            'candidat' => new CandidatResource($candidat),
        ]);
    }

    /** Retrait du CV (RG22). */
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

    /** Compétences déclarées par le candidat (RG24/RG26). */
    public function competences(Request $request): JsonResponse
    {
        $candidat = $this->candidat($request)->load('competences');

        return response()->json([
            'competences' => CompetenceDeclareeResource::collection($candidat->competences),
        ]);
    }

    /** Remplacement complet de la liste des compétences (RG24/RG26). */
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

    /** Déclaration ou mise à jour d'une seule compétence (RG24/RG26). */
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
