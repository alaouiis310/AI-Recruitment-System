<?php

use App\Http\Controllers\AiRecruitmentController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AnalyseIaController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CandidatCandidatureController;
use App\Http\Controllers\Api\CompetenceController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DepartementController;
use App\Http\Controllers\Api\EntrepriseController;
use App\Http\Controllers\Api\EntretienController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\OffreEmploiController;
use App\Http\Controllers\Api\ProfilCandidatController;
use App\Http\Controllers\Api\RecruteurCandidatureController;
use App\Http\Controllers\Api\RecruteurOffreController;
use App\Http\Controllers\Api\ResultatTestController;
use App\Http\Controllers\Api\TestTechniqueController;
use Illuminate\Support\Facades\Route;

Route::get('/health', fn () => response()->json([
    'status'  => 'ok',
    'service' => 'AI Recruitment System API',
    'time'    => now()->toIso8601String(),
]));

Route::prefix('auth')->group(function () {
    Route::post('/inscription/candidat', [AuthController::class, 'inscriptionCandidat'])
        ->middleware('throttle:6,1');

    Route::post('/inscription/recruteur', [AuthController::class, 'inscriptionRecruteur'])
        ->middleware('throttle:6,1');

    Route::post('/connexion', [AuthController::class, 'connexion'])
        ->middleware('throttle:5,1');
});

// Les clés primaires sont numériques : /entreprises/abc renvoie 404 sans
// interroger la base.
Route::pattern('entreprise', '[0-9]+');
Route::pattern('candidat', '[0-9]+');
Route::pattern('departement', '[0-9]+');
Route::pattern('entretien', '[0-9]+');
Route::pattern('offre', '[0-9]+');
Route::pattern('recruteur', '[0-9]+');
Route::pattern('candidature', '[0-9]+');
Route::pattern('competence', '[0-9]+');
Route::pattern('notification', '[0-9]+');
Route::pattern('resultat', '[0-9]+');
Route::pattern('test', '[0-9]+');
Route::pattern('utilisateur', '[0-9]+');

Route::middleware(['auth:sanctum', 'compte.actif'])->group(function () {

    Route::prefix('auth')->group(function () {
        Route::get('/moi', [AuthController::class, 'moi']);
        Route::patch('/profil', [AuthController::class, 'modifierProfil']);
        Route::patch('/mot-de-passe', [AuthController::class, 'changerMotDePasse']);
        Route::post('/deconnexion', [AuthController::class, 'deconnexion']);
        Route::post('/deconnexion-globale', [AuthController::class, 'deconnexionGlobale']);
    });

    Route::middleware('role:candidat')->prefix('candidat')->group(function () {
        Route::get('/tableau-de-bord', [DashboardController::class, 'candidat']);

        // RG22/RG23 — le candidat dépose et remplace son CV à tout moment.
        Route::post('/cv', [ProfilCandidatController::class, 'televerserCv']);
        Route::delete('/cv', [ProfilCandidatController::class, 'supprimerCv']);

        Route::post('/photo', [ProfilCandidatController::class, 'televerserPhoto']);
        Route::delete('/photo', [ProfilCandidatController::class, 'supprimerPhoto']);

        // RG24/RG26 — compétences déclarées et leur niveau de maîtrise.
        Route::get('/competences', [ProfilCandidatController::class, 'competences']);
        Route::put('/competences', [ProfilCandidatController::class, 'synchroniserCompetences']);
        Route::post('/competences', [ProfilCandidatController::class, 'declarerCompetence']);
        Route::delete('/competences/{competence}', [ProfilCandidatController::class, 'retirerCompetence']);

        // RG27/RG31 — une seule candidature par candidat et par offre.
        Route::get('/candidatures', [CandidatCandidatureController::class, 'index']);
        Route::post('/candidatures', [CandidatCandidatureController::class, 'store']);
        Route::get('/candidatures/{candidature}', [CandidatCandidatureController::class, 'show']);
        Route::delete('/candidatures/{candidature}', [CandidatCandidatureController::class, 'destroy']);
        Route::get('/candidatures/{candidature}/analyse', [AnalyseIaController::class, 'show']);

        // RG45 — le candidat consulte les tests reçus sur sa candidature.
        Route::get('/candidatures/{candidature}/tests', [ResultatTestController::class, 'parCandidature']);

        // RG34 — le candidat consulte les entretiens de sa candidature sans
        // pouvoir les modifier.
        Route::get('/candidatures/{candidature}/entretiens', [EntretienController::class, 'parCandidature']);
    });

    Route::middleware('role:recruteur')->prefix('recruteur')->group(function () {
        Route::get('/tableau-de-bord', [DashboardController::class, 'recruteur']);

        // RG12/RG13 — le recruteur ne gère que les offres qu'il a publiées ;
        // la propriété de l'enregistrement est vérifiée par OffreEmploiPolicy.
        Route::get('/offres', [RecruteurOffreController::class, 'index']);
        Route::post('/offres', [RecruteurOffreController::class, 'store']);
        Route::patch('/offres/{offre}', [RecruteurOffreController::class, 'update']);
        Route::delete('/offres/{offre}', [RecruteurOffreController::class, 'destroy']);
        Route::put('/offres/{offre}/tests', [TestTechniqueController::class, 'synchroniserOffre']);

        // RG14 — le recruteur ne voit que les candidatures portant sur
        // les offres qu'il a publiées ; la restriction est appliquée par
        // la requête, jamais en filtrant une collection déjà chargée.
        Route::get('/candidatures', [RecruteurCandidatureController::class, 'index']);
        Route::get('/candidatures/{candidature}', [RecruteurCandidatureController::class, 'show']);
        Route::patch('/candidatures/{candidature}/statut', [RecruteurCandidatureController::class, 'changerStatut']);

        // RG37/RG42 — résultat de l'analyse, et relance après mise à jour
        // du profil du candidat.
        Route::get('/candidatures/{candidature}/analyse', [AnalyseIaController::class, 'show']);
        Route::post('/candidatures/{candidature}/analyse', [AnalyseIaController::class, 'relancer']);

        // RG45 — envoi d'un test au candidat et saisie de son score. La
        // propriété de la candidature gouverne l'accès (RG14).
        Route::get('/resultats-tests', [ResultatTestController::class, 'index']);
        Route::post('/candidatures/{candidature}/tests/{test}', [ResultatTestController::class, 'envoyer']);
        Route::get('/candidatures/{candidature}/tests', [ResultatTestController::class, 'parCandidature']);
        Route::patch('/resultats-tests/{resultat}/score', [ResultatTestController::class, 'enregistrerScore']);

        // RG34/RG35/RG36 — les entretiens relèvent de la candidature, donc de
        // l'offre publiée par le recruteur (RG14).
        Route::get('/entretiens', [EntretienController::class, 'index']);
        Route::get('/entretiens/{entretien}', [EntretienController::class, 'show']);
        Route::post('/candidatures/{candidature}/entretiens', [EntretienController::class, 'store']);
        Route::get('/candidatures/{candidature}/entretiens', [EntretienController::class, 'parCandidature']);
        Route::patch('/entretiens/{entretien}', [EntretienController::class, 'update']);
        Route::delete('/entretiens/{entretien}', [EntretienController::class, 'destroy']);
    });

    Route::middleware('role:administrateur')->prefix('admin')->group(function () {
        Route::get('/tableau-de-bord', [AdminController::class, 'tableauDeBord']);
        Route::get('/analytiques', [AdminController::class, 'tableauDeBord']);

        Route::get('/candidats', [AdminController::class, 'candidats']);
        Route::post('/candidats', [AdminController::class, 'storeCandidat']);
        Route::get('/candidats/{candidat}', [AdminController::class, 'showCandidat']);

        Route::get('/recruteurs', [AdminController::class, 'recruteurs']);
        Route::post('/recruteurs', [AdminController::class, 'storeRecruteur']);
        Route::get('/recruteurs/{recruteur}', [AdminController::class, 'showRecruteur']);

        Route::patch('/utilisateurs/{utilisateur}/etat', [AdminController::class, 'changerEtat']);

        Route::get('/offres', [AdminController::class, 'offres']);
        Route::post('/offres', [AdminController::class, 'storeOffre']);
        Route::get('/offres/{offre}', [AdminController::class, 'showOffre']);
        Route::patch('/offres/{offre}', [AdminController::class, 'updateOffre']);
        Route::delete('/offres/{offre}', [AdminController::class, 'destroyOffre']);

        Route::get('/candidatures/export', [AdminController::class, 'exporterCandidatures']);
        Route::get('/candidatures', [AdminController::class, 'candidatures']);
        Route::get('/candidatures/{candidature}', [AdminController::class, 'showCandidature']);
        Route::patch('/candidatures/{candidature}/statut', [AdminController::class, 'changerStatutCandidature']);
        Route::delete('/candidatures/{candidature}', [AdminController::class, 'destroyCandidature']);
    });

    Route::get('/entreprises', [EntrepriseController::class, 'index']);
    Route::get('/entreprises/{entreprise}', [EntrepriseController::class, 'show']);
    Route::get('/entreprises/{entreprise}/departements', [DepartementController::class, 'index']);
    Route::get('/departements/{departement}', [DepartementController::class, 'show']);

    Route::middleware('role:administrateur,recruteur')->group(function () {
        Route::post('/entreprises', [EntrepriseController::class, 'store']);
        Route::patch('/entreprises/{entreprise}', [EntrepriseController::class, 'update']);
        Route::delete('/entreprises/{entreprise}', [EntrepriseController::class, 'destroy']);

        Route::post('/entreprises/{entreprise}/departements', [DepartementController::class, 'store']);
        Route::patch('/departements/{departement}', [DepartementController::class, 'update']);
        Route::delete('/departements/{departement}', [DepartementController::class, 'destroy']);
    });

    /*
     |--------------------------------------------------------------------------
     | Compétences — RG20, RG25
     |--------------------------------------------------------------------------
     | Référentiel partagé : consultable par tout compte authentifié, maintenu
     | par l'administrateur seul.
     */
    Route::get('/competences', [CompetenceController::class, 'index']);
    Route::get('/competences/categories', [CompetenceController::class, 'categories']);
    Route::get('/competences/{competence}', [CompetenceController::class, 'show']);

    Route::middleware('role:administrateur')->group(function () {
        Route::post('/competences', [CompetenceController::class, 'store']);
        Route::patch('/competences/{competence}', [CompetenceController::class, 'update']);
        Route::delete('/competences/{competence}', [CompetenceController::class, 'destroy']);
    });

    /*
     |--------------------------------------------------------------------------
     | Notifications — RG44
     |--------------------------------------------------------------------------
     | Toujours celles du compte authentifié : aucun identifiant d'utilisateur
     | ne figure dans les URL.
     */
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::patch('/notifications/{notification}/lue', [NotificationController::class, 'marquerLue']);
    Route::post('/notifications/toutes-lues', [NotificationController::class, 'marquerToutesLues']);

    /*
     |--------------------------------------------------------------------------
     | Tests techniques — RG45
     |--------------------------------------------------------------------------
     | Catalogue consultable par les recruteurs, maintenu par l'administrateur.
     */
    Route::get('/tests', [TestTechniqueController::class, 'index']);
    Route::get('/tests/{test}', [TestTechniqueController::class, 'show']);

    Route::middleware('role:administrateur')->group(function () {
        Route::post('/tests', [TestTechniqueController::class, 'store']);
        Route::patch('/tests/{test}', [TestTechniqueController::class, 'update']);
        Route::delete('/tests/{test}', [TestTechniqueController::class, 'destroy']);
    });

    /*
     |--------------------------------------------------------------------------
     | Offres d'emploi — RG15 à RG21
     |--------------------------------------------------------------------------
     | Consultation ouverte à tout compte authentifié : la liste ne montre que
     | les offres ouvertes et non expirées (RG17, RG18). Une offre fermée,
     | suspendue ou expirée reste consultable par son auteur.
     |
     | La publication et la gestion se font dans l'espace recruteur, sous
     | /api/recruteur/offres.
     */
    Route::get('/offres', [OffreEmploiController::class, 'index']);
    Route::get('/offres/{offre}', [OffreEmploiController::class, 'show']);

    /*
     |--------------------------------------------------------------------------
     | Assistant et évaluation de CV par IA — module de Nilam (Gemini)
     |--------------------------------------------------------------------------
     | Outils d'aide : rien n'est enregistré, et le score d'une candidature
     | reste celui de ScoringService (RG40). L'évaluation de CV déposés à la
     | volée est réservée aux recruteurs et administrateurs ; l'assistant est
     | ouvert à tout compte.
     */
    Route::prefix('ia')->group(function () {
        Route::post('/assistant', [AiRecruitmentController::class, 'chat'])
            ->middleware('throttle:20,1');

        Route::middleware('role:recruteur,administrateur')->group(function () {
            Route::post('/evaluer-cv', [AiRecruitmentController::class, 'evaluate'])
                ->middleware('throttle:10,1');
            Route::post('/classer-cvs', [AiRecruitmentController::class, 'rank'])
                ->middleware('throttle:5,1');
        });
    });
});
