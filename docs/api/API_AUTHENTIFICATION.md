# API d'authentification — AI Recruitment System

Documentation destinée à l'intégration du frontend Vue.js.

**URL de base :** `http://localhost:8000/api`
**Authentification :** jeton porteur (Bearer) — Laravel Sanctum
**Format :** JSON en entrée comme en sortie

---

## 1. Principe général

L'API est **sans état**. Après une inscription ou une connexion réussie, le
serveur retourne un jeton que le client doit conserver et transmettre dans
l'en-tête de chaque requête protégée :

```
Authorization: Bearer 1|AbCdEf123456...
Accept: application/json
Content-Type: application/json
```

L'en-tête `Accept: application/json` est **obligatoire** : sans lui, Laravel
peut répondre en HTML au lieu de JSON sur les erreurs.

---

## 2. Rôles

Conformément à **RG2**, chaque utilisateur possède un et un seul rôle.

| Valeur | Libellé | Espace réservé |
|---|---|---|
| `candidat` | Candidat | `/api/candidat/*` |
| `recruteur` | Recruteur | `/api/recruteur/*` |
| `administrateur` | Administrateur | `/api/admin/*` |

L'inscription publique ne permet de créer que des comptes `candidat` ou
`recruteur`. Un compte administrateur est créé par le seeder.

**État du compte :** `actif`, `suspendu`, `desactive`. Seul un compte `actif`
peut s'authentifier ; un compte suspendu reçoit un `403` même avec des
identifiants corrects.

---

## 3. Endpoints

### 3.1 Vérification du service

```
GET /api/health
```

Aucune authentification requise. Retourne :

```json
{
  "status": "ok",
  "service": "AI Recruitment System API",
  "time": "2026-08-28T20:15:00+00:00"
}
```

---

### 3.2 Inscription d'un candidat

```
POST /api/auth/inscription/candidat
```

**Corps de la requête**

| Champ | Type | Obligatoire | Contrainte |
|---|---|---|---|
| `nom` | string | oui | 100 caractères max |
| `prenom` | string | oui | 100 caractères max |
| `email` | string | oui | unique, 150 max (RG1) |
| `password` | string | oui | 8 caractères min, lettres + chiffres |
| `password_confirmation` | string | oui | doit correspondre à `password` |
| `telephone` | string | non | 20 max |
| `telephone2` | string | non | 20 max |
| `adresse` | string | non | 255 max |
| `date_naissance` | date | non | format `AAAA-MM-JJ`, âge minimum 16 ans |
| `diplome` | string | non | 150 max |
| `github` | url | non | 255 max |
| `linkedin` | url | non | 255 max |
| `experience_totale` | number | non | entre 0 et 60 |

**Exemple de requête**

```json
{
  "nom": "Alami",
  "prenom": "Youssef",
  "email": "youssef@example.ma",
  "password": "Password123",
  "password_confirmation": "Password123",
  "telephone": "0600000001",
  "adresse": "Quartier Iberia, Tanger",
  "date_naissance": "2001-04-12",
  "diplome": "Diplôme d'ingénieur en génie informatique",
  "github": "https://github.com/exemple",
  "linkedin": "https://linkedin.com/in/exemple",
  "experience_totale": 2.5
}
```

**Réponse `201`**

```json
{
  "message": "Compte candidat créé avec succès.",
  "utilisateur": {
    "id_user": 5,
    "nom": "Alami",
    "prenom": "Youssef",
    "nom_complet": "Youssef Alami",
    "email": "youssef@example.ma",
    "role": "candidat",
    "role_libelle": "Candidat",
    "etat_compte": "actif",
    "date_creation": "2026-08-28",
    "profil_candidat": {
      "id_candidat": 3,
      "telephone": "0600000001",
      "telephone2": null,
      "adresse": "Quartier Iberia, Tanger",
      "date_naissance": "2001-04-12",
      "diplome": "Diplôme d'ingénieur en génie informatique",
      "cv_pdf": null,
      "photo": null,
      "github": "https://github.com/exemple",
      "linkedin": "https://linkedin.com/in/exemple",
      "experience_totale": 2.5
    }
  },
  "token": "3|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
  "token_type": "Bearer"
}
```

Le jeton est retourné directement : l'utilisateur est connecté dès son
inscription, sans avoir à se reconnecter.

---

### 3.3 Inscription d'un recruteur

```
POST /api/auth/inscription/recruteur
```

Conformément à **RG7**, un recruteur appartient obligatoirement à une
entreprise. Deux cas sont acceptés.

**Champs communs**

| Champ | Type | Obligatoire | Contrainte |
|---|---|---|---|
| `nom` | string | oui | 100 max |
| `prenom` | string | oui | 100 max |
| `email` | string | oui | unique, 150 max |
| `password` | string | oui | 8 min, lettres + chiffres |
| `password_confirmation` | string | oui | doit correspondre |
| `telephone` | string | non | 20 max |
| `poste` | string | non | 100 max |

**Cas A — rattachement à une entreprise existante**

```json
{
  "nom": "Idrissi",
  "prenom": "Karim",
  "email": "karim@example.ma",
  "password": "Password123",
  "password_confirmation": "Password123",
  "poste": "Chargé de recrutement",
  "id_entreprise": 1
}
```

**Cas B — création simultanée de l'entreprise**

```json
{
  "nom": "Bennani",
  "prenom": "Salma",
  "email": "salma@example.ma",
  "password": "Password123",
  "password_confirmation": "Password123",
  "telephone": "0539000001",
  "poste": "Responsable des ressources humaines",
  "entreprise": {
    "nom": "TechnoMaroc",
    "secteur": "Technologies de l'information",
    "adresse": "12 avenue Mohammed V",
    "ville": "Tanger",
    "site_web": "https://technomaroc.example.ma",
    "description": "Société de services numériques basée à Tanger."
  }
}
```

L'un des deux (`id_entreprise` **ou** `entreprise`) est obligatoire. Fournir
ni l'un ni l'autre produit un `422`.

Dans le cas B, seul `entreprise.nom` est obligatoire ; les autres champs de
l'entreprise sont facultatifs.

**Réponse `201`**

```json
{
  "message": "Compte recruteur créé avec succès.",
  "utilisateur": {
    "id_user": 6,
    "nom": "Bennani",
    "prenom": "Salma",
    "nom_complet": "Salma Bennani",
    "email": "salma@example.ma",
    "role": "recruteur",
    "role_libelle": "Recruteur",
    "etat_compte": "actif",
    "date_creation": "2026-08-28",
    "profil_recruteur": {
      "id_recruteur": 2,
      "telephone": "0539000001",
      "poste": "Responsable des ressources humaines",
      "entreprise": {
        "id_entreprise": 1,
        "nom": "TechnoMaroc",
        "secteur": "Technologies de l'information",
        "adresse": "12 avenue Mohammed V",
        "ville": "Tanger",
        "site_web": "https://technomaroc.example.ma",
        "description": "Société de services numériques basée à Tanger."
      }
    }
  },
  "token": "4|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
  "token_type": "Bearer"
}
```

---

### 3.4 Connexion

```
POST /api/auth/connexion
```

```json
{
  "email": "candidat@airs.ma",
  "password": "Password123",
  "device_name": "navigateur-web"
}
```

`device_name` est facultatif : il nomme le jeton, ce qui permettra plus tard
à l'utilisateur d'identifier ses sessions ouvertes.

**Réponse `200`** — même structure que l'inscription : `message`,
`utilisateur` (avec le profil métier correspondant au rôle), `token`,
`token_type`.

**Codes de réponse**

| Code | Situation |
|---|---|
| `200` | Connexion réussie, jeton retourné |
| `401` | Identifiants incorrects |
| `403` | Compte suspendu ou désactivé |
| `422` | Champs manquants ou mal formés |
| `429` | Plus de 5 tentatives par minute |

> **Important :** le message du `401` est volontairement identique que
> l'adresse soit inconnue ou le mot de passe erroné, afin d'empêcher
> l'énumération des comptes existants. Ne pas afficher de distinction dans
> l'interface.

---

### 3.5 Utilisateur courant

```
GET /api/auth/moi
Authorization: Bearer {token}
```

**Réponse `200`**

```json
{
  "utilisateur": {
    "id_user": 3,
    "nom": "Alami",
    "prenom": "Youssef",
    "nom_complet": "Youssef Alami",
    "email": "candidat@airs.ma",
    "role": "candidat",
    "role_libelle": "Candidat",
    "etat_compte": "actif",
    "date_creation": "2026-08-28",
    "profil_candidat": { }
  }
}
```

À appeler au démarrage de l'application pour restaurer la session lorsqu'un
jeton est présent dans le stockage local.

---

### 3.6 Modification du profil (RG3)

```
PATCH /api/auth/profil
Authorization: Bearer {token}
```

Tous les champs sont facultatifs — seuls ceux transmis sont modifiés. Le
serveur répartit automatiquement les champs entre le compte utilisateur et le
profil métier.

**Champs communs à tous les rôles**

`nom`, `prenom`, `email`

**Champs supplémentaires pour un candidat**

`telephone`, `telephone2`, `adresse`, `date_naissance`, `diplome`, `github`,
`linkedin`, `experience_totale`

**Champs supplémentaires pour un recruteur**

`telephone`, `poste`

Un champ envoyé mais non autorisé pour le rôle courant est simplement ignoré.

**Exemple**

```json
{
  "prenom": "Youssef",
  "telephone": "0611223344",
  "experience_totale": 3.5
}
```

**Réponse `200`** — `message` et l'objet `utilisateur` mis à jour.

---

### 3.7 Changement de mot de passe

```
PATCH /api/auth/mot-de-passe
Authorization: Bearer {token}
```

```json
{
  "ancien_password": "Password123",
  "password": "NouveauPass456",
  "password_confirmation": "NouveauPass456"
}
```

Le nouveau mot de passe doit différer de l'ancien et respecter les mêmes
contraintes (8 caractères minimum, lettres et chiffres).

**Réponse `200`**

```json
{
  "message": "Mot de passe modifié. Les autres sessions ont été déconnectées."
}
```

**Tous les autres jetons de l'utilisateur sont révoqués** ; le jeton utilisé
pour cette requête reste valide. L'utilisateur n'a donc pas à se reconnecter,
mais ses sessions ouvertes ailleurs sont fermées.

---

### 3.8 Déconnexion

```
POST /api/auth/deconnexion
Authorization: Bearer {token}
```

Révoque uniquement le jeton courant.

```
POST /api/auth/deconnexion-globale
Authorization: Bearer {token}
```

Révoque l'ensemble des jetons de l'utilisateur, toutes sessions confondues.

**Réponse `200`** dans les deux cas. Côté frontend, supprimer le jeton du
stockage local.

---

### 3.9 Espaces réservés par rôle

```
GET /api/candidat/tableau-de-bord      (rôle candidat)
GET /api/recruteur/tableau-de-bord     (rôle recruteur)
GET /api/admin/tableau-de-bord         (rôle administrateur)
```

Un accès avec le mauvais rôle produit un `403`.

Ces routes servent actuellement de points d'ancrage : les fonctionnalités
métier viendront s'y greffer. Elles permettent dès à présent de vérifier que
le cloisonnement fonctionne.

---

## 4. Format des erreurs

### Erreur de validation — `422`

```json
{
  "message": "The email field is required.",
  "errors": {
    "email": ["Cette adresse e-mail est déjà utilisée."],
    "password": ["Le champ mot de passe doit contenir au moins 8 caractères."]
  }
}
```

Le tableau `errors` est indexé par nom de champ : il se branche directement
sur l'affichage des erreurs sous chaque champ de formulaire.

### Autres erreurs

```json
{ "message": "Non authentifié. Fournissez un jeton valide." }
```

```json
{ "message": "Accès refusé : cette ressource est réservée aux rôles suivants : recruteur." }
```

```json
{ "message": "Ce compte est Suspendu. Contactez un administrateur." }
```

### Récapitulatif

| Code | Signification | Conduite à tenir côté client |
|---|---|---|
| `200` | Requête traitée | — |
| `201` | Ressource créée | Enregistrer le jeton, rediriger |
| `401` | Jeton absent, invalide ou expiré | Supprimer le jeton, rediriger vers la connexion |
| `403` | Rôle insuffisant ou compte suspendu | Afficher un message, **ne pas** déconnecter |
| `422` | Validation échouée | Afficher les erreurs sous chaque champ |
| `429` | Trop de requêtes | Afficher un message d'attente |

---

## 5. Intégration Vue.js

Les extraits ci-dessous illustrent l'usage du contrat d'interface. Ils sont
donnés à titre indicatif : l'implémentation frontend reste libre.

### 5.1 Instance Axios

```js
// src/services/api.js
import axios from 'axios'

const api = axios.create({
  baseURL: 'http://localhost:8000/api',
  headers: { Accept: 'application/json' },
})

// Ajoute le jeton à chaque requête
api.interceptors.request.use((config) => {
  const token = localStorage.getItem('token')
  if (token) config.headers.Authorization = `Bearer ${token}`
  return config
})

// Déconnecte automatiquement sur 401
api.interceptors.response.use(
  (res) => res,
  (err) => {
    if (err.response?.status === 401) {
      localStorage.removeItem('token')
      window.location.href = '/connexion'
    }
    return Promise.reject(err)
  }
)

export default api
```

### 5.2 Store Pinia

```js
// src/stores/auth.js
import { defineStore } from 'pinia'
import api from '@/services/api'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    utilisateur: null,
    token: localStorage.getItem('token') || null,
  }),

  getters: {
    estConnecte:  (s) => !!s.token,
    estCandidat:  (s) => s.utilisateur?.role === 'candidat',
    estRecruteur: (s) => s.utilisateur?.role === 'recruteur',
    estAdmin:     (s) => s.utilisateur?.role === 'administrateur',
  },

  actions: {
    async connexion(identifiants) {
      const { data } = await api.post('/auth/connexion', identifiants)
      this._enregistrer(data)
      return data.utilisateur
    },

    async inscriptionCandidat(donnees) {
      const { data } = await api.post('/auth/inscription/candidat', donnees)
      this._enregistrer(data)
      return data.utilisateur
    },

    async inscriptionRecruteur(donnees) {
      const { data } = await api.post('/auth/inscription/recruteur', donnees)
      this._enregistrer(data)
      return data.utilisateur
    },

    async chargerUtilisateur() {
      if (!this.token) return null
      const { data } = await api.get('/auth/moi')
      this.utilisateur = data.utilisateur
      return data.utilisateur
    },

    async modifierProfil(champs) {
      const { data } = await api.patch('/auth/profil', champs)
      this.utilisateur = data.utilisateur
      return data.utilisateur
    },

    async deconnexion() {
      try {
        await api.post('/auth/deconnexion')
      } finally {
        this.token = null
        this.utilisateur = null
        localStorage.removeItem('token')
      }
    },

    _enregistrer(data) {
      this.token = data.token
      this.utilisateur = data.utilisateur
      localStorage.setItem('token', data.token)
    },
  },
})
```

### 5.3 Garde de navigation

```js
// src/router/index.js
router.beforeEach(async (to) => {
  const auth = useAuthStore()

  if (to.meta.public) return true
  if (!auth.estConnecte) return { name: 'connexion' }

  // Restaure l'utilisateur après un rechargement de page
  if (!auth.utilisateur) {
    try {
      await auth.chargerUtilisateur()
    } catch {
      return { name: 'connexion' }
    }
  }

  if (to.meta.role && auth.utilisateur.role !== to.meta.role) {
    return { name: 'acces-refuse' }
  }

  return true
})
```

Déclaration des routes :

```js
{ path: '/connexion', name: 'connexion', component: Connexion,
  meta: { public: true } },

{ path: '/candidat', component: EspaceCandidat,
  meta: { role: 'candidat' } },

{ path: '/recruteur', component: EspaceRecruteur,
  meta: { role: 'recruteur' } },
```

### 5.4 Affichage des erreurs de validation

```js
const erreurs = ref({})

async function soumettre() {
  erreurs.value = {}
  try {
    await auth.inscriptionCandidat(formulaire)
    router.push({ name: 'candidat' })
  } catch (e) {
    if (e.response?.status === 422) {
      erreurs.value = e.response.data.errors
    }
  }
}
```

Dans le gabarit :

```html
<input v-model="formulaire.email" type="email" />
<p v-if="erreurs.email" class="erreur">{{ erreurs.email[0] }}</p>
```

---

## 6. Comptes de démonstration

Créés par `php artisan db:seed`. Mot de passe commun : `Password123`

| Rôle | E-mail |
|---|---|
| Administrateur | `admin@airs.ma` |
| Recruteur | `recruteur@airs.ma` |
| Candidat | `candidat@airs.ma` |
| Candidat suspendu (test du `403`) | `suspendu@airs.ma` |

---

## 7. Limitation de débit

| Route | Limite |
|---|---|
| `POST /auth/connexion` | 5 requêtes / minute |
| `POST /auth/inscription/*` | 6 requêtes / minute |
| Autres routes | 60 requêtes / minute |

Le dépassement produit un `429` accompagné de l'en-tête `Retry-After`.
Pendant les phases de test intensif, cette limite peut être atteinte :
attendre une minute, ou demander au développeur backend de relever
temporairement la valeur.

---

## 8. Collection Postman

Deux fichiers sont fournis dans le dossier `postman/` du dépôt :

- `AIRS_Auth.postman_collection.json` — 22 requêtes réparties en 7 dossiers
- `AIRS_Local.postman_environment.json` — variables d'environnement

**Mise en route :** importer les deux fichiers, sélectionner l'environnement
*AIRS — Local*, puis exécuter une requête de connexion. Le jeton est
enregistré automatiquement dans la variable `token` ; toutes les requêtes
protégées l'utilisent ensuite sans manipulation.

La collection permet de vérifier le comportement de chaque point d'accès sans
attendre que l'interface soit développée.