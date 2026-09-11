# API Analyse IA — AI Recruitment System

Module 6 du backend. Couvre les règles de gestion **RG37** à **RG43**.

---

## 1. Le principe à retenir

> **Le score de compatibilité ne provient jamais du modèle de langage.**

C'est la contrainte de conception centrale du projet. Le score est calculé en
PHP par `ScoringService`, à partir des données de la base, selon un barème
écrit et testé. Il doit pouvoir être expliqué ligne à ligne à la soutenance,
et rester calculable lorsque l'API est indisponible.

L'analyse se déroule en **trois phases**, et leur ordre porte cette garantie :

| Phase | Rôle | Modèle de langage |
|---|---|---|
| 1. Extraction | Lit le CV PDF, en tire compétences, ancienneté, diplôme | **facultatif** |
| 2. Calcul | Produit les quatre scores et les compétences manquantes | **jamais** |
| 3. Rédaction | Met les scores calculés en phrases pour le recruteur | **facultatif** |

**Sans clé d'API**, les phases 1 et 3 sont sautées : l'analyse aboutit avec un
score complet, une recommandation et un résumé rédigé en PHP. L'application
reste pleinement fonctionnelle — c'est vérifié par la suite de tests, qui ne
configure aucune clé.

---

## 2. Le barème — RG40

Score global = **60 %** compétences + **25 %** expérience + **15 %** diplôme.

### Volet compétences (60 %)

Confronte les compétences déclarées par le candidat (pivot `posseder`, RG24)
à celles exigées par l'offre (pivot `requerir`, RG21).

Chaque compétence exigée vaut son **poids d'importance** :

| Importance | Poids |
|---|---|
| `essentielle` | 3 |
| `importante` | 2 |
| `souhaitee` | 1 |

Le candidat en obtient une fraction selon l'écart de niveau :

| Situation | Fraction obtenue |
|---|---|
| Niveau atteint ou dépassé | 100 % |
| 1 cran en dessous | 75 % |
| 2 crans en dessous | 50 % |
| 3 crans en dessous | 25 % |
| Compétence non déclarée | 0 % |

Score du volet = points obtenus ÷ points possibles.

*Exemple* — une offre exige PHP `avance` (essentielle, poids 3) et MySQL
`avance` (essentielle, poids 3). Le candidat déclare PHP `avance` et rien
d'autre : 3 points sur 6 possibles, soit **50**.

Une offre **sans compétence exigée** neutralise le volet à 100 : elle ne
permet pas de départager les candidats.

### Volet expérience (25 %)

Compare `experience_totale` du candidat à `experience_min` de l'offre.

- Proportionnel en deçà du seuil : 2 ans pour 4 exigés donne **50**.
- Plafonné à 100 : dépasser largement l'attente n'apporte pas de bonus.
- Offre sans exigence (`experience_min` à 0) : volet neutre à 100.

### Volet diplôme (15 %)

Le diplôme du candidat est une chaîne libre. On en déduit un nombre d'années
après le baccalauréat, en reconnaissant la notation `bac +N` puis les
libellés courants :

| Libellé reconnu | Années |
|---|---|
| doctorat, PhD | 8 |
| ingénieur, master, mastère | 5 |
| licence, bachelor | 3 |
| DUT, BTS, DEUG | 2 |
| baccalauréat | 0 |

Chaque année manquante retire **20 points**. Une licence (bac+3) face à une
exigence bac+5 donne donc **60**.

**Un diplôme non interprétable neutralise le volet à 100** — un choix
délibéré : la base ne permet pas de trancher ce cas, et le candidat ne doit
pas en être pénalisé.

### Bornes — RG39

Tous les scores sont compris entre 0 et 100, arrondis à deux décimales. La
contrainte `CHECK` est posée sur MySQL ; le calcul borne également les valeurs
de son côté, ce qu'un test vérifie.

---

## 3. La recommandation — RG42

Déduite du score global par des seuils explicites, jamais rédigée par le
modèle :

| Score global | Recommandation | Libellé |
|---|---|---|
| ≥ 70 | `retenir` | À retenir |
| ≥ 45 et < 70 | `a_examiner` | À examiner |
| < 45 | `rejeter` | À rejeter |

Ces seuils vivent dans l'énumération `Recommandation`, pas en base : ils
doivent pouvoir être justifiés et rejoués.

---

## 4. Quand l'analyse se déclenche — RG37

Le dépôt d'une candidature met un travail en file d'attente Redis. La requête
HTTP répond immédiatement en `201` : l'analyse lit un PDF et peut appeler une
API, elle est bien trop lente pour être faite dans la requête.

**Conséquence côté frontend :** `score_final` et l'analyse sont `null` juste
après le dépôt. Interrogez à nouveau quelques secondes plus tard, ou affichez
« analyse en cours ».

Le travail est réessayé trois fois (10 s puis 60 s d'attente). Une candidature
retirée entre-temps est ignorée sans erreur.

> Après toute modification du travail : `docker compose restart queue` — le
> worker charge le code au démarrage.

---

## 5. Représentation d'une analyse

```json
{
  "id_analyse": 1,

  "score_matching": 100.0,
  "score_competence": 100.0,
  "score_experience": 100.0,
  "score_diplome": 100.0,

  "competences_manquantes": [
    {
      "id_competence": 5,
      "nom": "MySQL",
      "niveau_requis": "avance",
      "niveau_actuel": "intermediaire",
      "importance": "essentielle"
    }
  ],

  "resume_cv": "Score de compatibilité de 100/100 avec l'offre « Développeur back-end PHP / Laravel »...",

  "recommandation": "retenir",
  "recommandation_libelle": "À retenir",

  "date_analyse": "2026-09-11",
  "id_candidature": 1
}
```

`competences_manquantes` (RG41) liste les compétences exigées que le candidat
ne couvre pas au niveau demandé. `niveau_actuel` vaut `null` lorsque la
compétence n'est pas déclarée du tout, et porte le niveau déclaré lorsqu'il
est simplement insuffisant. C'est de quoi afficher un écart de profil
directement exploitable par le recruteur.

---

## 6. Points d'accès

### 6.1 Consulter l'analyse

```
GET /api/recruteur/candidatures/{id_candidature}/analyse     recruteur
GET /api/candidat/candidatures/{id_candidature}/analyse      candidat
```

La propriété de la candidature gouverne celle de son analyse : c'est
`CandidaturePolicy` qui tranche, **RG14 s'applique donc sans règle nouvelle**.
Le recruteur consulte les analyses des candidatures reçues sur ses offres, le
candidat celle de sa propre candidature.

**Réponse `200`**

```json
{ "analyse": { "score_matching": 100.0, "...": "..." } }
```

**Réponse `404` — analyse pas encore produite**

```json
{
  "message": "Cette candidature n'a pas encore été analysée.",
  "analyse": null
}
```

Ce `404` est normal juste après un dépôt : le travail est encore en file.

### 6.2 Relancer l'analyse

```
POST /api/recruteur/candidatures/{id_candidature}/analyse    recruteur
```

**Réponse `202`**

```json
{ "message": "Analyse relancée. Le résultat sera disponible sous peu." }
```

Utile après une mise à jour du CV ou des compétences déclarées : le score
reflète l'état du profil **au moment du calcul**.

RG38 — une candidature n'a qu'une seule analyse : la relance **remplace** la
précédente, elle n'en ajoute pas.

---

## 7. Le classement — RG43

L'analyse renseigne `score_final` sur la candidature. C'est cette colonne
qu'utilise `GET /api/recruteur/candidatures`, classée par score décroissant,
les candidatures non encore analysées en fin de liste.

Le recruteur reçoit donc une liste ordonnée sans rien demander.

---

## 8. Configuration

`backend/.env` :

```
ANTHROPIC_API_KEY=sk-ant-...     # facultatif
IA_MODELE=claude-opus-5
IA_MAX_TOKENS=2048
IA_TIMEOUT=60
```

**La clé est facultative.** Sans elle, le module fonctionne : seules
l'extraction du CV et la rédaction du résumé par le modèle sont sautées. Le
score, la recommandation et les compétences manquantes sont produits
normalement.

Après modification : `docker compose exec app php artisan optimize:clear`.

---

## 9. Données de démonstration

`php artisan db:seed` analyse les six candidatures. Les seeders appellent le
service réel, les scores sont donc ceux que produira l'application :

| Candidat | Offre | Score |
|---|---|---|
| back-end | Développeur back-end PHP / Laravel | 100 |
| front-end | Développeur front-end Vue.js | 100 |
| data | Ingénieur data / intelligence artificielle | 100 |
| data | Administrateur systèmes et réseaux | 62,5 |
| back-end | Développeur front-end Vue.js | 55 |
| front-end | Administrateur systèmes et réseaux | 50 |

Les profils correspondant à leur offre atteignent 100, les profils éloignés
se situent entre 50 et 62,5 : le classement de RG43 est démontrable
immédiatement, sans clé d'API.

Aucun CV n'étant déposé par les seeders, la phase d'extraction ne s'exécute
pas — cela ne change rien au score, qui ne dépend que des données du profil.

---

## 10. Collection Postman

Les requêtes figurent dans le dossier **Analyse IA** de
`postman/AIRS.postman_collection.json`.
