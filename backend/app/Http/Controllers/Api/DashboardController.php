<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CandidatureResource;
use App\Http\Resources\EntretienResource;
use App\Http\Resources\OffreEmploiResource;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(private readonly DashboardService $dashboard) {}

    public function candidat(Request $request): JsonResponse
    {
        $donnees = $this->dashboard->candidat($request->user()->candidat);

        return response()->json([
            'statistiques' => $donnees['statistiques'],
            'candidatures_recentes' => CandidatureResource::collection($donnees['candidatures_recentes']),
            'entretiens_a_venir' => EntretienResource::collection($donnees['entretiens_a_venir']),
            'offres_recentes' => OffreEmploiResource::collection($donnees['offres_recentes']),
        ]);
    }

    public function recruteur(Request $request): JsonResponse
    {
        $donnees = $this->dashboard->recruteur($request->user()->recruteur);

        return response()->json([
            'statistiques' => $donnees['statistiques'],
            'candidatures_recentes' => CandidatureResource::collection($donnees['candidatures_recentes']),
            'offres_recentes' => OffreEmploiResource::collection($donnees['offres_recentes']),
            'entretiens_a_venir' => EntretienResource::collection($donnees['entretiens_a_venir']),
        ]);
    }
}
