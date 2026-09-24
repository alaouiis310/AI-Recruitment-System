<?php

namespace Database\Seeders;

use App\Enums\ImportanceCompetence;
use App\Enums\NiveauCompetence;
use App\Enums\NiveauEtude;
use App\Enums\StatutOffre;
use App\Enums\TypeContrat;
use App\Models\Competence;
use App\Models\Departement;
use App\Models\OffreEmploi;
use App\Models\Recruteur;
use Illuminate\Database\Seeder;

/** Offres de démonstration (RG10 à RG21). */
class OffreEmploiSeeder extends Seeder
{
    public function run(): void
    {
        // firstOrFail : un ordre d'exécution incorrect doit échouer bruyamment.
        $technoMaroc = Recruteur::whereRelation('entreprise', 'nom', 'TechnoMaroc')->firstOrFail();
        $atlas       = Recruteur::whereRelation('entreprise', 'nom', 'Atlas Digital')->firstOrFail();

        foreach ($this->offres($technoMaroc, $atlas) as $definition) {
            $competences = $definition['competences'];
            unset($definition['competences']);

            $offre = OffreEmploi::create($definition);

            // Les compétences sont désignées par leur nom, le référentiel étant partagé (RG20) (RG19/RG21).
            $offre->competences()->sync(
                Competence::whereIn('nom', array_keys($competences))
                    ->get()
                    ->mapWithKeys(fn (Competence $c) => [$c->id_competence => $competences[$c->nom]])
                    ->all()
            );
        }
    }

    /** Département d'un nom donné, au sein de l'entreprise du recruteur (RG9). */
    private function departement(Recruteur $recruteur, string $nom): int
    {
        return Departement::where('id_entreprise', $recruteur->id_entreprise)
            ->where('nom', $nom)
            ->firstOrFail()
            ->id_departement;
    }

    /** Attributs de pivot (RG21). */
    private function requiert(NiveauCompetence $niveau, ImportanceCompetence $importance): array
    {
        return [
            'niveau_requis' => $niveau->value,
            'importance'    => $importance->value,
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function offres(Recruteur $technoMaroc, Recruteur $atlas): array
    {
        $E = ImportanceCompetence::Essentielle;
        $I = ImportanceCompetence::Importante;
        $S = ImportanceCompetence::Souhaitee;

        $debutant      = NiveauCompetence::Debutant;
        $intermediaire = NiveauCompetence::Intermediaire;
        $avance        = NiveauCompetence::Avance;
        $expert        = NiveauCompetence::Expert;

        return [
            [
                'titre'            => 'Développeur back-end PHP / Laravel',
                'description'      => "Conception et maintenance des API du système d'information. Travail en binôme avec l'équipe front-end, revues de code et tests automatisés.",
                'type_contrat'     => TypeContrat::Cdi,
                'localisation'     => 'Tanger',
                'salaire'          => 14000,
                'experience_min'   => 2,
                'niveau_etude'     => NiveauEtude::Bac5,
                'date_publication' => now()->subWeeks(2)->toDateString(),
                'date_expiration'  => now()->addMonth()->toDateString(),
                'statut'           => StatutOffre::Ouverte,
                'id_recruteur'     => $technoMaroc->id_recruteur,
                'id_departement'   => $this->departement($technoMaroc, 'Développement logiciel'),
                'competences'      => [
                    'PHP'     => $this->requiert($avance, $E),
                    'Laravel' => $this->requiert($avance, $E),
                    'MySQL'   => $this->requiert($intermediaire, $I),
                    'Git'     => $this->requiert($intermediaire, $I),
                    'Docker'  => $this->requiert($debutant, $S),
                ],
            ],
            [
                'titre'            => 'Développeur front-end Vue.js',
                'description'      => 'Réalisation des interfaces du portail de recrutement. Intégration des maquettes, consommation des API REST et accessibilité.',
                'type_contrat'     => TypeContrat::Cdd,
                'localisation'     => 'Tanger',
                'salaire'          => 12000,
                'experience_min'   => 1,
                'niveau_etude'     => NiveauEtude::Bac3,
                'date_publication' => now()->subWeek()->toDateString(),
                'date_expiration'  => now()->addMonths(2)->toDateString(),
                'statut'           => StatutOffre::Ouverte,
                'id_recruteur'     => $technoMaroc->id_recruteur,
                'id_departement'   => $this->departement($technoMaroc, 'Développement logiciel'),
                'competences'      => [
                    'JavaScript' => $this->requiert($avance, $E),
                    'Vue.js'     => $this->requiert($intermediaire, $E),
                    'TypeScript' => $this->requiert($intermediaire, $I),
                    'Figma'      => $this->requiert($debutant, $S),
                ],
            ],
            [
                'titre'            => 'Administrateur systèmes et réseaux',
                'description'      => "Exploitation des serveurs, supervision et sécurisation du réseau. Astreinte partagée avec le reste de l'équipe.",
                'type_contrat'     => TypeContrat::Cdi,
                'localisation'     => 'Casablanca',
                'salaire'          => 13000,
                'experience_min'   => 3,
                'niveau_etude'     => NiveauEtude::Bac3,
                'date_publication' => now()->subDays(4)->toDateString(),
                'date_expiration'  => null,
                'statut'           => StatutOffre::Ouverte,
                'id_recruteur'     => $technoMaroc->id_recruteur,
                'id_departement'   => $this->departement($technoMaroc, 'Infrastructure et réseaux'),
                'competences'      => [
                    'Docker'     => $this->requiert($avance, $E),
                    'Kubernetes' => $this->requiert($intermediaire, $I),
                    'Git'        => $this->requiert($debutant, $S),
                ],
            ],
            [
                'titre'            => 'Ingénieur data / intelligence artificielle',
                'description'      => 'Construction des pipelines de données et des modèles prédictifs utilisés par les recommandations de candidatures.',
                'type_contrat'     => TypeContrat::Cdi,
                'localisation'     => 'Casablanca',
                'salaire'          => 18000,
                'experience_min'   => 3,
                'niveau_etude'     => NiveauEtude::Bac5,
                'date_publication' => now()->subDays(10)->toDateString(),
                'date_expiration'  => now()->addMonths(3)->toDateString(),
                'statut'           => StatutOffre::Ouverte,
                'id_recruteur'     => $atlas->id_recruteur,
                'id_departement'   => $this->departement($atlas, 'Data et intelligence artificielle'),
                'competences'      => [
                    'Python'     => $this->requiert($expert, $E),
                    'SQL'        => $this->requiert($avance, $E),
                    'PostgreSQL' => $this->requiert($intermediaire, $I),
                    'Anglais'    => $this->requiert($avance, $I),
                ],
            ],
            [
                'titre'            => 'Chargé de marketing digital',
                'description'      => "Pilotage des campagnes d'acquisition et de la présence en ligne du cabinet. Reporting mensuel auprès de la direction.",
                'type_contrat'     => TypeContrat::Alternance,
                'localisation'     => 'Casablanca',
                'salaire'          => null,
                'experience_min'   => 0,
                'niveau_etude'     => NiveauEtude::Bac3,
                'date_publication' => now()->subDays(20)->toDateString(),
                'date_expiration'  => now()->addWeeks(3)->toDateString(),

                // Offre suspendue : absente de la liste candidat (RG18).
                'statut'           => StatutOffre::Suspendue,
                'id_recruteur'     => $atlas->id_recruteur,
                'id_departement'   => $this->departement($atlas, 'Marketing digital'),
                'competences'      => [
                    'Français' => $this->requiert($avance, $E),
                    'Anglais'  => $this->requiert($intermediaire, $I),
                ],
            ],
            [
                'titre'            => 'Stage — assistant ressources humaines',
                'description'      => 'Appui au recrutement : tri des candidatures, organisation des entretiens et mise à jour du vivier.',
                'type_contrat'     => TypeContrat::Stage,
                'localisation'     => 'Tanger',
                'salaire'          => null,
                'experience_min'   => 0,
                'niveau_etude'     => NiveauEtude::Bac2,
                'date_publication' => now()->subMonths(3)->toDateString(),

                // Offre fermée et expirée : absente de la liste candidat (RG17/RG18).
                'date_expiration'  => now()->subWeek()->toDateString(),
                'statut'           => StatutOffre::Fermee,
                'id_recruteur'     => $technoMaroc->id_recruteur,
                'id_departement'   => $this->departement($technoMaroc, 'Ressources humaines'),
                'competences'      => [
                    'Français' => $this->requiert($avance, $E),
                    'Jira'     => $this->requiert($debutant, $S),
                ],
            ],
        ];
    }
}
