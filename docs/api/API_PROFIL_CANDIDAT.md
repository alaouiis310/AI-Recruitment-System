# API Profil candidat — AI Recruitment System

Module 4 du backend. Couvre les règles de gestion **RG22** à **RG26**.

---

## 1. Principe général

Toutes les routes sont préfixées par `/api/candidat` et **réservées au rôle
candidat** :

```
Authorization: Bearer <token>
Accept: application/json
```

Ces points d'accès portent toujours sur le **profil du compte connecté**. Il
n'y a pas d'identifiant de candidat dans les URL et aucune politique de
propriété à appliquer : un candidat ne peut agir que sur ses propres données
(RG3). Un recruteur ou un administrateur reçoit `403`.

| Action | Candidat | Recruteur | Administrateur |
|---|---|---|---|
| Déposer / remplacer son CV | oui | non | non |
| Déposer / remplacer sa photo | oui | non | non |
| Déclarer ses compétences | oui | non | non |

---

## 2. CV — RG22, RG23

### 2.1 Dépôt ou remplacement

```
POST /api/candidat/cv
Content-Type: multipart/form-data
Authorization: Bearer <token>       candidat
```

| Champ | Type | Obligatoire | Contrainte |
|---|---|---|---|
| `cv` | file | oui | PDF uniquement, 10 Mo maximum |

**Requête en `multipart/form-data`**, pas en JSON : c'est un envoi de fichier.

```js
const donnees = new FormData()
donnees.append('cv', fichier)
await api.post('/candidat/cv', donnees)
```

**Réponse `200`**

```json
{
  "message": "CV enregistré.",
  "candidat": {
    "id_candidat": 1,
    "cv_pdf": "http://localhost:8000/storage/cv/9xKq...pdf",
    "photo": null,
    "experience_totale": 2.5,
    "...": "..."
  }
}
```

`cv_pdf` est renvoyé sous forme d'**URL complète**, directement utilisable
dans un lien ou une balise `<embed>`.

RG23 — le candidat peut remplacer son CV à tout moment. Le fichier précédent
est effacé du disque une fois le nouveau enregistré : aucun fichier orphelin
ne s'accumule.

| Code | Situation |
|---|---|
| `403` | Compte non candidat |
| `422` | Fichier absent, format autre que PDF, ou taille supérieure à 10 Mo |

> **Attention** — `upload_max_filesize` vaut 10 Mo et `post_max_size` 12 Mo
> dans le conteneur. Un fichier nettement plus gros est rejeté par PHP
> **avant** d'atteindre la validation : la réponse n'est alors pas le `422`
> attendu. Le contrôle de taille côté navigateur reste donc utile.

### 2.2 Suppression

```
DELETE /api/candidat/cv
Authorization: Bearer <token>       candidat
```

**Réponse `200`** — le fichier est supprimé du disque et la colonne remise à
`null`.

---

## 3. Photo de profil

```
POST   /api/candidat/photo      dépôt ou remplacement
DELETE /api/candidat/photo      suppression
```

| Champ | Type | Obligatoire | Contrainte |
|---|---|---|---|
| `photo` | file | oui | JPEG, PNG ou WebP, 2 Mo maximum |

Même comportement que le CV : envoi en `multipart/form-data`, remplacement
effaçant le fichier précédent, URL complète en réponse.

---

## 4. Compétences déclarées — RG24, RG25, RG26

Les compétences proviennent du **référentiel partagé** (module 2). Le candidat
n'en crée pas : il déclare celles qu'il possède, en précisant son niveau de
maîtrise et son ancienneté.

Alimentez la liste de sélection depuis `GET /api/competences`.

### 4.1 Consulter ses compétences

```
GET /api/candidat/competences
Authorization: Bearer <token>       candidat
```

**Réponse `200`**

```json
{
  "competences": [
    {
      "id_competence": 1,
      "nom": "PHP",
      "categorie": "langage",
      "niveau": "avance",
      "niveau_libelle": "Avancé",
      "annees_experience": 2.5
    }
  ]
}
```

Cette liste n'est pas paginée : le nombre de compétences déclarées par un
candidat reste petit par nature.

### 4.2 Remplacer la liste complète

```
PUT /api/candidat/competences
Authorization: Bearer <token>       candidat
```

| Champ | Type | Obligatoire | Contrainte |
|---|---|---|---|
| `competences` | array | **oui, même vide** | la liste voulue *au complet* |
| `competences[].id_competence` | integer | oui | doit exister au référentiel, sans doublon |
| `competences[].niveau` | string | oui | `debutant`, `intermediaire`, `avance`, `expert` |
| `competences[].annees_experience` | number | non | entre 0 et 60, défaut 0 |

**Exemple de requête**

```json
{
  "competences": [
    { "id_competence": 1, "niveau": "avance",        "annees_experience": 2.5 },
    { "id_competence": 5, "niveau": "intermediaire", "annees_experience": 1 }
  ]
}
```

C'est un **remplacement complet** : toute compétence absente de la liste est
retirée. Envoyer `{"competences": []}` efface toutes les déclarations.

Ce verbe convient à un formulaire « mes compétences » que l'on enregistre en
bloc.

### 4.3 Déclarer une seule compétence

```
POST /api/candidat/competences
Authorization: Bearer <token>       candidat
```

| Champ | Type | Obligatoire | Contrainte |
|---|---|---|---|
| `id_competence` | integer | oui | doit exister au référentiel |
| `niveau` | string | oui | voir l'énumération |
| `annees_experience` | number | non | entre 0 et 60 |

Ajoute la compétence **sans toucher aux autres**. Si elle est déjà déclarée,
son niveau et son ancienneté sont mis à jour.

Ce verbe convient à un ajout unitaire depuis une liste déroulante.

**Réponse `201`** — la liste complète et à jour est renvoyée :

```json
{
  "message": "Compétence déclarée.",
  "competences": [ { "...": "..." } ]
}
```

### 4.4 Retirer une compétence

```
DELETE /api/candidat/competences/{id_competence}
Authorization: Bearer <token>       candidat
```

**Réponse `204`** — corps vide. L'identifiant est celui de la **compétence**,
pas d'une ligne de pivot : le couple (candidat, compétence) est unique.

---

## 5. Niveaux de maîtrise (RG26)

| Valeur | Libellé |
|---|---|
| `debutant` | Débutant |
| `intermediaire` | Intermédiaire |
| `avance` | Avancé |
| `expert` | Expert |

L'ordre est significatif : le module 6 comparera ce niveau à celui exigé par
l'offre (`niveau_requis`, RG21) pour pénaliser les écarts dans le score de
compatibilité (RG40).

---

## 6. Format des erreurs

| Code | Signification | Conduite à tenir côté client |
|---|---|---|
| `200` | Requête traitée | — |
| `201` | Compétence déclarée | Rafraîchir la liste affichée |
| `204` | Compétence retirée | Retirer l'élément de la liste |
| `401` | Jeton absent, invalide ou expiré | Supprimer le jeton, rediriger vers la connexion |
| `403` | Compte non candidat | Afficher un message, **ne pas** déconnecter |
| `422` | Validation échouée | Afficher les erreurs sous chaque champ |

Les erreurs sur les listes sont indexées par position :

```json
{
  "message": "Une des compétences sélectionnées n'existe pas.",
  "errors": {
    "competences.0.id_competence": ["Une des compétences sélectionnées n'existe pas."],
    "competences.2.niveau": ["Chaque compétence exige un niveau de maîtrise."]
  }
}
```

Le formulaire doit donc rattacher chaque message à la ligne correspondante.

---

## 7. Données de démonstration

`php artisan db:seed` crée trois candidats aux profils contrastés, tous avec
le mot de passe `Password123` :

| Compte | Profil | Compétences dominantes |
|---|---|---|
| `candidat@airs.ma` | back-end, 2,5 ans | PHP, Laravel, MySQL, Git |
| `candidat2@airs.ma` | front-end, 4 ans | JavaScript, Vue.js, TypeScript |
| `candidat3@airs.ma` | data, 6 ans | Python, SQL, PostgreSQL |

Ce contraste est délibéré : il rendra les scores de compatibilité du module 6
nettement différenciés, et donc le classement de RG43 lisible.

Aucun CV n'est déposé par les seeders — les fichiers ne sont pas versionnés.
Déposez-en un via `POST /api/candidat/cv` avant de tester l'analyse du
module 6, qui suppose un CV exploitable (RG22, RG37).

> Avant de servir les fichiers déposés, exécuter une fois :
> `docker compose exec app php artisan storage:link`

---

## 8. Collection Postman

Les requêtes figurent dans le dossier **Profil candidat** de
`postman/AIRS.postman_collection.json`. Les envois de fichiers y sont en
`form-data` : sélectionnez un fichier local dans l'onglet *Body* avant de
lancer la requête, Postman ne pouvant pas le faire à votre place.
