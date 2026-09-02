<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ChangerMotDePasseRequest;
use App\Http\Requests\Auth\ConnexionRequest;
use App\Http\Requests\Auth\InscriptionCandidatRequest;
use App\Http\Requests\Auth\InscriptionRecruteurRequest;
use App\Http\Requests\Auth\ModifierProfilRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function __construct(private readonly AuthService $auth) {}

    public function inscriptionCandidat(InscriptionCandidatRequest $request): JsonResponse
    {
        $user  = $this->auth->inscrireCandidat($request->validated());
        $jeton = $this->auth->emettreJeton($user, $request->input('device_name'));

        return response()->json([
            'message'     => 'Compte candidat créé avec succès.',
            'utilisateur' => new UserResource($user),
            'token'       => $jeton,
            'token_type'  => 'Bearer',
        ], 201);
    }

    public function inscriptionRecruteur(InscriptionRecruteurRequest $request): JsonResponse
    {
        $user  = $this->auth->inscrireRecruteur($request->validated());
        $jeton = $this->auth->emettreJeton($user, $request->input('device_name'));

        return response()->json([
            'message'     => 'Compte recruteur créé avec succès.',
            'utilisateur' => new UserResource($user),
            'token'       => $jeton,
            'token_type'  => 'Bearer',
        ], 201);
    }


    public function connexion(ConnexionRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Identifiants incorrects.',
                'errors'  => ['email' => ['Ces identifiants ne correspondent à aucun compte.']],
            ], 401);
        }

        if (! $user->compteActif()) {
            return response()->json([
                'message' => 'Ce compte est ' . $user->etat_compte->libelle() . '. Contactez un administrateur.',
            ], 403);
        }

        $user  = $this->auth->chargerProfil($user);
        $jeton = $this->auth->emettreJeton($user, $request->input('device_name'));

        return response()->json([
            'message'     => 'Connexion réussie.',
            'utilisateur' => new UserResource($user),
            'token'       => $jeton,
            'token_type'  => 'Bearer',
        ]);
    }

    public function moi(Request $request): JsonResponse
    {
        $user = $this->auth->chargerProfil($request->user());

        return response()->json([
            'utilisateur' => new UserResource($user),
        ]);
    }

    public function modifierProfil(ModifierProfilRequest $request): JsonResponse
    {
        $user    = $request->user();
        $donnees = $request->validated();

        $champsUser = array_filter([
            'name'   => $donnees['nom']    ?? null,
            'prenom' => $donnees['prenom'] ?? null,
            'email'  => $donnees['email']  ?? null,
        ], fn($v) => $v !== null);

        if ($champsUser) {
            $user->update($champsUser);
        }

        $profil = $user->profil();

        if ($profil) {
            $champsProfil = collect($donnees)
                ->except(['nom', 'prenom', 'email'])
                ->filter(fn($v, $k) => in_array($k, $profil->getFillable(), true))
                ->all();

            if ($champsProfil) {
                $profil->update($champsProfil);
            }
        }

        return response()->json([
            'message'     => 'Profil mis à jour.',
            'utilisateur' => new UserResource($this->auth->chargerProfil($user->fresh())),
        ]);
    }


    public function changerMotDePasse(ChangerMotDePasseRequest $request): JsonResponse
    {
        $user = $request->user();
        $user->update(['password' => $request->password]);

        $jetonCourant = $user->currentAccessToken()->id;
        $user->tokens()->where('id', '!=', $jetonCourant)->delete();

        return response()->json([
            'message' => 'Mot de passe modifié. Les autres sessions ont été déconnectées.',
        ]);
    }

    public function deconnexion(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Déconnexion réussie.']);
    }

    public function deconnexionGlobale(Request $request): JsonResponse
    {
        $request->user()->tokens()->delete();

        return response()->json(['message' => 'Toutes les sessions ont été fermées.']);
    }
}
