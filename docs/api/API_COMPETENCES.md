# API des compétences — AI Recruitment System

Référentiel partagé des compétences (RG20, RG25). Il alimente les compétences
requises par une offre (`requerir`, module 3) et celles déclarées par un
candidat (`posseder`, module 4).

---

## 1. Principe général

Toutes les requêtes exigent un jeton Sanctum et l'en-tête `Accept` :

```
Authorization: Bearer <token>
Accept: application/json
```

Sans `Accept: application/json`, les erreurs sont renvoyées en HTML.

**Lecture** : tout compte authentifié, quel que soit son rôle.
**Écriture** : administrateur uniquement — le référentiel est commun à toutes
les entreprises, il ne peut pas être modifié par un recruteur.

---

## 2. Catégories

Les catégories sont une énumération fermée. N'utilisez jamais une chaîne libre :
récupérez la liste via `GET /api/competences/categories`.

| Valeur | Libellé |
|---|---|
| `langage` | Langage de programmation |
| `framework` | Framework |
| `outil` | Outil |
| `base_de_donnees` | Base de données |
| `langue` | Langue |
| `savoir_etre` | Savoir-être |

---

## 3. Endpoints

### 3.1 Liste des compétences

```
GET /api/competences
Authorization: Bearer <token>
```

**Paramètres de requête**

| Champ | Type | Obligatoire | Contrainte |
|---|---|---|---|
| `page` | integer | non | ≥ 1, défaut 1 |
| `per_page` | integer | non | entre 1 et 100, défaut 15 |
| `recherche` | string | non | 100 max, recherche partielle sur le nom |
| `categorie` | string | non | une des valeurs de la section 2 |

**Réponse `200`**

```json
{
  "competences": [
    {
      "id_competence": 1,
      "nom": "PHP",
      "categorie": "langage",
      "categorie_libelle": "Langage de programmation",
      "description": "Langage serveur, socle de Laravel.",
      "date_creation": "2026-09-10"
    }
  ],
  "pagination": {
    "page_courante": 1,
    "par_page": 15,
    "total": 36,
    "derniere_page": 3
  }
}
```

Le tri est fixe : par catégorie, puis par nom.

---

### 3.2 Catégories disponibles

```
GET /api/competences/categories
Authorization: Bearer <token>
```

**Réponse `200`**

```json
{
  "categories": [
    { "valeur": "langage", "libelle": "Langage de programmation" },
    { "valeur": "framework", "libelle": "Framework" },
    { "valeur": "outil", "libelle": "Outil" },
    { "valeur": "base_de_donnees", "libelle": "Base de données" },
    { "valeur": "langue", "libelle": "Langue" },
    { "valeur": "savoir_etre", "libelle": "Savoir-être" }
  ]
}
```

---

### 3.3 Consultation d'une compétence

```
GET /api/competences/{id_competence}
Authorization: Bearer <token>
```

**Réponse `200`**

```json
{
  "competence": {
    "id_competence": 1,
    "nom": "PHP",
    "categorie": "langage",
    "categorie_libelle": "Langage de programmation",
    "description": "Langage serveur, socle de Laravel.",
    "date_creation": "2026-09-10"
  }
}
```

---

### 3.4 Création — administrateur

```
POST /api/competences
Authorization: Bearer <token administrateur>
```

**Corps de la requête**

| Champ | Type | Obligatoire | Contrainte |
|---|---|---|---|
| `nom` | string | oui | 100 max, unique dans le référentiel (RG20, RG25) |
| `categorie` | string | oui | une des valeurs de la section 2 |
| `description` | string | non | texte libre |

**Exemple de requête**

```json
{
  "nom": "Kotlin",
  "categorie": "langage",
  "description": "Langage de la plateforme JVM."
}
```

**Réponse `201`**

```json
{
  "message": "Compétence créée avec succès.",
  "competence": {
    "id_competence": 37,
    "nom": "Kotlin",
    "categorie": "langage",
    "categorie_libelle": "Langage de programmation",
    "description": "Langage de la plateforme JVM.",
    "date_creation": "2026-09-10"
  }
}
```

Un recruteur ou un candidat reçoit `403` : le référentiel est commun.

---

### 3.5 Modification — administrateur

```
PATCH /api/competences/{id_competence}
Authorization: Bearer <token administrateur>
```

Tous les champs sont facultatifs ; seuls ceux transmis sont modifiés. Le
contrôle d'unicité ignore la compétence en cours de modification, il est donc
possible de renvoyer son propre nom sans déclencher d'erreur.

**Réponse `200`**

```json
{
  "message": "Compétence mise à jour.",
  "competence": { "id_competence": 37, "nom": "Vue.js", "categorie": "framework", "categorie_libelle": "Framework", "description": null, "date_creation": "2026-09-10" }
}
```

---

### 3.6 Suppression — administrateur

```
DELETE /api/competences/{id_competence}
Authorization: Bearer <token administrateur>
```

**Réponse `204`** — corps vide.

> À partir du module 3, une compétence référencée par une offre (`requerir`) ou
> par un candidat (`posseder`) ne pourra plus être supprimée librement.

---

## 4. Codes de retour

| Code | Signification | Conduite à tenir côté client |
|---|---|---|
| `200` | Succès | — |
| `201` | Compétence créée | — |
| `204` | Compétence supprimée | Retirer la ligne de la liste |
| `401` | Jeton absent ou invalide | Rediriger vers la connexion |
| `403` | Rôle insuffisant | Masquer les actions d'écriture hors administrateur |
| `404` | Compétence introuvable | Rafraîchir la liste |
| `422` | Validation | Afficher `errors` par champ |

---

## 5. Comptes de démonstration

Voir `docs/api/API_AUTHENTIFICATION.md` section 6. Seul `admin@airs.ma` peut
écrire dans le référentiel.

---

## 6. Collection Postman

Dossier **Compétences** de `postman/AIRS.postman_collection.json`.
