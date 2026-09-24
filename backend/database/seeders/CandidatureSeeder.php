<?php

namespace Database\Seeders;

use App\Enums\StatutCandidature;
use App\Models\Candidat;
use App\Models\Candidature;
use App\Models\OffreEmploi;
use App\Models\User;
use Illuminate\Database\Seeder;

/** Candidatures de démonstration (RG27 à RG33). */
class CandidatureSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->candidatures() as [$email, $titre, $statut, $jours, $commentaire]) {
            $candidat = $this->candidat($email);
            $offre    = OffreEmploi::where('titre', $titre)->firstOrFail();

            $definitif = $statut->estDefinitif();

            Candidature::create([
                'id_candidat'           => $candidat->id_candidat,
                'id_offre'              => $offre->id_offre,
                'date_candidature'      => now()->subDays($jours)->toDateString(),
                'lettre_motivation'     => $this->lettre($candidat, $offre),
                'statut'                => $statut,
                'date_decision'         => $definitif ? now()->subDays(max($jours - 5, 0))->toDateString() : null,
                'commentaire_recruteur' => $commentaire,
            ]);
        }
    }

    /** firstOrFail : un ordre d'exécution incorrect doit échouer bruyamment. */
    private function candidat(string $email): Candidat
    {
        return User::where('email', $email)->firstOrFail()->candidat;
    }

    private function lettre(Candidat $candidat, OffreEmploi $offre): string
    {
        return sprintf(
            "Madame, Monsieur,\n\nFort de %s années d'expérience, je souhaite rejoindre votre équipe "
            .'au poste de %s. Mon parcours et mes compétences correspondent aux attentes exprimées '
            ."dans votre annonce.\n\nJe reste à votre disposition pour un entretien.",
            $candidat->experience_totale,
            $offre->titre,
        );
    }

    /** @return array<int, array{0: string, 1: string, 2: StatutCandidature, 3: int, 4: ?string}> */
    private function candidatures(): array
    {
        return [
            // Le profil back-end postule a l'offre qui lui correspond.
            ['candidat@airs.ma', 'Développeur back-end PHP / Laravel', StatutCandidature::Preselectionnee, 12,
                'Bon niveau technique, entretien à planifier.'],

            // ... et tente aussi le front-end, ou il correspond moins.
            ['candidat@airs.ma', 'Développeur front-end Vue.js', StatutCandidature::EnCours, 8, null],

            // Le profil front-end est bien place sur l'offre Vue.js.
            ['candidat2@airs.ma', 'Développeur front-end Vue.js', StatutCandidature::Preselectionnee, 6,
                'Portfolio convaincant.'],

            // ... et hors sujet sur l'offre systemes et reseaux.
            ['candidat2@airs.ma', 'Administrateur systèmes et réseaux', StatutCandidature::Refusee, 20,
                "Profil orienté front-end, sans expérience d'exploitation."],

            // Le profil data correspond a l'offre d'Atlas Digital.
            ['candidat3@airs.ma', 'Ingénieur data / intelligence artificielle', StatutCandidature::Acceptee, 25,
                'Excellente maîtrise de Python et des bases de données. Proposition envoyée.'],

            // Une candidature toute recente, encore en attente.
            ['candidat3@airs.ma', 'Administrateur systèmes et réseaux', StatutCandidature::EnAttente, 2, null],
        ];
    }
}
