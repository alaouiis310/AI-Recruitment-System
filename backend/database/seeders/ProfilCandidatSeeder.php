<?php

namespace Database\Seeders;

use App\Enums\EtatCompte;
use App\Enums\NiveauCompetence;
use App\Enums\RoleUtilisateur;
use App\Models\Candidat;
use App\Models\Competence;
use App\Models\User;
use Illuminate\Database\Seeder;

/** Profils candidats de démonstration (RG22 à RG26). */
class ProfilCandidatSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->profils() as $profil) {
            $competences = $profil['competences'];
            unset($profil['competences']);

            $candidat = $this->candidat($profil);

            $candidat->competences()->sync(
                Competence::whereIn('nom', array_keys($competences))
                    ->get()
                    ->mapWithKeys(fn (Competence $c) => [$c->id_competence => $competences[$c->nom]])
                    ->all()
            );
        }
    }

    /** Récupère le candidat déjà créé, ou le crée avec son compte utilisateur. */
    private function candidat(array $profil): Candidat
    {
        $user = User::where('email', $profil['email'])->first();

        if ($user === null) {
            $user = User::create([
                'name'        => $profil['nom'],
                'prenom'      => $profil['prenom'],
                'email'       => $profil['email'],
                'password'    => 'Password123',
                'role'        => RoleUtilisateur::Candidat,
                'etat_compte' => EtatCompte::Actif,
            ]);
        }

        return Candidat::firstOrCreate(
            ['id_user' => $user->id],
            [
                'telephone'         => $profil['telephone'],
                'adresse'           => $profil['adresse'],
                'date_naissance'    => $profil['date_naissance'],
                'diplome'           => $profil['diplome'],
                'experience_totale' => $profil['experience_totale'],
            ]
        );
    }

    /** Attributs du pivot posseder (RG26). */
    private function maitrise(NiveauCompetence $niveau, float $annees): array
    {
        return ['niveau' => $niveau->value, 'annees_experience' => $annees];
    }

    /** @return array<int, array<string, mixed>> */
    private function profils(): array
    {
        $debutant      = NiveauCompetence::Debutant;
        $intermediaire = NiveauCompetence::Intermediaire;
        $avance        = NiveauCompetence::Avance;
        $expert        = NiveauCompetence::Expert;

        return [
            // Profil tres proche de l'offre back-end PHP / Laravel.
            [
                'email'             => 'candidat@airs.ma',
                'nom'               => 'Alami',
                'prenom'            => 'Youssef',
                'telephone'         => '0600000000',
                'adresse'           => 'Quartier Iberia, Tanger',
                'date_naissance'    => '2001-04-12',
                'diplome'           => "Diplôme d'ingénieur en génie informatique",
                'experience_totale' => 2.5,
                'competences'       => [
                    'PHP'        => $this->maitrise($avance, 2.5),
                    'Laravel'    => $this->maitrise($avance, 2.0),
                    'MySQL'      => $this->maitrise($intermediaire, 2.5),
                    'Git'        => $this->maitrise($avance, 3.0),
                    'Docker'     => $this->maitrise($debutant, 1.0),
                    'JavaScript' => $this->maitrise($intermediaire, 2.0),
                    'Français'   => $this->maitrise($expert, 0),
                    'Anglais'    => $this->maitrise($intermediaire, 0),
                ],
            ],
            // Profil front-end : fort sur Vue.js, faible sur le back-end.
            [
                'email'             => 'candidat2@airs.ma',
                'nom'               => 'Benjelloun',
                'prenom'            => 'Imane',
                'telephone'         => '0600000002',
                'adresse'           => 'Maarif, Casablanca',
                'date_naissance'    => '1999-09-03',
                'diplome'           => 'Licence en développement web',
                'experience_totale' => 4.0,
                'competences'       => [
                    'JavaScript' => $this->maitrise($expert, 4.0),
                    'Vue.js'     => $this->maitrise($avance, 3.0),
                    'TypeScript' => $this->maitrise($avance, 2.5),
                    'Figma'      => $this->maitrise($intermediaire, 2.0),
                    'Git'        => $this->maitrise($avance, 4.0),
                    'PHP'        => $this->maitrise($debutant, 0.5),
                    'Français'   => $this->maitrise($expert, 0),
                    'Anglais'    => $this->maitrise($avance, 0),
                ],
            ],
            // Profil data : correspond a l'offre d'Atlas Digital.
            [
                'email'             => 'candidat3@airs.ma',
                'nom'               => 'Ouazzani',
                'prenom'            => 'Mehdi',
                'telephone'         => '0600000003',
                'adresse'           => 'Agdal, Rabat',
                'date_naissance'    => '1995-01-27',
                'diplome'           => 'Master en science des données',
                'experience_totale' => 6.0,
                'competences'       => [
                    'Python'     => $this->maitrise($expert, 6.0),
                    'SQL'        => $this->maitrise($avance, 5.0),
                    'PostgreSQL' => $this->maitrise($avance, 4.0),
                    'MongoDB'    => $this->maitrise($intermediaire, 2.0),
                    'Docker'     => $this->maitrise($intermediaire, 3.0),
                    'Anglais'    => $this->maitrise($expert, 0),
                    'Français'   => $this->maitrise($avance, 0),
                ],
            ],
        ];
    }
}
