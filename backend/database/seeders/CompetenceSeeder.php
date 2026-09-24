<?php

namespace Database\Seeders;

use App\Enums\CategorieCompetence;
use App\Models\Competence;
use Illuminate\Database\Seeder;

/** Référentiel de départ (RG20, RG25). */
class CompetenceSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->referentiel() as $categorie => $competences) {
            foreach ($competences as $nom => $description) {
                Competence::create([
                    'nom'         => $nom,
                    'categorie'   => $categorie,
                    'description' => $description,
                ]);
            }
        }
    }

    /** @return array<string, array<string, string>> */
    private function referentiel(): array
    {
        return [
            CategorieCompetence::Langage->value => [
                'PHP'        => 'Langage serveur, socle de Laravel.',
                'JavaScript' => 'Langage du navigateur et de Node.js.',
                'TypeScript' => 'JavaScript typé statiquement.',
                'Python'     => 'Langage généraliste, très utilisé en science des données.',
                'Java'       => 'Langage orienté objet des applications d\'entreprise.',
                'C#'         => 'Langage de la plateforme .NET.',
                'SQL'        => 'Langage d\'interrogation des bases relationnelles.',
            ],
            CategorieCompetence::Framework->value => [
                'Laravel'     => 'Framework PHP orienté API et web.',
                'Symfony'     => 'Framework PHP modulaire.',
                'Vue.js'      => 'Framework JavaScript pour interfaces réactives.',
                'React'       => 'Bibliothèque JavaScript pour interfaces.',
                'Angular'     => 'Framework front-end complet.',
                'Spring Boot' => 'Framework Java pour services web.',
                'Django'      => 'Framework web Python.',
            ],
            CategorieCompetence::Outil->value => [
                'Git'            => 'Gestion de versions décentralisée.',
                'Docker'         => 'Conteneurisation des environnements.',
                'Kubernetes'     => 'Orchestration de conteneurs.',
                'Jenkins'        => 'Intégration continue.',
                'Jira'           => 'Suivi de projet et de tickets.',
                'Figma'          => 'Conception d\'interfaces.',
                'Postman'        => 'Test et documentation d\'API.',
            ],
            CategorieCompetence::BaseDonnees->value => [
                'MySQL'         => 'Base relationnelle open source.',
                'PostgreSQL'    => 'Base relationnelle avancée.',
                'MongoDB'       => 'Base orientée documents.',
                'Redis'         => 'Stockage clé-valeur en mémoire.',
                'Oracle'        => 'Base relationnelle d\'entreprise.',
            ],
            CategorieCompetence::Langue->value => [
                'Arabe'   => 'Langue officielle du Maroc.',
                'Français' => 'Langue de travail courante au Maroc.',
                'Anglais' => 'Langue de travail internationale.',
                'Espagnol' => 'Langue utile dans le nord du Maroc.',
                'Amazighe' => 'Langue officielle du Maroc.',
            ],
            CategorieCompetence::SavoirEtre->value => [
                'Travail en équipe'      => 'Capacité à collaborer efficacement.',
                'Communication'          => 'Clarté à l\'oral comme à l\'écrit.',
                'Autonomie'              => 'Capacité à avancer sans supervision constante.',
                'Rigueur'                => 'Souci du détail et de la qualité.',
                'Résolution de problèmes' => 'Analyse et traitement des situations complexes.',
                'Gestion du temps'       => 'Organisation et respect des délais.',
                'Adaptabilité'           => 'Aisance face au changement.',
            ],
        ];
    }
}
