<?php

namespace App\Http\Controllers\Api;

use App\Enums\EtatCompte;
use App\Enums\StatutCandidature;
use App\Http\Concerns\ReponsePaginee;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ChangerEtatCompteRequest;
use App\Http\Requests\Admin\CreerOffreAdminRequest;
use App\Http\Requests\Admin\ListerUtilisateursRequest;
use App\Http\Requests\Auth\InscriptionCandidatRequest;
use App\Http\Requests\Auth\InscriptionRecruteurRequest;
use App\Http\Requests\Candidature\ChangerStatutRequest;
use App\Http\Requests\Candidature\ListerCandidaturesRequest;
use App\Http\Requests\Offre\ListerOffresRequest;
use App\Http\Requests\Offre\ModifierOffreRequest;
use App\Http\Resources\CandidatResource;
use App\Http\Resources\CandidatureResource;
use App\Http\Resources\OffreEmploiResource;
use App\Http\Resources\RecruteurResource;
use App\Http\Resources\UserResource;
use App\Models\Candidat;
use App\Models\Candidature;
use App\Models\OffreEmploi;
use App\Models\Recruteur;
use App\Models\User;
use App\Services\AdminService;
use App\Services\AuthService;
use App\Services\CandidatureService;
use App\Services\OffreEmploiService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminController extends Controller
{
    use ReponsePaginee;

    public function __construct(
        private readonly AdminService $admin,
        private readonly AuthService $auth,
        private readonly OffreEmploiService $offres,
        private readonly CandidatureService $candidatures,
    ) {}

    public function tableauDeBord(): JsonResponse
    {
        return response()->json([
            'statistiques' => $this->admin->statistiques(),
            'candidatures_recentes' => CandidatureResource::collection(
                $this->admin->dernieresCandidatures()
            ),
        ]);
    }

    public function candidats(ListerUtilisateursRequest $request): JsonResponse
    {
        return $this->paginee(
            $this->admin->listerCandidats($request->validated()),
            'candidats',
            CandidatResource::class,
        );
    }

    public function showCandidat(Candidat $candidat): JsonResponse
    {
        $candidat->load(['user', 'competences', 'candidatures.offre'])
            ->loadCount('candidatures')
            ->loadAvg('candidatures', 'score_final');

        return response()->json(['candidat' => new CandidatResource($candidat)]);
    }

    public function storeCandidat(InscriptionCandidatRequest $request): JsonResponse
    {
        return response()->json([
            'message' => 'Compte candidat créé.',
            'utilisateur' => new UserResource($this->auth->inscrireCandidat($request->validated())),
        ], 201);
    }

    public function recruteurs(ListerUtilisateursRequest $request): JsonResponse
    {
        return $this->paginee(
            $this->admin->listerRecruteurs($request->validated()),
            'recruteurs',
            RecruteurResource::class,
        );
    }

    public function showRecruteur(Recruteur $recruteur): JsonResponse
    {
        $recruteur->load(['user', 'entreprise', 'offres.departement'])
            ->loadCount('offres');

        return response()->json(['recruteur' => new RecruteurResource($recruteur)]);
    }

    public function storeRecruteur(InscriptionRecruteurRequest $request): JsonResponse
    {
        return response()->json([
            'message' => 'Compte recruteur créé.',
            'utilisateur' => new UserResource($this->auth->inscrireRecruteur($request->validated())),
        ], 201);
    }

    public function changerEtat(
        ChangerEtatCompteRequest $request,
        User $utilisateur,
    ): JsonResponse {
        $etat = EtatCompte::from($request->validated()['etat_compte']);

        return response()->json([
            'message' => 'État du compte mis à jour.',
            'utilisateur' => new UserResource(
                $this->admin->changerEtat($request->user(), $utilisateur, $etat)
            ),
        ]);
    }

    public function offres(ListerOffresRequest $request): JsonResponse
    {
        return $this->paginee(
            $this->offres->listerToutes($request->validated()),
            'offres',
            OffreEmploiResource::class,
        );
    }

    public function showOffre(OffreEmploi $offre): JsonResponse
    {
        $offre->load(['departement.entreprise', 'recruteur.user', 'recruteur.entreprise', 'competences', 'tests'])
            ->loadCount('candidatures');

        return response()->json(['offre' => new OffreEmploiResource($offre)]);
    }

    public function storeOffre(CreerOffreAdminRequest $request): JsonResponse
    {
        $donnees = $request->validated();
        $recruteur = Recruteur::findOrFail($donnees['id_recruteur']);
        unset($donnees['id_recruteur']);

        return response()->json([
            'message' => 'Offre créée.',
            'offre' => new OffreEmploiResource($this->offres->creer($recruteur, $donnees)),
        ], 201);
    }

    public function updateOffre(ModifierOffreRequest $request, OffreEmploi $offre): JsonResponse
    {
        $this->authorize('update', $offre);

        return response()->json([
            'message' => 'Offre mise à jour.',
            'offre' => new OffreEmploiResource($this->offres->modifier($offre, $request->validated())),
        ]);
    }

    public function destroyOffre(OffreEmploi $offre): JsonResponse
    {
        $this->authorize('delete', $offre);
        $this->offres->supprimer($offre);

        return response()->json([], 204);
    }

    public function candidatures(ListerCandidaturesRequest $request): JsonResponse
    {
        return $this->paginee(
            $this->candidatures->listerToutes($request->validated()),
            'candidatures',
            CandidatureResource::class,
        );
    }

    public function showCandidature(Candidature $candidature): JsonResponse
    {
        $candidature->load([
            'candidat.user',
            'candidat.competences',
            'offre.departement.entreprise',
            'offre.competences',
            'analyse',
            'entretiens',
            'resultatsTests.test',
        ]);

        return response()->json(['candidature' => new CandidatureResource($candidature)]);
    }

    public function changerStatutCandidature(
        ChangerStatutRequest $request,
        Candidature $candidature,
    ): JsonResponse {
        $this->authorize('traiter', $candidature);
        $donnees = $request->validated();

        return response()->json([
            'message' => 'Statut de la candidature mis à jour.',
            'candidature' => new CandidatureResource($this->candidatures->changerStatut(
                $candidature,
                StatutCandidature::from($donnees['statut']),
                $donnees['commentaire_recruteur'] ?? null,
            )),
        ]);
    }

    public function destroyCandidature(Candidature $candidature): JsonResponse
    {
        $this->authorize('delete', $candidature);
        $this->candidatures->retirer($candidature);

        return response()->json([], 204);
    }

    public function exporterCandidatures(ListerCandidaturesRequest $request): StreamedResponse
    {
        $lignes = $this->admin->candidaturesPourExport($request->validated());

        return response()->streamDownload(function () use ($lignes) {
            $sortie = fopen('php://output', 'w');
            fwrite($sortie, "\xEF\xBB\xBF");
            fputcsv($sortie, ['Candidat', 'Email', 'Offre', 'Score IA', 'Statut', 'Date'], ';');

            foreach ($lignes as $candidature) {
                fputcsv($sortie, [
                    $candidature->candidat->user->nomComplet(),
                    $candidature->candidat->user->email,
                    $candidature->offre->titre,
                    $candidature->score_final,
                    $candidature->statut->libelle(),
                    $candidature->date_candidature?->format('d/m/Y'),
                ], ';');
            }

            fclose($sortie);
        }, 'candidatures-'.now()->format('Y-m-d').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
