<?php

namespace Database\Seeders;

/**
 * Données du jeu de démonstration, séparées du seeder pour rester lisibles.
 *
 * Entreprises et personnes sont fictives. Les compétences sont désignées par
 * leur nom dans le référentiel de CompetenceSeeder ; les dates sont exprimées
 * en jours relatifs au jour de l'exécution.
 */
final class DonneesDemonstration
{
    public static function entreprises(): array
    {
        return [
            [
                'entreprise' => ['nom' => 'Rif Énergie', 'secteur' => 'Énergies renouvelables', 'adresse' => 'Zone franche Tanger Automotive City', 'ville' => 'Tanger', 'site_web' => 'https://rif-energie.example.ma', 'description' => 'Développement et exploitation de parcs éoliens et solaires dans le nord du Maroc.'],
                'departements' => [
                    'Ingénierie' => 'Conception et suivi des installations.',
                    "Systèmes d'information" => 'Applications métier et supervision.',
                    'Ressources humaines' => 'Recrutement et gestion des carrières.',
                ],
                'recruteur' => ['Nadia', 'El Fassi', 'recruteur3@airs.ma', 'Responsable talents', '0539112233'],
            ],
            [
                'entreprise' => ['nom' => 'Finance Horizon', 'secteur' => 'Services financiers', 'adresse' => 'Avenue Annakhil, Hay Riad', 'ville' => 'Rabat', 'site_web' => 'https://finance-horizon.example.ma', 'description' => 'Solutions de paiement et de crédit pour les PME.'],
                'departements' => [
                    'Informatique' => 'Développement des plateformes de paiement.',
                    'Risques et conformité' => 'Contrôle réglementaire et lutte anti-fraude.',
                    'Ressources humaines' => 'Recrutement et formation.',
                ],
                'recruteur' => ['Omar', 'Tazi', 'recruteur4@airs.ma', 'Chargé de recrutement IT', '0537445566'],
            ],
            [
                'entreprise' => ['nom' => 'Medina Santé', 'secteur' => 'Santé numérique', 'adresse' => 'Boulevard Mohammed VI, Guéliz', 'ville' => 'Marrakech', 'site_web' => 'https://medina-sante.example.ma', 'description' => 'Plateforme de prise de rendez-vous et de téléconsultation.'],
                'departements' => [
                    'Produit' => 'Vision produit et expérience utilisateur.',
                    'Développement' => 'Applications web et mobile.',
                    'Support' => 'Assistance aux praticiens et aux patients.',
                ],
                'recruteur' => ['Leila', 'Benchekroun', 'recruteur5@airs.ma', 'Talent acquisition manager', '0524778899'],
            ],
            [
                'entreprise' => ['nom' => 'Sahara Logistique', 'secteur' => 'Logistique et transport', 'adresse' => 'Zone industrielle Aïn Sebaâ', 'ville' => 'Casablanca', 'site_web' => 'https://sahara-logistique.example.ma', 'description' => 'Transport de marchandises et entreposage à l’échelle nationale.'],
                'departements' => [
                    "Systèmes d'information" => 'Outils de suivi des flux et des entrepôts.',
                    'Opérations' => 'Planification des tournées.',
                    'Data' => 'Pilotage par la donnée et prévisions.',
                ],
                'recruteur' => ['Hamza', 'Berrada', 'recruteur6@airs.ma', 'Responsable RH', '0522334455'],
            ],
        ];
    }

    public static function offres(): array
    {
        $o = fn ($recruteur, $departement, $titre, $contrat, $ville, $salaire, $niveau, $experience, $publiee, $expire, $statut, $competences, $description) => compact(
            'recruteur', 'departement', 'titre', 'contrat', 'ville', 'salaire', 'niveau', 'experience', 'competences', 'description', 'statut'
        ) + ['publiee_il_y_a' => $publiee, 'expire_dans' => $expire];

        return [
            $o('recruteur@airs.ma', 'Développement logiciel', 'Développeur Java / Spring Boot', 'cdi', 'Tanger', 15000, 'bac_5', 3, 60, 30, 'ouverte',
                ['Java' => ['avance', 'essentielle'], 'Spring Boot' => ['avance', 'essentielle'], 'SQL' => ['intermediaire', 'importante'], 'Git' => ['intermediaire', 'importante'], 'Docker' => ['debutant', 'souhaitee']],
                'Développement de micro-services pour notre plateforme de gestion de flotte. Revues de code, tests automatisés et déploiement continu.'),
            $o('recruteur@airs.ma', 'Infrastructure et réseaux', 'Ingénieur DevOps', 'cdi', 'Tanger', 17000, 'bac_5', 3, 40, 45, 'ouverte',
                ['Docker' => ['expert', 'essentielle'], 'Kubernetes' => ['avance', 'essentielle'], 'Jenkins' => ['intermediaire', 'importante'], 'Git' => ['avance', 'importante'], 'Anglais' => ['intermediaire', 'souhaitee']],
                'Industrialisation des déploiements, supervision et haute disponibilité de nos environnements de production.'),
            $o('recruteur3@airs.ma', "Systèmes d'information", 'Développeur full stack React / Laravel', 'cdi', 'Tanger', 13000, 'bac_3', 2, 120, 20, 'ouverte',
                ['React' => ['avance', 'essentielle'], 'PHP' => ['intermediaire', 'essentielle'], 'Laravel' => ['intermediaire', 'importante'], 'MySQL' => ['intermediaire', 'importante'], 'Git' => ['intermediaire', 'souhaitee']],
                'Refonte du portail de suivi de production des parcs éoliens, du back-office Laravel à l’interface React.'),
            $o('recruteur3@airs.ma', "Systèmes d'information", 'Data analyst', 'cdd', 'Tanger', 11000, 'bac_5', 1, 90, 15, 'ouverte',
                ['Python' => ['avance', 'essentielle'], 'SQL' => ['avance', 'essentielle'], 'PostgreSQL' => ['intermediaire', 'importante'], 'Anglais' => ['intermediaire', 'souhaitee']],
                'Analyse des données de production énergétique et construction de tableaux de bord pour la direction.'),
            $o('recruteur4@airs.ma', 'Informatique', 'Développeur C# / .NET', 'cdi', 'Rabat', 16000, 'bac_5', 3, 140, 10, 'ouverte',
                ['C#' => ['expert', 'essentielle'], 'SQL' => ['avance', 'essentielle'], 'Oracle' => ['intermediaire', 'importante'], 'Git' => ['intermediaire', 'importante'], 'Rigueur' => ['avance', 'souhaitee']],
                'Évolution du cœur de notre plateforme de paiement : transactions, rapprochements et reporting réglementaire.'),
            $o('recruteur4@airs.ma', 'Risques et conformité', 'Analyste conformité', 'cdi', 'Rabat', 12000, 'bac_5', 2, 70, 25, 'ouverte',
                ['Rigueur' => ['expert', 'essentielle'], 'Français' => ['expert', 'essentielle'], 'SQL' => ['intermediaire', 'importante'], 'Anglais' => ['avance', 'importante'], 'Communication' => ['avance', 'souhaitee']],
                'Contrôle des opérations, analyse des alertes anti-fraude et rédaction des rapports destinés au régulateur.'),
            $o('recruteur4@airs.ma', 'Informatique', 'Stage — développeur Angular', 'stage', 'Rabat', null, 'bac_3', 0, 30, 40, 'ouverte',
                ['Angular' => ['intermediaire', 'essentielle'], 'TypeScript' => ['intermediaire', 'importante'], 'Git' => ['debutant', 'souhaitee']],
                'Stage de fin d’études de six mois au sein de l’équipe front-end de l’espace client.'),
            $o('recruteur5@airs.ma', 'Développement', 'Développeur front-end React', 'cdi', 'Marrakech', 12500, 'bac_3', 2, 50, 20, 'ouverte',
                ['React' => ['avance', 'essentielle'], 'JavaScript' => ['avance', 'essentielle'], 'TypeScript' => ['intermediaire', 'importante'], 'Figma' => ['debutant', 'souhaitee']],
                'Développement de l’application de prise de rendez-vous utilisée par plus de deux mille praticiens.'),
            $o('recruteur5@airs.ma', 'Produit', 'Product owner santé numérique', 'cdi', 'Marrakech', 16000, 'bac_5', 4, 25, 35, 'ouverte',
                ['Jira' => ['avance', 'essentielle'], 'Communication' => ['expert', 'essentielle'], 'Anglais' => ['avance', 'importante'], 'Français' => ['avance', 'importante'], 'Gestion du temps' => ['avance', 'souhaitee']],
                'Pilotage du backlog de la téléconsultation, en lien étroit avec les praticiens et l’équipe de développement.'),
            $o('recruteur5@airs.ma', 'Support', 'Technicien support applicatif', 'cdd', 'Marrakech', 7000, 'bac_2', 1, 15, 45, 'ouverte',
                ['Résolution de problèmes' => ['avance', 'essentielle'], 'Communication' => ['avance', 'essentielle'], 'SQL' => ['debutant', 'importante'], 'Français' => ['avance', 'importante']],
                'Assistance de niveau 1 et 2 aux cabinets médicaux utilisateurs de la plateforme.'),
            $o('recruteur6@airs.ma', 'Data', 'Ingénieur data / BI', 'cdi', 'Casablanca', 17000, 'bac_5', 3, 150, 5, 'ouverte',
                ['Python' => ['avance', 'essentielle'], 'SQL' => ['expert', 'essentielle'], 'PostgreSQL' => ['avance', 'importante'], 'MongoDB' => ['intermediaire', 'souhaitee'], 'Anglais' => ['intermediaire', 'souhaitee']],
                'Conception de l’entrepôt de données et des prévisions de volumes pour les centres logistiques.'),
            $o('recruteur6@airs.ma', "Systèmes d'information", 'Développeur back-end Symfony', 'cdi', 'Casablanca', 14000, 'bac_5', 2, 35, 25, 'ouverte',
                ['PHP' => ['avance', 'essentielle'], 'Symfony' => ['avance', 'essentielle'], 'MySQL' => ['intermediaire', 'importante'], 'Docker' => ['intermediaire', 'importante'], 'Redis' => ['debutant', 'souhaitee']],
                'Développement des API de suivi des colis consommées par nos partenaires e-commerce.'),
            $o('recruteur6@airs.ma', 'Opérations', 'Alternance — assistant logistique digital', 'alternance', 'Casablanca', null, 'bac_2', 0, 100, -10, 'fermee',
                ['Français' => ['avance', 'essentielle'], 'Travail en équipe' => ['intermediaire', 'importante'], 'Autonomie' => ['intermediaire', 'souhaitee']],
                'Alternance d’un an : planification des tournées et suivi des indicateurs de livraison.'),
        ];
    }

    public static function candidats(): array
    {
        $c = fn ($prenom, $nom, $n, $ville, $naissance, $diplome, $experience, $accroche, $parcours, $competences) => [
            'prenom' => $prenom, 'nom' => $nom, 'email' => "candidat{$n}@airs.ma",
            'telephone' => sprintf('06%08d', 11223300 + $n * 7), 'adresse' => $ville, 'naissance' => $naissance,
            'diplome' => $diplome, 'experience' => $experience, 'accroche' => $accroche, 'parcours' => $parcours,
            'competences' => $competences,
        ];

        return [
            $c('Salma', 'Chraibi', 4, 'Maârif, Casablanca', '1993-03-14', "Diplôme d'ingénieur en génie logiciel — ENSIAS", 5,
                'Ingénieure logiciel Java, cinq ans de micro-services en production.',
                'Développeuse Java puis référente technique dans une ESN casablancaise ; migration d’un monolithe vers Spring Boot.',
                ['Java' => ['expert', 5], 'Spring Boot' => ['avance', 4], 'SQL' => ['avance', 5], 'Git' => ['avance', 5], 'Docker' => ['intermediaire', 2], 'Anglais' => ['avance', 0], 'Français' => ['expert', 0]]),
            $c('Anas', 'Kettani', 5, 'Agdal, Rabat', '1994-11-02', 'Master en ingénierie logicielle — Université Mohammed V', 4,
                'Développeur .NET spécialisé dans les applications financières.',
                'Quatre ans sur des applications de gestion de crédit en C# et Oracle.',
                ['C#' => ['expert', 4], 'SQL' => ['avance', 4], 'Oracle' => ['intermediaire', 3], 'Git' => ['avance', 4], 'Rigueur' => ['avance', 0], 'Français' => ['expert', 0], 'Anglais' => ['intermediaire', 0]]),
            $c('Hajar', 'Lahlou', 6, 'Malabata, Tanger', '1997-06-21', 'Licence professionnelle en développement web — FST Tanger', 3,
                'Développeuse full stack PHP / Laravel.',
                'Trois ans en agence web : API Laravel, intégration Vue.js et mise en production Docker.',
                ['PHP' => ['avance', 3], 'Laravel' => ['avance', 3], 'MySQL' => ['avance', 3], 'Vue.js' => ['intermediaire', 2], 'Git' => ['avance', 3], 'Docker' => ['intermediaire', 1], 'Français' => ['expert', 0], 'Anglais' => ['intermediaire', 0]]),
            $c('Reda', 'Amrani', 7, 'Bourgogne, Casablanca', '1990-01-09', "Diplôme d'ingénieur réseaux et systèmes — INPT", 6,
                'Ingénieur DevOps, conteneurisation et intégration continue.',
                'Six ans d’exploitation puis d’industrialisation : Kubernetes en production, pipelines Jenkins.',
                ['Docker' => ['expert', 6], 'Kubernetes' => ['avance', 4], 'Jenkins' => ['avance', 5], 'Git' => ['expert', 6], 'Python' => ['intermediaire', 3], 'Anglais' => ['avance', 0]]),
            $c('Kenza', 'Bennis', 8, 'Guéliz, Marrakech', '1999-08-30', 'Licence en informatique — Université Cadi Ayyad', 2,
                'Développeuse front-end React.',
                'Deux ans sur des interfaces React pour une start-up du tourisme.',
                ['React' => ['avance', 2], 'JavaScript' => ['avance', 2], 'TypeScript' => ['intermediaire', 1], 'Figma' => ['intermediaire', 1], 'Git' => ['intermediaire', 2], 'Français' => ['avance', 0]]),
            $c('Yassine', 'Mansouri', 9, 'Iberia, Tanger', '2001-02-17', 'Licence en développement informatique — FST Tanger', 1,
                'Développeur PHP junior.',
                'Un an de stage puis de CDD sur un site e-commerce.',
                ['PHP' => ['intermediaire', 1], 'Laravel' => ['debutant', 1], 'MySQL' => ['intermediaire', 1], 'Git' => ['debutant', 1], 'Français' => ['avance', 0]]),
            $c('Soukaina', 'Rami', 10, 'Hay Riad, Rabat', '1995-12-05', 'Master en science des données — INSEA', 3,
                'Data scientist, modélisation et visualisation.',
                'Trois ans en cabinet de conseil : prévisions de ventes et tableaux de bord.',
                ['Python' => ['expert', 3], 'SQL' => ['avance', 3], 'PostgreSQL' => ['avance', 3], 'MongoDB' => ['intermediaire', 1], 'Anglais' => ['avance', 0], 'Français' => ['expert', 0]]),
            $c('Ilyas', 'Ziani', 11, 'Gauthier, Casablanca', '1993-09-12', "Diplôme d'ingénieur informatique — EMSI", 4,
                'Développeur back-end PHP / Symfony.',
                'Quatre ans sur des API Symfony à fort trafic, cache Redis et conteneurs Docker.',
                ['PHP' => ['expert', 4], 'Symfony' => ['avance', 4], 'MySQL' => ['avance', 4], 'Docker' => ['avance', 3], 'Redis' => ['intermediaire', 2], 'Git' => ['avance', 4], 'Français' => ['avance', 0]]),
            $c('Meryem', 'Filali', 12, 'Hivernage, Marrakech', '1991-04-27', 'Master en management des systèmes d’information — ENCG', 5,
                'Product owner, produits numériques grand public.',
                'Cinq ans de pilotage produit dans une banque en ligne puis une application de santé.',
                ['Jira' => ['expert', 5], 'Communication' => ['expert', 0], 'Anglais' => ['avance', 0], 'Français' => ['expert', 0], 'Gestion du temps' => ['avance', 0], 'Figma' => ['debutant', 1]]),
            $c('Othmane', 'Benali', 13, 'Val Fleuri, Tanger', '2002-07-08', 'Licence en génie informatique (en cours) — ENSA Tanger', 0,
                'Étudiant en dernière année, à la recherche d’un stage de fin d’études.',
                'Projets universitaires en Angular et un stage d’observation de deux mois.',
                ['Angular' => ['intermediaire', 1], 'TypeScript' => ['intermediaire', 1], 'JavaScript' => ['intermediaire', 1], 'Git' => ['debutant', 1], 'Français' => ['avance', 0]]),
            $c('Nour El Houda', 'Saidi', 14, 'Souissi, Rabat', '1996-10-19', 'Master en audit et contrôle de gestion — ISCAE', 3,
                'Analyste conformité bancaire.',
                'Trois ans au service conformité d’un établissement de paiement.',
                ['Rigueur' => ['expert', 0], 'Français' => ['expert', 0], 'Anglais' => ['avance', 0], 'Communication' => ['avance', 0], 'SQL' => ['intermediaire', 2]]),
            $c('Amine', 'Chakir', 15, 'Sidi Maârouf, Casablanca', '1998-05-03', 'DUT en génie informatique — EST Casablanca', 2,
                'Technicien support et assistance utilisateurs.',
                'Deux ans de support applicatif dans un centre de services.',
                ['Résolution de problèmes' => ['avance', 2], 'Communication' => ['avance', 0], 'SQL' => ['debutant', 1], 'Français' => ['avance', 0], 'Arabe' => ['expert', 0]]),
            $c('Zineb', 'Alaoui', 16, 'Marshan, Tanger', '1998-01-25', 'Licence professionnelle en développement web — FST Tanger', 2,
                'Développeuse full stack React / Laravel.',
                'Deux ans en freelance sur des applications métier React et Laravel.',
                ['React' => ['intermediaire', 2], 'PHP' => ['intermediaire', 2], 'Laravel' => ['intermediaire', 2], 'MySQL' => ['intermediaire', 2], 'Git' => ['avance', 2], 'Français' => ['avance', 0]]),
            $c('Karim', 'Ouahbi', 17, 'Derb Ghallef, Casablanca', '1992-12-11', 'BTS en commerce international', 0,
                'En reconversion vers le développement web.',
                'Huit ans dans le commerce, puis une formation intensive en développement web.',
                ['JavaScript' => ['debutant', 0], 'Python' => ['debutant', 0], 'Communication' => ['avance', 0], 'Travail en équipe' => ['avance', 0], 'Français' => ['avance', 0]]),
            $c('Ghita', 'Sqalli', 18, 'Semlalia, Marrakech', '1992-03-16', "Diplôme d'ingénieur en informatique — ENSA Marrakech", 5,
                'Développeuse front-end senior, React et Vue.js.',
                'Cinq ans d’interfaces web, dont deux comme lead front-end d’une équipe de quatre.',
                ['JavaScript' => ['expert', 5], 'TypeScript' => ['avance', 3], 'React' => ['avance', 3], 'Vue.js' => ['avance', 3], 'Figma' => ['avance', 2], 'Git' => ['avance', 5], 'Anglais' => ['avance', 0]]),
            $c('Mohamed', 'Tahiri', 19, 'Hassan, Rabat', '2000-09-29', 'Licence en informatique — Université Mohammed V', 1,
                'Développeur Java junior.',
                'Un an de développement Java sur un outil interne de gestion des stocks.',
                ['Java' => ['intermediaire', 1], 'Spring Boot' => ['debutant', 1], 'SQL' => ['intermediaire', 1], 'Git' => ['intermediaire', 1], 'Français' => ['avance', 0]]),
        ];
    }

    /**
     * [email, offre, déposée il y a (jours), statut, décision il y a (jours), commentaire].
     * Chaque candidature suit la publication de son offre ; les plus anciennes sont tranchées.
     */
    public static function candidatures(): array
    {
        $backEnd = 'Développeur back-end PHP / Laravel';
        $vue = 'Développeur front-end Vue.js';
        $sys = 'Administrateur systèmes et réseaux';

        return [
            ['candidat6@airs.ma', $backEnd, 12, 'preselectionnee', null, 'Très bonne maîtrise de Laravel, entretien technique à planifier.'],
            ['candidat11@airs.ma', $backEnd, 13, 'en_cours', null, null],
            ['candidat16@airs.ma', $backEnd, 11, 'en_cours', null, null],
            ['candidat9@airs.ma', $backEnd, 8, 'en_attente', null, null],
            ['candidat17@airs.ma', $backEnd, 5, 'en_attente', null, null],
            ['candidat4@airs.ma', $backEnd, 10, 'refusee', 4, 'Profil orienté Java, peu de pratique de PHP.'],
            ['candidat18@airs.ma', $vue, 6, 'preselectionnee', null, 'Portfolio remarquable, profil senior.'],
            ['candidat8@airs.ma', $vue, 5, 'en_cours', null, null],
            ['candidat16@airs.ma', $vue, 4, 'en_attente', null, null],
            ['candidat7@airs.ma', $sys, 3, 'en_cours', null, null],
            ['candidat15@airs.ma', $sys, 2, 'en_attente', null, null],
            ['candidat4@airs.ma', 'Développeur Java / Spring Boot', 55, 'acceptee', 40, 'Excellente maîtrise de Spring Boot. Proposition acceptée.'],
            ['candidat19@airs.ma', 'Développeur Java / Spring Boot', 50, 'refusee', 42, 'Profil encore junior pour ce poste.'],
            ['candidat5@airs.ma', 'Développeur Java / Spring Boot', 45, 'refusee', 38, 'Expérience .NET, pas de pratique de Java.'],
            ['candidat7@airs.ma', 'Ingénieur DevOps', 35, 'acceptee', 20, 'Très solide sur Kubernetes et l’intégration continue.'],
            ['candidat11@airs.ma', 'Ingénieur DevOps', 30, 'refusee', 22, 'Profil développeur plutôt qu’exploitation.'],
            ['candidat16@airs.ma', 'Développeur full stack React / Laravel', 110, 'acceptee', 95, 'Bonne polyvalence React et Laravel.'],
            ['candidat6@airs.ma', 'Développeur full stack React / Laravel', 100, 'refusee', 90, 'Expérience React insuffisante pour ce poste.'],
            ['candidat8@airs.ma', 'Développeur full stack React / Laravel', 105, 'refusee', 92, 'Pas d’expérience back-end.'],
            ['candidat10@airs.ma', 'Data analyst', 85, 'acceptee', 70, 'Profil data très complet.'],
            ['candidat17@airs.ma', 'Data analyst', 80, 'refusee', 75, 'Compétences techniques encore à construire.'],
            ['candidat5@airs.ma', 'Développeur C# / .NET', 130, 'acceptee', 115, 'Parfaite adéquation avec notre socle .NET.'],
            ['candidat19@airs.ma', 'Développeur C# / .NET', 125, 'refusee', 118, 'Pas d’expérience C#.'],
            ['candidat14@airs.ma', 'Analyste conformité', 65, 'preselectionnee', null, 'Expérience conformité directement transposable.'],
            ['candidat12@airs.ma', 'Analyste conformité', 60, 'refusee', 50, 'Profil produit plutôt que contrôle.'],
            ['candidat13@airs.ma', 'Stage — développeur Angular', 22, 'en_cours', null, null],
            ['candidat8@airs.ma', 'Stage — développeur Angular', 15, 'en_attente', null, null],
            ['candidat18@airs.ma', 'Développeur front-end React', 45, 'acceptee', 30, 'Lead front-end expérimentée. Proposition acceptée.'],
            ['candidat8@airs.ma', 'Développeur front-end React', 40, 'preselectionnee', null, 'Bon profil React, à revoir pour un second poste.'],
            ['candidat16@airs.ma', 'Développeur front-end React', 35, 'refusee', 28, 'Déjà recrutée sur un autre poste.'],
            ['candidat12@airs.ma', 'Product owner santé numérique', 20, 'preselectionnee', null, 'Expérience santé numérique appréciée.'],
            ['candidat14@airs.ma', 'Product owner santé numérique', 12, 'en_attente', null, null],
            ['candidat15@airs.ma', 'Technicien support applicatif', 10, 'en_cours', null, null],
            ['candidat17@airs.ma', 'Technicien support applicatif', 7, 'en_attente', null, null],
            ['candidat10@airs.ma', 'Ingénieur data / BI', 148, 'refusee', 130, 'Poste pourvu en interne.'],
            ['candidat4@airs.ma', 'Ingénieur data / BI', 140, 'refusee', 128, 'Profil développement plutôt que data.'],
            ['candidat11@airs.ma', 'Développeur back-end Symfony', 28, 'preselectionnee', null, 'Excellent niveau Symfony.'],
            ['candidat6@airs.ma', 'Développeur back-end Symfony', 25, 'en_cours', null, null],
            ['candidat9@airs.ma', 'Développeur back-end Symfony', 18, 'refusee', 12, 'Pas encore d’expérience Symfony.'],
            ['candidat15@airs.ma', 'Alternance — assistant logistique digital', 95, 'acceptee', 80, 'Motivation et rigueur remarquées.'],
            ['candidat17@airs.ma', 'Alternance — assistant logistique digital', 90, 'refusee', 82, 'Candidature retenue sur un autre profil.'],
        ];
    }

    /**
     * [email, offre, dans (jours ; négatif = passé), heure, mode, résultat, commentaire].
     */
    public static function entretiens(): array
    {
        return [
            ['candidat6@airs.ma', 'Développeur back-end PHP / Laravel', 2, '10:00', 'visio', 'en_attente', 'Entretien technique : architecture d’une API Laravel.'],
            ['candidat18@airs.ma', 'Développeur front-end Vue.js', 3, '14:30', 'presentiel', 'en_attente', 'Rencontre avec l’équipe produit.'],
            ['candidat8@airs.ma', 'Développeur front-end React', 1, '16:00', 'visio', 'en_attente', 'Revue de code sur un exercice React.'],
            ['candidat14@airs.ma', 'Analyste conformité', 4, '11:00', 'presentiel', 'en_attente', 'Entretien avec la responsable conformité.'],
            ['candidat12@airs.ma', 'Product owner santé numérique', 5, '15:00', 'visio', 'en_attente', 'Étude de cas : priorisation d’un backlog.'],
            ['candidat11@airs.ma', 'Développeur back-end Symfony', 6, '09:30', 'telephone', 'en_attente', 'Premier échange avec le lead développeur.'],
            ['candidat4@airs.ma', 'Développeur Java / Spring Boot', -48, '10:00', 'presentiel', 'favorable', 'Très bonne maîtrise technique et communication claire.'],
            ['candidat5@airs.ma', 'Développeur Java / Spring Boot', -41, '11:00', 'visio', 'defavorable', 'Bases Java insuffisantes pour le poste.'],
            ['candidat7@airs.ma', 'Ingénieur DevOps', -27, '14:00', 'visio', 'favorable', 'Démonstration convaincante d’un pipeline de déploiement.'],
            ['candidat18@airs.ma', 'Développeur front-end React', -36, '11:30', 'presentiel', 'favorable', 'Excellente vision de l’architecture front-end.'],
            ['candidat10@airs.ma', 'Data analyst', -76, '10:00', 'visio', 'favorable', 'Présentation d’un projet de prévision très aboutie.'],
        ];
    }

    public static function lettre(string $prenom, string $offre, float $experience): string
    {
        $parcours = $experience >= 1
            ? sprintf('Fort(e) de %s année(s) d’expérience, ', rtrim(rtrim(number_format($experience, 1, ',', ''), '0'), ','))
            : 'Récemment formé(e) et très motivé(e), ';

        return "Madame, Monsieur,\n\n{$parcours}je souhaite rejoindre votre équipe au poste de « {$offre} ». "
            ."Les missions décrites dans votre annonce correspondent à mes compétences et à mon projet professionnel.\n\n"
            ."Je serais heureux(se) de vous exposer ma motivation lors d’un entretien.\n\n{$prenom}";
    }
}
