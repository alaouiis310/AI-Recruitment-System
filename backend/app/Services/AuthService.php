<?php

namespace App\Services;

use App\Enums\EtatCompte;
use App\Enums\RoleUtilisateur;
use App\Models\Candidat;
use App\Models\Entreprise;
use App\Models\Recruteur;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AuthService
{
    public function inscrireCandidat(array $donnees): User
    {
        return DB::transaction(function () use ($donnees) {
            $user = User::create([
                'name'        => $donnees['nom'],
                'prenom'      => $donnees['prenom'],
                'email'       => $donnees['email'],
                'password'    => $donnees['password'],
                'role'        => RoleUtilisateur::Candidat,
                'etat_compte' => EtatCompte::Actif,
            ]);

            Candidat::create([
                'id_user'           => $user->id,
                'telephone'         => $donnees['telephone']  ?? null,
                'telephone2'        => $donnees['telephone2'] ?? null,
                'adresse'           => $donnees['adresse']    ?? null,
                'date_naissance'    => $donnees['date_naissance'] ?? null,
                'diplome'           => $donnees['diplome']    ?? null,
                'github'            => $donnees['github']     ?? null,
                'linkedin'          => $donnees['linkedin']   ?? null,
                'experience_totale' => $donnees['experience_totale'] ?? 0,
            ]);

            return $user->load('candidat');
        });
    }

    public function inscrireRecruteur(array $donnees): User
    {
        return DB::transaction(function () use ($donnees) {
            $idEntreprise = $donnees['id_entreprise'] ?? null;

            if (! $idEntreprise) {
                $entreprise   = Entreprise::create($donnees['entreprise']);
                $idEntreprise = $entreprise->id_entreprise;
            }

            $user = User::create([
                'name'        => $donnees['nom'],
                'prenom'      => $donnees['prenom'],
                'email'       => $donnees['email'],
                'password'    => $donnees['password'],
                'role'        => RoleUtilisateur::Recruteur,
                'etat_compte' => EtatCompte::Actif,
            ]);

            Recruteur::create([
                'id_user'       => $user->id,
                'id_entreprise' => $idEntreprise,
                'telephone'     => $donnees['telephone'] ?? null,
                'poste'         => $donnees['poste']     ?? null,
            ]);

            return $user->load('recruteur.entreprise');
        });
    }

    public function chargerProfil(User $user): User
    {
        return match (true) {
            $user->estCandidat()  => $user->load('candidat'),
            $user->estRecruteur() => $user->load('recruteur.entreprise'),
            default               => $user,
        };
    }

    public function emettreJeton(User $user, ?string $device = null): string
    {
        $nom = $device ?: 'api-token';

        $capacites = match (true) {
            $user->estAdministrateur() => ['*'],
            $user->estRecruteur()      => ['offre:gerer', 'candidature:consulter'],
            default                    => ['candidature:deposer', 'profil:gerer'],
        };

        return $user->createToken($nom, $capacites)->plainTextToken;
    }
}
