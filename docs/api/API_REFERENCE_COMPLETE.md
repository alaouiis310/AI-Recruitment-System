# Référence complète de l'API backend

Cette page est le point d'entrée du frontend. Elle décrit l'API disponible sur
la branche `develop` après intégration des modules RG1 à RG45.

## Convention commune

- URL locale : `http://localhost:8000/api`
- Authentification : `Authorization: Bearer <token>`
- Contenu JSON : `Accept: application/json` et `Content-Type: application/json`
- Listes : `?page=1&per_page=15` (maximum 100)
- Une liste paginée renvoie la collection demandée et `pagination` avec
  `page_courante`, `par_page`, `total` et `derniere_page`.
- Erreurs : `401` sans jeton, `403` mauvais rôle/propriétaire, `404` ressource
  absente, `422` validation, `429` limite de connexion/inscription.

Le jeton est renvoyé par la connexion sous `token`. Les valeurs d'enum sont
toujours en minuscules (`ouverte`, `preselectionnee`, `visio`, etc.).

## Santé et authentification

| Méthode | Endpoint | Accès | Usage |
|---|---|---|---|
| GET | `/health` | Public | Vérifier que l'API répond |
| POST | `/auth/inscription/candidat` | Public | Créer un compte candidat |
| POST | `/auth/inscription/recruteur` | Public | Créer un compte recruteur |
| POST | `/auth/connexion` | Public | Obtenir le jeton Sanctum |
| GET | `/auth/moi` | Authentifié | Recharger l'utilisateur et son profil |
| PATCH | `/auth/profil` | Authentifié | Modifier nom, prénom et données du profil |
| PATCH | `/auth/mot-de-passe` | Authentifié | Changer le mot de passe |
| POST | `/auth/deconnexion` | Authentifié | Révoquer le jeton courant |
| POST | `/auth/deconnexion-globale` | Authentifié | Révoquer tous les jetons |

Voir `API_AUTHENTIFICATION.md` pour les payloads complets.

## Tableaux de bord

| Méthode | Endpoint | Accès | Données principales |
|---|---|---|---|
| GET | `/candidat/tableau-de-bord` | Candidat | Statistiques, candidatures, entretiens et offres récentes |
| GET | `/recruteur/tableau-de-bord` | Recruteur | Offres, pipeline, entretiens et candidatures récentes |
| GET | `/admin/tableau-de-bord` | Admin | Statistiques globales et candidatures récentes |
| GET | `/admin/analytiques` | Admin | Même contrat statistique pour la page analytique |

## Candidat

### Profil, CV et compétences

| Méthode | Endpoint | Usage |
|---|---|---|
| POST | `/candidat/cv` | Envoyer `cv` en multipart, PDF, maximum 10 Mio |
| DELETE | `/candidat/cv` | Supprimer le CV |
| POST | `/candidat/photo` | Envoyer `photo` en multipart |
| DELETE | `/candidat/photo` | Supprimer la photo |
| GET | `/candidat/competences` | Lister les compétences déclarées |
| PUT | `/candidat/competences` | Remplacer la liste complète |
| POST | `/candidat/competences` | Ajouter ou mettre à jour une compétence |
| DELETE | `/candidat/competences/{competence}` | Retirer une compétence |

### Candidatures, analyses, tests et entretiens

| Méthode | Endpoint | Usage |
|---|---|---|
| GET | `/candidat/candidatures` | Liste personnelle, filtre `statut` |
| POST | `/candidat/candidatures` | Postuler avec `id_offre`, `lettre_motivation` optionnelle |
| GET | `/candidat/candidatures/{candidature}` | Détail personnel |
| DELETE | `/candidat/candidatures/{candidature}` | Retirer un dossier non définitif |
| GET | `/candidat/candidatures/{candidature}/analyse` | Résultat de l'analyse IA |
| GET | `/candidat/candidatures/{candidature}/entretiens` | Entretiens de ce dossier |
| GET | `/candidat/candidatures/{candidature}/tests` | Tests reçus et résultats |

Voir `API_PROFIL_CANDIDAT.md`, `API_CANDIDATURES.md` et
`API_ANALYSE_IA.md` pour les structures détaillées.

## Recruteur

### Offres

| Méthode | Endpoint | Usage |
|---|---|---|
| GET | `/recruteur/offres` | Toutes ses offres, filtre `statut` inclus |
| POST | `/recruteur/offres` | Publier une offre |
| PATCH | `/recruteur/offres/{offre}` | Modifier sa propre offre |
| DELETE | `/recruteur/offres/{offre}` | Supprimer sa propre offre |
| PUT | `/recruteur/offres/{offre}/tests` | Remplacer les tests proposés : `{ "tests": [1, 2] }` |

### Candidatures et IA

| Méthode | Endpoint | Usage |
|---|---|---|
| GET | `/recruteur/candidatures` | Pipeline classé par score, filtres `statut`, `id_offre`, `recherche` |
| GET | `/recruteur/candidatures/{candidature}` | Détail d'un dossier reçu |
| PATCH | `/recruteur/candidatures/{candidature}/statut` | Avancer le statut et ajouter un commentaire |
| GET | `/recruteur/candidatures/{candidature}/analyse` | Consulter l'analyse IA |
| POST | `/recruteur/candidatures/{candidature}/analyse` | Relancer l'analyse |

### Entretiens

| Méthode | Endpoint | Usage |
|---|---|---|
| GET | `/recruteur/entretiens` | Liste, filtres `resultat`, `a_venir`, `per_page` |
| GET | `/recruteur/entretiens/{entretien}` | Détail |
| POST | `/recruteur/candidatures/{candidature}/entretiens` | Planifier |
| GET | `/recruteur/candidatures/{candidature}/entretiens` | Liste par dossier |
| PATCH | `/recruteur/entretiens/{entretien}` | Replanifier ou saisir l'issue |
| DELETE | `/recruteur/entretiens/{entretien}` | Annuler |

Payload de création :

```json
{
  "date": "2026-10-15",
  "heure": "14:30",
  "mode": "visio",
  "lien_si_online": "https://meet.example.ma/abc",
  "commentaire": "Entretien technique"
}
```

`mode` : `presentiel`, `visio`, `telephone`. `resultat` : `en_attente`,
`favorable`, `defavorable`. Une visioconférence exige `lien_si_online`.

### Tests techniques

| Méthode | Endpoint | Usage |
|---|---|---|
| GET | `/recruteur/resultats-tests` | Tous les passages liés à ses offres |
| POST | `/recruteur/candidatures/{candidature}/tests/{test}` | Envoyer un test et notifier le candidat |
| GET | `/recruteur/candidatures/{candidature}/tests` | Résultats d'un dossier |
| PATCH | `/recruteur/resultats-tests/{resultat}/score` | Saisir score, commentaire et date |

Payload de score :

```json
{
  "score_obtenu": 82,
  "commentaire": "Bon niveau algorithmique.",
  "date_passage": "2026-09-20"
}
```

## Administration

Tous ces endpoints exigent le rôle `administrateur`.

### Comptes

| Méthode | Endpoint | Usage |
|---|---|---|
| GET | `/admin/candidats` | Filtres `recherche`, `etat_compte`, pagination |
| POST | `/admin/candidats` | Créer un candidat (même payload que l'inscription) |
| GET | `/admin/candidats/{candidat}` | Profil, compétences et candidatures |
| GET | `/admin/recruteurs` | Filtres `recherche`, `etat_compte`, pagination |
| POST | `/admin/recruteurs` | Créer un recruteur (même payload que l'inscription) |
| GET | `/admin/recruteurs/{recruteur}` | Profil, entreprise et offres |
| PATCH | `/admin/utilisateurs/{utilisateur}/etat` | `{ "etat_compte": "actif|suspendu|desactive" }` |

Suspendre ou désactiver un compte révoque tous ses jetons. Un administrateur
ne peut pas suspendre son propre compte.

### Offres et candidatures globales

| Méthode | Endpoint | Usage |
|---|---|---|
| GET | `/admin/offres` | Toutes les offres et tous les statuts |
| POST | `/admin/offres` | Créer avec `id_recruteur` et `id_departement` |
| GET | `/admin/offres/{offre}` | Détail complet, tests et nombre de dossiers |
| PATCH | `/admin/offres/{offre}` | Modifier |
| DELETE | `/admin/offres/{offre}` | Supprimer |
| GET | `/admin/candidatures` | Filtres `statut`, `id_offre`, `recherche` |
| GET | `/admin/candidatures/export` | Télécharger le CSV UTF-8 filtrable |
| GET | `/admin/candidatures/{candidature}` | Détail avec IA, entretiens et tests |
| PATCH | `/admin/candidatures/{candidature}/statut` | Avancer le statut |
| DELETE | `/admin/candidatures/{candidature}` | Supprimer |

La création d'offre admin reprend le payload décrit dans `API_OFFRES.md` et
ajoute `id_recruteur`. Le département doit appartenir à l'entreprise de ce
recruteur.

## Ressources partagées

### Entreprises et départements

| Méthode | Endpoint | Accès |
|---|---|---|
| GET | `/entreprises` | Tout compte |
| POST | `/entreprises` | Admin/recruteur selon la policy |
| GET | `/entreprises/{entreprise}` | Tout compte |
| PATCH | `/entreprises/{entreprise}` | Admin ou recruteur propriétaire |
| DELETE | `/entreprises/{entreprise}` | Selon la policy |
| GET | `/entreprises/{entreprise}/departements` | Tout compte |
| POST | `/entreprises/{entreprise}/departements` | Admin/recruteur autorisé |
| GET | `/departements/{departement}` | Tout compte |
| PATCH | `/departements/{departement}` | Admin/recruteur autorisé |
| DELETE | `/departements/{departement}` | Admin/recruteur autorisé |

### Référentiel de compétences

| Méthode | Endpoint | Accès |
|---|---|---|
| GET | `/competences` | Tout compte |
| GET | `/competences/categories` | Tout compte |
| GET | `/competences/{competence}` | Tout compte |
| POST | `/competences` | Admin |
| PATCH | `/competences/{competence}` | Admin |
| DELETE | `/competences/{competence}` | Admin |

### Offres visibles

| Méthode | Endpoint | Usage |
|---|---|---|
| GET | `/offres` | Offres ouvertes non expirées; filtres `mots_cles`, `localisation`, `type_contrat`, `id_departement` |
| GET | `/offres/{offre}` | Détail; une offre non publiable reste réservée à son auteur/admin |

### Catalogue de tests

| Méthode | Endpoint | Accès |
|---|---|---|
| GET | `/tests` | Recruteur/admin; filtre `recherche` |
| GET | `/tests/{test}` | Recruteur/admin |
| POST | `/tests` | Admin |
| PATCH | `/tests/{test}` | Admin |
| DELETE | `/tests/{test}` | Admin |

Création : `titre` et `duree` obligatoires, `description` et `score_max`
optionnels.

### Notifications

| Méthode | Endpoint | Usage |
|---|---|---|
| GET | `/notifications` | Liste personnelle; `?non_lues=1` |
| PATCH | `/notifications/{notification}/lue` | Marquer une notification personnelle |
| POST | `/notifications/toutes-lues` | Tout marquer comme lu |

## Valeurs de démonstration

Après `php artisan migrate:fresh --seed`, le mot de passe commun est
`Password123` :

- `admin@airs.ma`
- `recruteur@airs.ma`
- `candidat@airs.ma`

La base de démonstration contient aussi des offres, candidatures, analyses IA,
entretiens, tests techniques, résultats et notifications.
