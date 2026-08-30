
# AI Recruitment System — Système de Recrutement Intelligent

Plateforme de recrutement assistée par intelligence artificielle : publication d'offres d'emploi, dépôt de candidatures, analyse automatique des CV et classement des candidats par score de compatibilité.

Ce document décrit l'installation et l'exécution complète de l'application, incluant le backend (API Laravel) et le frontend (Vue.js), avec leur environnement Docker.

---

## 1. Architecture de l'environnement

L'environnement de développement est entièrement conteneurisé. Huit services coopèrent au sein d'un réseau Docker isolé (`airs_network`) :

| Service | Image / Base | Rôle | Port hôte |
|:---|:---|:---|:---:|
| **frontend** | Node.js 20-alpine | Application Vue.js (Vite) | **5173** |
| **nginx** | nginx:1.27-alpine | Serveur web, point d'entrée unique de l'API | **8000** |
| **app** | PHP 8.4-FPM | Exécution du code Laravel | — (interne) |
| **queue** | idem app | Worker asynchrone (analyses IA, e-mails) | — (interne) |
| **mysql** | mysql:8.0 | Base de données relationnelle (MLD) | **3307** |
| **redis** | redis:7-alpine | File d'attente des jobs + cache | — (interne) |
| **phpmyadmin** | phpmyadmin:5 | Administration visuelle de la base | **8081** |
| **mailpit** | axllent/mailpit | Interception des e-mails de notification | **8025** |

### Flux d'une requête

**Backend :** Le navigateur contacte Nginx sur le port 8000, Nginx transmet les fichiers `.php` au service `app` via FastCGI (port 9000 interne), Laravel interroge MySQL et Redis par leur nom de service. Aucun conteneur applicatif n'est exposé directement sur la machine hôte.

**Frontend :** Le navigateur contacte directement le service `frontend` sur le port 5173. Vite sert les fichiers en mode développement avec hot-reload.

**Traitement asynchrone :** Lorsqu'un candidat dépose une candidature, l'API répond immédiatement et publie un job dans Redis. Le conteneur `queue` le consomme, extrait le texte du CV, appelle le modèle d'IA et enregistre le résultat dans la table `analyses_ia`. Cette séparation évite qu'une requête HTTP reste bloquée pendant plusieurs secondes.

---

## 2. Prérequis

### Matériel

| Élément | Minimum | Recommandé |
|:---|:---:|:---:|
| RAM | 8 Go | 16 Go |
| Espace disque libre | 10 Go | 20 Go |
| Processeur | 4 cœurs | 4 cœurs ou plus |

### Logiciels

| Logiciel | Version minimale | Vérification |
|:---|:---|:---|
| Docker Engine | 24.0 | `docker --version` |
| Docker Compose | 2.20 (plugin V2) | `docker compose version` |
| Git | 2.30 | `git --version` |
| Make (optionnel) | 4.0 | `make --version` |

> **Windows :** Installer Docker Desktop avec le backend WSL 2 activé, et cloner le dépôt à l'intérieur du système de fichiers WSL (`/home/<user>/...`) et non dans `C:\Users\...` — les performances des volumes montés sont sinon très dégradées.

> **Aucune installation locale de PHP, Composer, MySQL ou Node.js n'est nécessaire :** tout est fourni par les conteneurs.

---

## 3. Installation

### Étape 1 — Cloner le dépôt

```bash
git clone https://github.com/<organisation>/ai-recruitment-system.git
cd ai-recruitment-system
```

### Étape 2 — Configurer les variables Docker

```bash
cp .env.example .env
```

Sous Linux ou macOS, aligner l'UID/GID sur votre utilisateur pour éviter les problèmes de permissions sur les fichiers générés :

```bash
sed -i "s/^UID=.*/UID=$(id -u)/"  .env
sed -i "s/^GID=.*/GID=$(id -g)/"  .env
```

### Étape 3 — Configurer Laravel

```bash
cp backend/.env.example backend/.env
```

Vérifier que les valeurs suivantes sont bien présentes dans `backend/.env` — les hôtes correspondent aux noms des services Docker, pas à `localhost` :

```env
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=ai_recruitment
DB_USERNAME=airs_user
DB_PASSWORD=secret

REDIS_HOST=redis
REDIS_PORT=6379
QUEUE_CONNECTION=redis
CACHE_STORE=redis

MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025

# Clé du fournisseur LLM utilisé pour l'analyse des CV
OPENROUTER_API_KEY=
```

### Étape 4 — Configurer le Frontend

```bash
# Copier les variables d'environnement du frontend
cp frontend/.env.example frontend/.env 2>/dev/null || echo "VITE_API_URL=http://localhost:8000" > frontend/.env
```

Le fichier `frontend/.env` doit contenir :

```env
VITE_API_URL=http://localhost:8000
```

### Étape 5 — Construire les images

```bash
docker compose build
```

La première construction télécharge les images de base et compile les extensions PHP : compter **5 à 15 minutes**. Les suivantes sont quasi instantanées grâce au cache de couches.

### Étape 6 — Démarrer les conteneurs

```bash
docker compose up -d
```

Vérifier que les huit services sont actifs :

```bash
docker compose ps
```

La colonne `STATUS` doit indiquer `Up` pour chacun, et `Up (healthy)` pour `mysql`.

### Étape 7 — Initialiser l'application Laravel

```bash
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan storage:link
docker compose exec app php artisan migrate --seed
```

> `storage:link` crée le lien symbolique rendant accessibles les CV et photos téléversés par les candidats.

### Étape 8 — Installer les dépendances Frontend

```bash
docker compose exec frontend npm install
```

---

## 4. Vérification de l'installation

| Vérification | URL / Commande | Résultat attendu |
|:---|:---|:---|
| **Frontend Vue.js** | http://localhost:5173 | Page d'accueil de l'application |
| **API opérationnelle** | http://localhost:8000/api/health | `{"status":"ok"}` |
| **Base de données** | http://localhost:8081 | Les 14 tables du MLD sont visibles |
| **Boîte mail de test** | http://localhost:8025 | Interface Mailpit vide |
| **Worker actif** | `docker compose logs queue` | `Processing jobs from the [default] queue` |

---

## 5. Utilisation quotidienne

### Avec `make` :

```bash
make up        # démarrer tous les services
make down      # arrêter tous les services
make logs      # suivre les logs de tous les services
make shell     # terminal dans le conteneur applicatif
make migrate   # exécuter les migrations
make fresh     # réinitialiser la base + seeders
make test      # lancer les tests
```

### Équivalents sans `make` :

```bash
# Démarrer
docker compose up -d

# Arrêter
docker compose down

# Voir les logs
docker compose logs -f

# Logs d'un service spécifique
docker compose logs -f frontend
docker compose logs -f app

# Terminal dans le conteneur app
docker compose exec app bash

# Terminal dans le conteneur frontend
docker compose exec frontend sh

# Exécuter des commandes Laravel
docker compose exec app php artisan migrate
docker compose exec app php artisan migrate:fresh --seed
docker compose exec app php artisan test

# Exécuter des commandes npm
docker compose exec frontend npm install
docker compose exec frontend npm run build
```

> **Toutes les commandes `php artisan`, `composer` et `php` doivent être exécutées à l'intérieur du conteneur `app`**, jamais sur la machine hôte.

> **Toutes les commandes `npm` doivent être exécutées à l'intérieur du conteneur `frontend`**.

---

## 6. Structure du dépôt

```
ai-recruitment-system/
├── backend/                    # API REST Laravel
│   ├── app/
│   │   ├── Http/Controllers/Api/
│   │   ├── Models/             # Un modèle par entité du MLD
│   │   ├── Policies/           # Règles d'accès (dont RG14)
│   │   ├── Jobs/               # AnalyseCandidatureJob
│   │   └── Services/           # ScoringService, CvExtractorService
│   ├── database/migrations/    # Traduction du MLD en SQL
│   ├── routes/api.php
│   └── .env                    # Configuration Laravel (non versionné)
│
├── frontend/                   # Application Vue 3
│   ├── src/
│   │   ├── components/
│   │   ├── views/
│   │   ├── router/
│   │   └── stores/
│   ├── Dockerfile              # Image Node.js pour le frontend
│   ├── package.json
│   ├── vite.config.js
│   ├── .env                    # Variables d'environnement (non versionné)
│   └── README.md               # Documentation frontend
│
├── docker/
│   ├── php/
│   │   ├── Dockerfile          # Image applicative PHP-FPM
│   │   └── php.ini             # Surcharges PHP
│   ├── nginx/default.conf      # Vhost Nginx
│   └── mysql/my.cnf            # Configuration MySQL (UTF-8)
│
├── docs/                       # MCD, MLD, dictionnaires, rapports
├── docker-compose.yml          # Orchestration des services
├── .env.example                # Variables Docker
├── .dockerignore
├── Makefile
└── README.md
```

---

## 7. Résolution des problèmes courants

### `Bind for 0.0.0.0:8000 failed: port is already allocated`

Un autre programme occupe le port. Modifier `APP_PORT` dans `.env`, puis :

```bash
docker compose up -d
```

### `SQLSTATE[HY000] [2002] Connection refused` lors des migrations

MySQL n'a pas fini de démarrer. Attendre que `docker compose ps` affiche `Up (healthy)` pour le service `mysql`, puis relancer la commande.

### `SQLSTATE[HY000] [1045] Access denied for user`

Les identifiants de `backend/.env` ne correspondent pas à ceux du `.env` racine. Après correction, réinitialiser le volume :

```bash
docker compose down -v && docker compose up -d
```

> ⚠️ Cette commande supprime toutes les données de la base.

### `Permission denied` sur `storage/` ou `bootstrap/cache/`

UID/GID mal configurés. Corriger le `.env` racine, puis :

```bash
docker compose exec app chown -R www-data:www-data storage bootstrap/cache
docker compose build --no-cache app && docker compose up -d
```

### Les modifications du code ne sont pas prises en compte

Vider les caches de configuration Laravel :

```bash
docker compose exec app php artisan optimize:clear
```

### Le worker ne traite pas les jobs

Le worker charge la configuration à son démarrage : après toute modification du code d'un job, le redémarrer avec :

```bash
docker compose restart queue
```

### Le frontend ne démarre pas / reste en `Restarting`

Voir les logs du frontend :

```bash
docker compose logs frontend
```

Erreur courante : version de Node.js incompatible. Vérifier que le Dockerfile utilise `node:20-alpine` ou `node:22-alpine`.

### Réinitialisation complète de l'environnement

```bash
docker compose down -v --rmi local
docker compose build --no-cache
docker compose up -d
```

---

## 8. Documentation liée

| Document | Emplacement |
|:---|:---|
| Modèle Conceptuel de Données (MCD) | `docs/mcd.png` |
| Modèle Logique de Données (MLD) | `docs/mld.png` |
| Règles de gestion et dictionnaires | `docs/regles_de_gestion.docx` |
| Rôle des fichiers de configuration | `docs/configuration.md` |
| Documentation du frontend | `frontend/README.md` |

---

## 9. Accès rapide

| Service | URL |
|:---|:---|
| **Frontend Vue.js** | http://localhost:5173 |
| **API Laravel** | http://localhost:8000 |
| **phpMyAdmin** | http://localhost:8081 |
| **Mailpit (Emails)** | http://localhost:8025 |

---

