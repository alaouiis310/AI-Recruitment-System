# API Candidatures — AI Recruitment System

Module 5 du backend. Couvre les règles de gestion **RG27** à **RG33** et
**RG43**.

---

## 1. Principe général

```
Authorization: Bearer <token>
Accept: application/json
```

Deux surfaces, selon qui agit :

| Surface | Préfixe | Pour qui |
|---|---|---|
| Dépôt et suivi | `/api/candidat/candidatures` | candidats |
| Traitement | `/api/recruteur/candidatures` | recruteurs |

**RG14 — la règle centrale de ce module.** Un recruteur ne voit et ne traite
que les candidatures portant sur **les offres qu'il a lui-même publiées**. Ce
n'est pas un contrôle de rôle : il a le bon rôle, mais pas le droit sur cet
enregistrement. La restriction est appliquée par la requête SQL, jamais en
filtrant une liste déjà chargée.

| Action | Candidat | Recruteur | Administrateur |
|---|---|---|---|
| Postuler à une offre | oui | non | non |
| Voir ses propres candidatures | oui | — | oui |
| Voir les candidatures reçues | non | **sur ses offres uniquement** | oui |
| Changer le statut | non | **sur ses offres uniquement** | oui |
| Retirer une candidature | oui, tant qu'elle n'est pas tranchée | non | oui |

---

## 2. Cycle de vie d'une candidature — RG32

```
en_attente ─┬─> en_cours ──> preselectionnee ─┬─> acceptee
            │       │                │        └─> refusee
            │       └────────────────┴─> refusee
            ├─> preselectionnee
            └─> refusee
```

| Statut | Libellé | Transitions possibles |
|---|---|---|
| `en_attente` | En attente | `en_cours`, `preselectionnee`, `refusee` |
| `en_cours` | En cours d'examen | `preselectionnee`, `refusee` |
| `preselectionnee` | Présélectionnée | `acceptee`, `refusee` |
| `acceptee` | Acceptée | *aucune — définitif* |
| `refusee` | Refusée | *aucune — définitif* |

Une candidature acceptée ou refusée est **définitive** : le recruteur ne
revient pas sur sa décision. Toute transition hors de ce cycle renvoie `422`.

**Le frontend n'a pas à réimplémenter ce graphe** : chaque candidature
renvoyée expose `statuts_possibles`, la liste des transitions encore ouvertes
depuis son état courant. Il suffit d'en faire des boutons.

---

## 3. Représentation d'une candidature

```json
{
  "id_candidature": 1,
  "date_candidature": "2026-08-30",
  "lettre_motivation": "Madame, Monsieur, ...",

  "statut": "preselectionnee",
  "statut_libelle": "Présélectionnée",
  "statut_definitif": false,
  "statuts_possibles": [
    { "valeur": "acceptee", "libelle": "Acceptée" },
    { "valeur": "refusee",  "libelle": "Refusée" }
  ],

  "score_final": 82.5,

  "date_decision": null,
  "commentaire_recruteur": "Bon niveau technique, entretien à planifier.",

  "id_candidat": 1,
  "id_offre": 1,

  "offre": { "id_offre": 1, "titre": "Développeur back-end PHP / Laravel", "...": "..." },
  "candidat": { "id_candidat": 1, "...": "..." }
}
```

`score_final` vaut `null` tant que l'analyse du module 6 n'a pas abouti
(RG40). `date_decision` n'est renseignée qu'une fois le statut définitif.

Les clés `offre` et `candidat` ne sont présentes que lorsque la relation a été
chargée : `offre` sur les listes du candidat, `candidat` sur celles du
recruteur.

---

## 4. Espace candidat

### 4.1 Postuler à une offre — RG27, RG31, RG33

```
POST /api/candidat/candidatures
Authorization: Bearer <token>       candidat
```

| Champ | Type | Obligatoire | Contrainte |
|---|---|---|---|
| `id_offre` | integer | oui | l'offre doit exister **et accepter les candidatures** |
| `lettre_motivation` | string | non | 5000 caractères maximum |

**Exemple de requête**

```json
{
  "id_offre": 1,
  "lettre_motivation": "Madame, Monsieur, fort de deux années d'expérience..."
}
```

**Réponse `201`**

```json
{
  "message": "Candidature envoyée.",
  "candidature": {
    "id_candidature": 7,
    "date_candidature": "2026-09-11",
    "statut": "en_attente",
    "score_final": null,
    "...": "..."
  }
}
```

La date de dépôt est celle du jour (RG33) : elle n'est **jamais** fournie par
le client. Le statut initial est toujours `en_attente`.

**Deux refus possibles, tous deux en `422` :**

*Candidature déjà déposée (RG31)* — une seule candidature par candidat et par
offre :

```json
{
  "message": "Vous avez déjà postulé à cette offre.",
  "errors": { "id_offre": ["Une seule candidature est permise par offre."] }
}
```

*Offre qui n'accepte plus de candidatures (RG17, RG18)* — fermée, suspendue
ou expirée :

```json
{
  "message": "Cette offre n'accepte plus de candidatures.",
  "errors": { "id_offre": ["L'offre est Fermée."] }
}
```

Pour éviter le second cas, n'affichez le bouton « Postuler » que si
`accepte_candidatures` vaut `true` sur l'offre.

### 4.2 Suivre ses candidatures

```
GET /api/candidat/candidatures
Authorization: Bearer <token>       candidat
```

| Champ | Type | Obligatoire | Contrainte |
|---|---|---|---|
| `page` | integer | non | minimum 1 |
| `per_page` | integer | non | entre 1 et 100 |
| `statut` | string | non | un des cinq statuts de RG32 |

Triées de la plus récente à la plus ancienne. Chaque entrée porte son offre,
de quoi afficher une liste sans requête supplémentaire.

```
GET /api/candidat/candidatures/{id_candidature}
```

Consultation du détail, avec l'offre et ses compétences requises. Un candidat
consultant la candidature d'un autre reçoit `403`.

### 4.3 Retirer une candidature

```
DELETE /api/candidat/candidatures/{id_candidature}
Authorization: Bearer <token>       candidat
```

**Réponse `204`** — corps vide.

Le retrait n'est possible **que tant que la candidature n'est pas tranchée** :
une candidature acceptée ou refusée renvoie `403`. Une candidature retirée
libère le couple (candidat, offre) : le candidat peut postuler à nouveau.

---

## 5. Espace recruteur

### 5.1 Candidatures reçues — RG14, RG30, RG43

```
GET /api/recruteur/candidatures
Authorization: Bearer <token>       recruteur
```

| Champ | Type | Obligatoire | Contrainte |
|---|---|---|---|
| `page` | integer | non | minimum 1 |
| `per_page` | integer | non | entre 1 et 100 |
| `statut` | string | non | un des cinq statuts de RG32 |
| `id_offre` | integer | non | restreint à une de vos offres |

**RG43 — les candidatures sont classées par score décroissant.** Le recruteur
reçoit donc d'emblée une liste ordonnée, la plus prometteuse en tête.

Les candidatures **non encore analysées** (`score_final` à `null`) passent en
fin de liste : elles restent visibles, mais ne polluent pas le haut du
classement. À score égal, la plus récente d'abord.

Chaque entrée porte son candidat, de quoi afficher un tableau de suivi.

```
GET /api/recruteur/candidatures/{id_candidature}
```

Consultation du détail : le candidat, **ses compétences déclarées** (RG26) et
l'offre avec **ses compétences requises** (RG21) sont chargés ensemble — de
quoi comparer les deux côte à côte sans requête supplémentaire.

Une candidature portant sur l'offre d'un autre recruteur renvoie `403`, même
si son identifiant est valide (RG14).

### 5.2 Faire avancer une candidature — RG32

```
PATCH /api/recruteur/candidatures/{id_candidature}/statut
Authorization: Bearer <token>       recruteur ayant publié l'offre
```

| Champ | Type | Obligatoire | Contrainte |
|---|---|---|---|
| `statut` | string | oui | doit être une transition autorisée depuis l'état courant |
| `commentaire_recruteur` | string | non | 2000 caractères maximum |

**Exemple de requête**

```json
{
  "statut": "preselectionnee",
  "commentaire_recruteur": "Bon niveau technique, entretien à planifier."
}
```

**Réponse `200`**

```json
{
  "message": "Statut de la candidature mis à jour.",
  "candidature": {
    "statut": "preselectionnee",
    "statut_definitif": false,
    "statuts_possibles": [
      { "valeur": "acceptee", "libelle": "Acceptée" },
      { "valeur": "refusee",  "libelle": "Refusée" }
    ],
    "...": "..."
  }
}
```

Passer à `acceptee` ou `refusee` **date automatiquement la décision** dans
`date_decision`.

**Transition hors cycle — `422` :**

```json
{
  "message": "Une candidature « En attente » ne peut pas passer à « Acceptée ».",
  "errors": {
    "statut": ["Statuts possibles depuis l'état actuel : en_cours, preselectionnee, refusee."]
  }
}
```

Sur une candidature déjà tranchée, le message devient « Cette candidature a
reçu une décision définitive. »

---

## 6. Format des erreurs

| Code | Signification | Conduite à tenir côté client |
|---|---|---|
| `200` | Requête traitée | — |
| `201` | Candidature envoyée | Rediriger vers « mes candidatures » |
| `204` | Candidature retirée | Retirer l'élément de la liste |
| `401` | Jeton absent, invalide ou expiré | Supprimer le jeton, rediriger vers la connexion |
| `403` | Rôle insuffisant, candidature d'un autre, ou retrait d'un dossier tranché | Afficher un message, **ne pas** déconnecter |
| `404` | Candidature inexistante | Rafraîchir la liste |
| `422` | Doublon (RG31), offre fermée, ou transition hors cycle (RG32) | Afficher `message` |

Les trois cas de `422` sont distingués par leur `message` : affichez-le tel
quel, il est rédigé pour l'utilisateur final.

---

## 7. Données de démonstration

`php artisan db:seed` crée six candidatures couvrant les cinq statuts :

| Candidat | Offre | Statut |
|---|---|---|
| `candidat@airs.ma` | Développeur back-end PHP / Laravel | présélectionnée |
| `candidat@airs.ma` | Développeur front-end Vue.js | en cours |
| `candidat2@airs.ma` | Développeur front-end Vue.js | présélectionnée |
| `candidat2@airs.ma` | Administrateur systèmes et réseaux | refusée |
| `candidat3@airs.ma` | Ingénieur data / intelligence artificielle | acceptée |
| `candidat3@airs.ma` | Administrateur systèmes et réseaux | en attente |

Pour vérifier RG14 en deux requêtes : connectez-vous en `recruteur@airs.ma`
(TechnoMaroc) puis en `recruteur2@airs.ma` (Atlas Digital) et comparez
`GET /api/recruteur/candidatures` — chacun ne voit que les siennes.

`score_final` est nul partout : il est alimenté par le module 6.

---

## 8. Collection Postman

Les requêtes figurent dans les dossiers **Candidatures — candidat** et
**Candidatures — recruteur** de `postman/AIRS.postman_collection.json`. Les
listes alimentent la variable `id_candidature`.
