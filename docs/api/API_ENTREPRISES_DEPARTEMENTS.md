# API Entreprises & Départements — AI Recruitment System

Module 1 du backend. Couvre les règles de gestion **RG5**, **RG6**, **RG8**
et **RG9**.

---

## 1. Principe général

Toutes les routes de ce module exigent un jeton Sanctum valide et un compte
actif :

```
Authorization: Bearer <token>
Accept: application/json
```

L'en-tête `Accept: application/json` est obligatoire : sans lui, les erreurs
sont renvoyées en HTML et non en JSON.

**Lecture** — ouverte à tout utilisateur authentifié, quel que soit son rôle.

**Écriture** — réservée aux administrateurs et aux recruteurs. Le rôle seul ne
suffit pas : la propriété de l'enregistrement est vérifiée par une politique
d'accès.

| Action | Administrateur | Recruteur | Candidat |
|---|---|---|---|
| Lister / consulter une entreprise | oui | oui | oui |
| Créer une entreprise | oui | non | non |
| Modifier une entreprise | toutes | la sienne uniquement | non |
| Supprimer une entreprise | oui, si elle est vide | non | non |
| Lister / consulter un département | oui | oui | oui |
| Créer un département | dans toute entreprise | dans la sienne uniquement | non |
| Modifier / supprimer un département | tous | ceux de son entreprise | non |

Un recruteur qui vise une entreprise autre que la sienne reçoit un `403`
(RG7 : il appartient à exactement une entreprise).

Le recruteur crée son entreprise lors de son inscription
(`POST /api/auth/inscription/recruteur`) ; il n'a donc pas à en créer d'autre.

---

## 2. Enveloppe des collections

Les listes sont paginées : **15 éléments par page** par défaut.

| Paramètre | Type | Défaut | Contrainte |
|---|---|---|---|
| `page` | integer | `1` | minimum 1 |
| `per_page` | integer | `15` | entre 1 et 100 |

```json
{
  "entreprises": [ { "...": "..." } ],
  "pagination": {
    "page_courante": 1,
    "par_page": 15,
    "total": 42,
    "derniere_page": 3
  }
}
```

La ressource est exposée sous son nom français au pluriel ; le bloc
`pagination` est identique pour toutes les collections de l'API.

---

## 3. Entreprises

### 3.1 Liste des entreprises

```
GET /api/entreprises
Authorization: Bearer <token>
```

**Paramètres de requête**

| Champ | Type | Obligatoire | Contrainte |
|---|---|---|---|
| `page` | integer | non | minimum 1 |
| `per_page` | integer | non | entre 1 et 100 |
| `recherche` | string | non | 150 max — recherche partielle sur le nom |
| `ville` | string | non | 100 max — correspondance exacte |
| `secteur` | string | non | 100 max — correspondance exacte |

**Exemple**

```
GET /api/entreprises?recherche=Techno&ville=Tanger&per_page=5
```

**Réponse `200`**

```json
{
  "entreprises": [
    {
      "id_entreprise": 1,
      "nom": "TechnoMaroc",
      "secteur": "Technologies de l'information",
      "adresse": "12 avenue Mohammed V",
      "ville": "Tanger",
      "site_web": "https://technomaroc.example.ma",
      "description": "Société de services numériques basée à Tanger.",
      "date_creation": "2026-08-15",
      "nombre_recruteurs": 1,
      "nombre_departements": 5
    }
  ],
  "pagination": {
    "page_courante": 1,
    "par_page": 5,
    "total": 1,
    "derniere_page": 1
  }
}
```

Les entreprises sont triées par nom. `nombre_recruteurs` et
`nombre_departements` ne sont présents que sur les listes et la consultation
unitaire ; ils sont absents de la réponse d'authentification.

---

### 3.2 Consultation d'une entreprise

```
GET /api/entreprises/{id_entreprise}
Authorization: Bearer <token>
```

**Réponse `200`**

```json
{
  "entreprise": {
    "id_entreprise": 1,
    "nom": "TechnoMaroc",
    "secteur": "Technologies de l'information",
    "adresse": "12 avenue Mohammed V",
    "ville": "Tanger",
    "site_web": "https://technomaroc.example.ma",
    "description": "Société de services numériques basée à Tanger.",
    "date_creation": "2026-08-15",
    "nombre_recruteurs": 1,
    "nombre_departements": 5
  }
}
```

Les lectures ne comportent pas de clé `message` : celle-ci est réservée aux
opérations d'écriture.

---

### 3.3 Création d'une entreprise (RG5)

```
POST /api/entreprises
Authorization: Bearer <token>       administrateur uniquement
```

**Corps de la requête**

| Champ | Type | Obligatoire | Contrainte |
|---|---|---|---|
| `nom` | string | oui | 150 caractères max |
| `secteur` | string | non | 100 max |
| `adresse` | string | non | 255 max |
| `ville` | string | non | 100 max |
| `site_web` | url | non | 255 max |
| `description` | string | non | — |

**Exemple de requête**

```json
{
  "nom": "Atlas Digital",
  "secteur": "Conseil et transformation digitale",
  "adresse": "45 boulevard Zerktouni",
  "ville": "Casablanca",
  "site_web": "https://atlasdigital.example.ma",
  "description": "Cabinet de conseil en transformation digitale."
}
```

**Réponse `201`**

```json
{
  "message": "Entreprise créée avec succès.",
  "entreprise": {
    "id_entreprise": 2,
    "nom": "Atlas Digital",
    "secteur": "Conseil et transformation digitale",
    "adresse": "45 boulevard Zerktouni",
    "ville": "Casablanca",
    "site_web": "https://atlasdigital.example.ma",
    "description": "Cabinet de conseil en transformation digitale.",
    "date_creation": "2026-09-10"
  }
}
```

Un recruteur reçoit `403` : son entreprise est créée à l'inscription et, selon
RG7, il n'en a qu'une.

---

### 3.4 Modification d'une entreprise (RG6, RG7)

```
PATCH /api/entreprises/{id_entreprise}
Authorization: Bearer <token>       administrateur ou recruteur propriétaire
```

Tous les champs sont facultatifs ; seuls ceux transmis sont modifiés. Les
contraintes sont celles de la création.

**Exemple de requête**

```json
{ "ville": "Tétouan", "site_web": "https://technomaroc.example.ma" }
```

**Réponse `200`**

```json
{
  "message": "Entreprise mise à jour.",
  "entreprise": { "id_entreprise": 1, "nom": "TechnoMaroc", "ville": "Tétouan", "...": "..." }
}
```

| Code | Situation |
|---|---|
| `403` | Recruteur visant une entreprise qui n'est pas la sienne |
| `404` | Entreprise inexistante |
| `422` | `site_web` non conforme, `nom` trop long… |

---

### 3.5 Suppression d'une entreprise (RG6, RG8)

```
DELETE /api/entreprises/{id_entreprise}
Authorization: Bearer <token>       administrateur uniquement
```

**Réponse `204`** — corps vide.

La suppression est **refusée tant que l'entreprise n'est pas vide**. La
cascade de la clé étrangère supprimerait les profils recruteurs en laissant
leurs comptes utilisateurs orphelins, et effacerait les départements auxquels
les offres seront rattachées (RG10, RG11).

**Réponse `409`**

```json
{
  "message": "Cette entreprise ne peut pas être supprimée : elle compte encore 1 recruteur(s) et 5 département(s).",
  "errors": {
    "entreprise": [
      "Détachez les recruteurs et supprimez les départements au préalable."
    ]
  }
}
```

Détachez les recruteurs et supprimez les départements, puis relancez la
requête.

---

## 4. Départements

Un département appartient à une seule entreprise (RG9). La collection est donc
imbriquée sous son entreprise : **`id_entreprise` provient toujours de l'URL et
n'est jamais lu dans le corps de la requête.** Un `id_entreprise` transmis dans
le corps est ignoré sans erreur.

Les routes visant un département précis restent à plat : sa clé suffit à
l'identifier.

### 4.1 Liste des départements d'une entreprise (RG8)

```
GET /api/entreprises/{id_entreprise}/departements
Authorization: Bearer <token>
```

**Paramètres de requête**

| Champ | Type | Obligatoire | Contrainte |
|---|---|---|---|
| `page` | integer | non | minimum 1 |
| `per_page` | integer | non | entre 1 et 100 |
| `recherche` | string | non | 100 max — recherche partielle sur le nom |

**Réponse `200`**

```json
{
  "departements": [
    {
      "id_departement": 1,
      "nom": "Ressources humaines",
      "description": "Recrutement, paie et gestion des carrières.",
      "id_entreprise": 1,
      "date_creation": "2026-08-15"
    }
  ],
  "pagination": {
    "page_courante": 1,
    "par_page": 15,
    "total": 5,
    "derniere_page": 1
  }
}
```

Les départements sont triés par nom. Seuls ceux de l'entreprise indiquée dans
l'URL sont renvoyés.

---

### 4.2 Consultation d'un département

```
GET /api/departements/{id_departement}
Authorization: Bearer <token>
```

**Réponse `200`**

```json
{
  "departement": {
    "id_departement": 1,
    "nom": "Ressources humaines",
    "description": "Recrutement, paie et gestion des carrières.",
    "id_entreprise": 1,
    "date_creation": "2026-08-15"
  }
}
```

---

### 4.3 Création d'un département (RG8, RG9)

```
POST /api/entreprises/{id_entreprise}/departements
Authorization: Bearer <token>       administrateur, ou recruteur de cette entreprise
```

**Corps de la requête**

| Champ | Type | Obligatoire | Contrainte |
|---|---|---|---|
| `nom` | string | oui | 100 max, unique **au sein de l'entreprise** (RG8) |
| `description` | string | non | — |

**Exemple de requête**

```json
{
  "nom": "Développement logiciel",
  "description": "Conception et réalisation des applications métier."
}
```

**Réponse `201`**

```json
{
  "message": "Département créé avec succès.",
  "departement": {
    "id_departement": 2,
    "nom": "Développement logiciel",
    "description": "Conception et réalisation des applications métier.",
    "id_entreprise": 1,
    "date_creation": "2026-09-10"
  }
}
```

L'unicité porte sur le couple `(id_entreprise, nom)` : deux entreprises
peuvent chacune avoir un département « Ressources humaines », mais une même
entreprise ne peut pas en avoir deux.

**Réponse `422` — doublon dans la même entreprise**

```json
{
  "message": "Cette entreprise possède déjà un département portant ce nom.",
  "errors": {
    "nom": ["Cette entreprise possède déjà un département portant ce nom."]
  }
}
```

---

### 4.4 Modification d'un département

```
PATCH /api/departements/{id_departement}
Authorization: Bearer <token>       administrateur, ou recruteur de l'entreprise
```

| Champ | Type | Obligatoire | Contrainte |
|---|---|---|---|
| `nom` | string | non | 100 max, unique au sein de l'entreprise |
| `description` | string | non | — |

**Réponse `200`**

```json
{
  "message": "Département mis à jour.",
  "departement": { "id_departement": 2, "nom": "Ingénierie logicielle", "...": "..." }
}
```

Conserver le nom actuel est accepté : la règle d'unicité ignore
l'enregistrement en cours de modification.

---

### 4.5 Suppression d'un département

```
DELETE /api/departements/{id_departement}
Authorization: Bearer <token>       administrateur, ou recruteur de l'entreprise
```

**Réponse `204`** — corps vide.

> La suppression d'un département portant des offres est refusée par une
> réponse `409` (RG10, RG11).

---

## 5. Format des erreurs

Comme pour l'authentification, les erreurs sur `/api/*` sont toujours en JSON.

### Erreur de validation — `422`

```json
{
  "message": "Le nom de l'entreprise est obligatoire.",
  "errors": {
    "nom": ["Le nom de l'entreprise est obligatoire."]
  }
}
```

### Accès refusé — `403`

```json
{
  "message": "Accès refusé : vous n'êtes pas autorisé à effectuer cette action."
}
```

Renvoyé dans deux situations distinctes :

- **mauvais rôle** — un candidat tentant une écriture ; le message précise
  alors les rôles attendus ;
- **mauvais propriétaire** — un recruteur visant une entreprise ou un
  département qui ne relève pas de la sienne (RG7, RG9).

### Ressource introuvable — `404`

```json
{ "message": "Ressource introuvable." }
```

### Récapitulatif

| Code | Signification | Conduite à tenir côté client |
|---|---|---|
| `200` | Requête traitée | — |
| `201` | Ressource créée | Ajouter l'élément à la liste affichée |
| `204` | Ressource supprimée | Retirer l'élément de la liste |
| `401` | Jeton absent, invalide ou expiré | Supprimer le jeton, rediriger vers la connexion |
| `403` | Rôle insuffisant ou non-propriétaire | Afficher un message, **ne pas** déconnecter |
| `404` | Ressource inexistante | Afficher « introuvable », rafraîchir la liste |
| `409` | Suppression impossible en l'état | Afficher `message` et la marche à suivre |
| `422` | Validation échouée | Afficher les erreurs sous chaque champ |

> Le code `409` complète la liste des codes de réponse. Il est employé pour une
> requête bien formée qui entre en conflit avec l'état de la ressource —
> ce qu'un `422`, réservé aux erreurs de validation du corps, ne décrit pas.

---

## 6. Comptes de démonstration

Créés par `php artisan db:seed`. Mot de passe commun : `Password123`

| Rôle | E-mail | Entreprise |
|---|---|---|
| Administrateur | `admin@airs.ma` | — |
| Recruteur | `recruteur@airs.ma` | TechnoMaroc (5 départements) |
| Recruteur | `recruteur2@airs.ma` | Atlas Digital (3 départements) |
| Candidat | `candidat@airs.ma` | — |

Les deux recruteurs permettent de rejouer le refus d'accès entre entreprises :
connecté avec `recruteur@airs.ma`, un `PATCH` sur l'entreprise d'Atlas Digital
renvoie `403`.

Les deux entreprises possèdent un département « Ressources humaines » : c'est
la démonstration que l'unicité porte sur le couple `(id_entreprise, nom)`.

---

## 7. Collection Postman

Le dossier `postman/` du dépôt contient :

- `AIRS.postman_collection.json` — authentification, entreprises et
  départements, avec scripts de test sur les codes de statut ;
- `AIRS_Local.postman_environment.json` — variables `base_url`, `token`,
  `id_entreprise`, `id_departement`.

Le jeton est enregistré automatiquement dans la variable `token` par le script
de test de la requête de connexion.
