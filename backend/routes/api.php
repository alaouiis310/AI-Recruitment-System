<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\EntrepriseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::get('/health', fn() => response()->json([
    'status'  => 'ok',
    'service' => 'AI Recruitment System API',
    'time'    => now()->toIso8601String(),
]));

Route::prefix('auth')->group(function () {
    Route::post('/inscription/candidat',  [AuthController::class, 'inscriptionCandidat'])
        ->middleware('throttle:6,1');

    Route::post('/inscription/recruteur', [AuthController::class, 'inscriptionRecruteur'])
        ->middleware('throttle:6,1');


    Route::post('/connexion', [AuthController::class, 'connexion'])
        ->middleware('throttle:5,1');
});


// Les clés primaires sont numériques : /entreprises/abc renvoie 404 sans
// interroger la base.
Route::pattern('entreprise', '[0-9]+');

Route::middleware(['auth:sanctum', 'compte.actif'])->group(function () {

    Route::prefix('auth')->group(function () {
        Route::get('/moi',                  [AuthController::class, 'moi']);
        Route::patch('/profil',             [AuthController::class, 'modifierProfil']);
        Route::patch('/mot-de-passe',       [AuthController::class, 'changerMotDePasse']);
        Route::post('/deconnexion',         [AuthController::class, 'deconnexion']);
        Route::post('/deconnexion-globale', [AuthController::class, 'deconnexionGlobale']);
    });

    Route::middleware('role:candidat')->prefix('candidat')->group(function () {
        Route::get('/tableau-de-bord', fn(Request $r) => response()->json([
            'message' => 'Espace candidat',
            'role'    => $r->user()->role->value,
        ]));
    });

    Route::middleware('role:recruteur')->prefix('recruteur')->group(function () {
        Route::get('/tableau-de-bord', fn(Request $r) => response()->json([
            'message'    => 'Espace recruteur',
            'entreprise' => $r->user()->recruteur?->entreprise?->nom,
        ]));
    });

    Route::middleware('role:administrateur')->prefix('admin')->group(function () {
        Route::get('/tableau-de-bord', fn() => response()->json([
            'message' => 'Espace administrateur',
        ]));
    });

    /*
     |--------------------------------------------------------------------------
     | Entreprises — RG5, RG6
     |--------------------------------------------------------------------------
     | Lecture ouverte à tout compte authentifié ; écriture réservée aux
     | administrateurs et aux recruteurs, la propriété de l'enregistrement
     | étant tranchée par EntreprisePolicy.
     */
    Route::get('/entreprises', [EntrepriseController::class, 'index']);
    Route::get('/entreprises/{entreprise}', [EntrepriseController::class, 'show']);

    Route::middleware('role:administrateur,recruteur')->group(function () {
        Route::post('/entreprises', [EntrepriseController::class, 'store']);
        Route::patch('/entreprises/{entreprise}', [EntrepriseController::class, 'update']);
        Route::delete('/entreprises/{entreprise}', [EntrepriseController::class, 'destroy']);
    });
});
