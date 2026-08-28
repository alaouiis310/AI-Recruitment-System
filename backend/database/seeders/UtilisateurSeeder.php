<?php

namespace Database\Seeders;

use App\Enums\EtatCompte;
use App\Enums\RoleUtilisateur;
use App\Models\Candidat;
use App\Models\Entreprise;
use App\Models\Recruteur;
use App\Models\User;
use Illuminate\Database\Seeder;


class UtilisateurSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name'        => 'El Otmani',
            'prenom'      => 'Ahmed',
            'email'       => 'admin@airs.ma',
            'password'    => 'Password123',
            'role'        => RoleUtilisateur::Administrateur,
            'etat_compte' => EtatCompte::Actif,
        ]);

        $entreprise = Entreprise::create([
            'nom'         => 'TechnoMaroc',
            'secteur'     => 'Technologies de l\'information',
            'adresse'     => '12 avenue Mohammed V',
            'ville'       => 'Tanger',
            'site_web'    => 'https://technomaroc.example.ma',
            'description' => 'Société de services numériques basée à Tanger.',
        ]);

        $userRecruteur = User::create([
            'name'        => 'Bennani',
            'prenom'      => 'Salma',
            'email'       => 'recruteur@airs.ma',
            'password'    => 'Password123',
            'role'        => RoleUtilisateur::Recruteur,
            'etat_compte' => EtatCompte::Actif,
        ]);

        Recruteur::create([
            'id_user'       => $userRecruteur->id,
            'id_entreprise' => $entreprise->id_entreprise,
            'telephone'     => '0539000000',
            'poste'         => 'Responsable des ressources humaines',
        ]);

        $userCandidat = User::create([
            'name'        => 'Alami',
            'prenom'      => 'Youssef',
            'email'       => 'candidat@airs.ma',
            'password'    => 'Password123',
            'role'        => RoleUtilisateur::Candidat,
            'etat_compte' => EtatCompte::Actif,
        ]);

        Candidat::create([
            'id_user'           => $userCandidat->id,
            'telephone'         => '0600000000',
            'adresse'           => 'Quartier Iberia, Tanger',
            'date_naissance'    => '2001-04-12',
            'diplome'           => 'Diplôme d\'ingénieur en génie informatique',
            'github'            => 'https://github.com/exemple',
            'linkedin'          => 'https://linkedin.com/in/exemple',
            'experience_totale' => 2.5,
        ]);

        User::create([
            'name'        => 'Compte',
            'prenom'      => 'Suspendu',
            'email'       => 'suspendu@airs.ma',
            'password'    => 'Password123',
            'role'        => RoleUtilisateur::Candidat,
            'etat_compte' => EtatCompte::Suspendu,
        ]);
    }
}
