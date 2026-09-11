# API Offres d'emploi — AI Recruitment System

Module 3 du backend. Couvre les règles de gestion **RG10** à **RG21**.

---

## 1. Principe général

Toutes les routes exigent un jeton Sanctum valide et un compte actif :

```
Authorization: Bearer <token>
Accept: application/json
```

Le module expose **deux surfaces distinctes** :

| Surface | Préfixe | Pour qui |
|---|---|---|
| Consultation | `/api/offres` | tout compte authentifié |
| Gestion | `/api/recruteur/offres` | recruteurs uniquement |

**Consultation** — la liste ne montre que les offres **ouvertes et non
expirées** (RG17, RG18). Une offre fermée, suspendue ou expirée reste
consultable par le recruteur qui l'a publiée et par l'administrateur, mais
renvoie `403` à un candidat.

**Gestion** — un recruteur ne voit, ne modifie et ne supprime que les offres
**qu'il a lui-même publiées** (RG12, RG13). Sa liste montre en revanche tous
les statuts, puisqu'il s'agit de son propre portefeuille.

| Action | Administrateur | Recruteur | Candidat |
|---|---|---|---|
| Lister les offres ouvertes | oui | oui | oui |
| Consulter une offre ouverte | oui | oui | oui |
| Consulter une offre fermée ou expirée | oui | la sienne uniquement | non |
| Lister ses propres offres | — | oui | non |
| Publier une offre | — | oui | non |
| Modifier / supprimer une offre | toutes | les siennes uniquement | non |

Un recruteur ne peut publier que dans un **département de son entreprise**
(RG9, RG11) : un `id_departement` hors de ce périmètre est refusé en `422`.

---

## 2. Enveloppe des collections

Comme pour les autres modules : **15 éléments par page** par défaut,
`page` et `per_page` (1 à 100).

```json
{
  "offres": [ { "...": "..." } ],
  "pagination": {
    "page_courante": 1,
    "par_page": 15,
    "total": 4,
    "derniere_page": 1
  }
}
```

Les offres sont triées par date de publication décroissante : les plus
récentes en premier.

---

## 3. Représentation d'une offre

```json
{
  "id_offre": 1,
  "titre": "Développeur back-end PHP / Laravel",
  "description": "Conception et maintenance des API du système d'information.",
  "localisation": "Tanger",

  "type_contrat": "cdi",
  "type_contrat_libelle": "CDI",

  "salaire": 14000.0,
  "experience_min": 2.0,

  "niveau_etude": "bac_5",
  "niveau_etude_libelle": "Bac +5 (Master, ingénieur)",

  "date_publication": "2026-08-28",
  "date_expiration": "2026-10-11",
  "expiree": false,

  "statut": "ouverte",
  "statut_libelle": "Ouverte",
  "accepte_candidatures": true,

  "id_recruteur": 1,
  "id_departement": 2,

  "departement": { "id_departement": 2, "nom": "Développement logiciel", "...": "..." },

  "competences": [
    {
      "id_competence": 1,
      "nom": "PHP",
      "categorie": "langage",
      "niveau_requis": "avance",
      "niveau_requis_libelle": "Avancé",
      "importance": "essentielle",
      "importance_libelle": "Essentielle"
    }
  ]
}
```

`accepte_candidatures` combine RG17 et RG18 : l'offre est ouverte **et** non
expirée. Le module 5 s'appuiera dessus pour refuser une candidature.

Les clés `departement`, `recruteur` et `competences` ne sont présentes que
lorsque la relation a été chargée ; `competences` l'est toujours sur les
listes et la consultation unitaire.

---

## 4. Consultation — tout compte authentifié

### 4.1 Liste des offres ouvertes (RG15, RG17, RG18)

```
GET /api/offres
Authorization: Bearer <token>
```

**Paramètres de requête**

| Champ | Type | Obligatoire | Contrainte |
|---|---|---|---|
| `page` | integer | non | minimum 1 |
| `per_page` | integer | non | entre 1 et 100 |
| `mots_cles` | string | non | 150 max — recherche sur le titre **et** la description |
| `localisation` | string | non | 100 max — correspondance exacte |
| `type_contrat` | string | non | `cdi`, `cdd`, `stage`, `alternance`, `freelance`, `interim` |
| `id_departement` | integer | non | doit exister |

**Exemple**

```
GET /api/offres?mots_cles=PHP&localisation=Tanger&type_contrat=cdi&per_page=5
```

Les offres fermées, suspendues ou expirées ne remontent jamais dans cette
liste, quel que soit le filtre appliqué.

---

### 4.2 Consultation d'une offre

```
GET /api/offres/{id_offre}
Authorization: Bearer <token>
```

**Réponse `200`** — voir la représentation en section 3, encapsulée :

```json
{ "offre": { "id_offre": 1, "titre": "Développeur back-end PHP / Laravel", "...": "..." } }
```

| Code | Situation |
|---|---|
| `403` | Offre fermée, suspendue ou expirée demandée par un candidat |
| `404` | Offre inexistante |

---

## 5. Gestion — espace recruteur

### 5.1 Liste de ses propres offres (RG12)

```
GET /api/recruteur/offres
Authorization: Bearer <token>       recruteur
```

Mêmes paramètres que la liste publique, plus :

| Champ | Type | Obligatoire | Contrainte |
|---|---|---|---|
| `statut` | string | non | `ouverte`, `fermee`, `suspendue` |

Cette liste montre **tous les statuts** : c'est le portefeuille du recruteur.
Elle ne contient que les offres qu'il a publiées.

---

### 5.2 Publication d'une offre (RG12, RG13, RG15 à RG21)

```
POST /api/recruteur/offres
Authorization: Bearer <token>       recruteur
```

**Corps de la requête**

| Champ | Type | Obligatoire | Contrainte |
|---|---|---|---|
| `titre` | string | oui | 150 max |
| `description` | string | oui | — |
| `type_contrat` | string | oui | une des valeurs de RG15 |
| `localisation` | string | oui | 100 max |
| `id_departement` | integer | oui | **doit relever de l'entreprise du recruteur** (RG9, RG11) |
| `salaire` | number | non | positif |
| `experience_min` | number | non | entre 0 et 60 (années) |
| `niveau_etude` | string | non | `bac`, `bac_2`, `bac_3`, `bac_5`, `bac_8` |
| `date_publication` | date | non | par défaut le jour même (RG16) |
| `date_expiration` | date | non | postérieure ou égale à la publication (RG17) |
| `statut` | string | non | par défaut `ouverte` (RG18) |
| `competences` | array | non | compétences requises, voir ci-dessous |
| `competences[].id_competence` | integer | oui | doit exister, sans doublon dans la liste |
| `competences[].niveau_requis` | string | oui | `debutant`, `intermediaire`, `avance`, `expert` |
| `competences[].importance` | string | oui | `essentielle`, `importante`, `souhaitee` |

**Exemple de requête**

```json
{
  "titre": "Développeur back-end PHP / Laravel",
  "description": "Conception et maintenance des API du système d'information.",
  "type_contrat": "cdi",
  "localisation": "Tanger",
  "salaire": 14000,
  "experience_min": 2,
  "niveau_etude": "bac_5",
  "date_expiration": "2026-12-31",
  "id_departement": 2,
  "competences": [
    { "id_competence": 1, "niveau_requis": "avance",        "importance": "essentielle" },
    { "id_competence": 5, "niveau_requis": "intermediaire", "importance": "souhaitee" }
  ]
}
```

**Réponse `201`**

```json
{
  "message": "Offre publiée avec succès.",
  "offre": { "id_offre": 7, "titre": "Développeur back-end PHP / Laravel", "...": "..." }
}
```

L'offre et ses compétences requises sont écrites dans la même transaction :
si une compétence est invalide, l'offre n'est pas créée.

| Code | Situation |
|---|---|
| `403` | Compte non recruteur |
| `422` | Champ obligatoire manquant, énumération inconnue, ou département hors de votre entreprise |

---

### 5.3 Modification d'une offre (RG12, RG13)

```
PATCH /api/recruteur/offres/{id_offre}
Authorization: Bearer <token>       recruteur ayant publié l'offre
```

Tous les champs sont facultatifs ; seuls ceux transmis sont modifiés. Les
contraintes sont celles de la publication.

**Les compétences suivent une règle particulière :**

| `competences` | Effet |
|---|---|
| absente du corps | compétences **inchangées** |
| tableau fourni | **remplacement complet** de la liste |
| tableau vide `[]` | toutes les compétences requises sont retirées |

**Fermer une offre** (RG18) revient à modifier son statut :

```json
{ "statut": "fermee" }
```

**Réponse `200`**

```json
{
  "message": "Offre mise à jour.",
  "offre": { "id_offre": 7, "statut": "fermee", "accepte_candidatures": false, "...": "..." }
}
```

| Code | Situation |
|---|---|
| `403` | Offre publiée par un autre recruteur |
| `404` | Offre inexistante |
| `422` | Validation échouée |

---

### 5.4 Suppression d'une offre

```
DELETE /api/recruteur/offres/{id_offre}
Authorization: Bearer <token>       recruteur ayant publié l'offre
```

**Réponse `204`** — corps vide. Les compétences requises sont supprimées en
cascade avec l'offre.

> Lorsque le module « Candidatures » existera, la suppression d'une offre
> ayant reçu des candidatures devra être refusée (RG30).

---

## 6. Énumérations

Valeurs acceptées par l'API et libellés à afficher.

### Type de contrat (RG15)

| Valeur | Libellé |
|---|---|
| `cdi` | CDI |
| `cdd` | CDD |
| `stage` | Stage |
| `alternance` | Alternance |
| `freelance` | Freelance |
| `interim` | Intérim |

### Statut de l'offre (RG18)

| Valeur | Libellé | Visible des candidats |
|---|---|---|
| `ouverte` | Ouverte | oui, si non expirée |
| `fermee` | Fermée | non |
| `suspendue` | Suspendue | non |

### Niveau d'études

| Valeur | Libellé |
|---|---|
| `bac` | Baccalauréat |
| `bac_2` | Bac +2 (DUT, BTS) |
| `bac_3` | Bac +3 (Licence) |
| `bac_5` | Bac +5 (Master, ingénieur) |
| `bac_8` | Bac +8 (Doctorat) |

### Niveau de compétence requis (RG21)

| Valeur | Libellé |
|---|---|
| `debutant` | Débutant |
| `intermediaire` | Intermédiaire |
| `avance` | Avancé |
| `expert` | Expert |

### Importance d'une compétence (RG21)

| Valeur | Libellé |
|---|---|
| `essentielle` | Essentielle |
| `importante` | Importante |
| `souhaitee` | Souhaitée |

L'ordre de ces deux dernières énumérations est significatif : il servira à
pondérer le score de compatibilité du module 6 (RG40).

---

## 7. Format des erreurs

Identique aux autres modules : toujours du JSON sur `/api/*`.

### Récapitulatif

| Code | Signification | Conduite à tenir côté client |
|---|---|---|
| `200` | Requête traitée | — |
| `201` | Offre publiée | Rediriger vers la liste du recruteur |
| `204` | Offre supprimée | Retirer l'élément de la liste |
| `401` | Jeton absent, invalide ou expiré | Supprimer le jeton, rediriger vers la connexion |
| `403` | Rôle insuffisant, ou offre publiée par un autre recruteur | Afficher un message, **ne pas** déconnecter |
| `404` | Offre inexistante | Rafraîchir la liste |
| `422` | Validation échouée | Afficher les erreurs sous chaque champ |

Cas particulier à signaler à l'utilisateur avec soin :

```json
{
  "message": "Ce département n'appartient pas à votre entreprise.",
  "errors": {
    "id_departement": ["Ce département n'appartient pas à votre entreprise."]
  }
}
```

Le formulaire de publication doit donc alimenter sa liste déroulante de
départements depuis `GET /api/entreprises/{id_entreprise}/departements`, avec
l'entreprise du recruteur connecté — disponible dans
`utilisateur.profil_recruteur.entreprise` renvoyé par `GET /api/auth/moi`.

---

## 8. Données de démonstration

`php artisan db:seed` publie six offres :

| Entreprise | Offre | Statut | Visible des candidats |
|---|---|---|---|
| TechnoMaroc | Développeur back-end PHP / Laravel | ouverte | oui |
| TechnoMaroc | Développeur front-end Vue.js | ouverte | oui |
| TechnoMaroc | Administrateur systèmes et réseaux | ouverte | oui |
| TechnoMaroc | Stage — assistant ressources humaines | fermée, expirée | non |
| Atlas Digital | Ingénieur data / intelligence artificielle | ouverte | oui |
| Atlas Digital | Chargé de marketing digital | suspendue | non |

Quatre offres sur six remontent donc dans `GET /api/offres`, ce qui rend le
filtrage de RG17 et RG18 vérifiable immédiatement.

Chaque offre porte ses compétences requises avec niveau et importance, de quoi
alimenter le futur calcul de compatibilité (RG40).

---

## 9. Collection Postman

Les requêtes de ce module figurent dans le dossier **Offres** de
`postman/AIRS.postman_collection.json`. La liste des offres alimente la
variable `id_offre` utilisée par les requêtes suivantes.
