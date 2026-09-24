# Les fichiers de configuration et leur rôle

*Section du rapport technique — mise en place de l'environnement de
développement — AI Recruitment System*

---

## 1. Vue d'ensemble

L'environnement de développement repose sur la conteneurisation. Plutôt que
d'installer PHP, MySQL, Redis et Nginx directement sur chaque poste de
travail, l'ensemble de la pile technique est décrit sous forme de fichiers
versionnés dans le dépôt Git.

Cette approche répond à trois besoins du projet :

- **Reproductibilité** — chaque membre de l'équipe travaille avec des versions
  strictement identiques, ce qui élimine la classe de problèmes dite
  « cela fonctionne sur ma machine ».
- **Rapidité d'intégration** — un nouvel arrivant obtient un environnement
  fonctionnel en deux commandes, sans installation manuelle.
- **Isolation** — le projet n'interfère avec aucun autre logiciel installé sur
  le poste, et sa suppression ne laisse aucune trace.

Sept fichiers assurent cette configuration. Ils se répartissent en trois
catégories : la définition de l'image applicative, l'orchestration des
services, et la configuration interne de chaque brique technique.

| Fichier | Catégorie | Rôle synthétique |
|---|---|---|
| `docker/php/Dockerfile` | Image | Construit l'image d'exécution PHP du projet |
| `docker-compose.yml` | Orchestration | Décrit et relie les sept services |
| `.env` / `.env.example` | Orchestration | Paramètres variables de l'orchestration |
| `.dockerignore` | Image | Restreint le contexte de construction |
| `docker/php/php.ini` | Configuration | Ajuste le comportement de PHP |
| `docker/nginx/default.conf` | Configuration | Définit l'hôte virtuel du serveur web |
| `docker/mysql/my.cnf` | Configuration | Fixe l'encodage de la base de données |
| `Makefile` | Confort | Abrège les commandes récurrentes |

---

## 2. `docker/php/Dockerfile`

Ce fichier est la **recette de construction** de l'image applicative. C'est le
seul service que nous ne pouvons pas utiliser tel quel depuis une image
publique, car l'application nécessite un ensemble précis d'extensions PHP.

Il procède en quatre temps :

**Image de base.** Nous partons de `php:8.4-fpm`, variante Debian. La variante
Alpine, plus légère, a été écartée : la compilation des extensions natives
(notamment `gd` et `redis`) y est nettement moins fiable, et le gain de taille
n'est pas un critère pertinent en développement.

**Dépendances système.** Les bibliothèques de développement (`libpng-dev`,
`libzip-dev`, `libicu-dev`, …) sont installées uniquement parce que les
extensions PHP en dépendent à la compilation. Le cache d'`apt` est supprimé
dans la même instruction `RUN`, afin qu'il ne soit pas figé dans une couche de
l'image.

**Extensions PHP.** Chaque extension répond à un besoin identifié du projet :

| Extension | Justification |
|---|---|
| `pdo_mysql` | Accès à la base de données depuis Eloquent |
| `gd` | Redimensionnement des photos de profil des candidats |
| `zip` | Dépendance de Composer |
| `intl` | Formatage des dates et nombres en français |
| `bcmath` | Calcul des scores de compatibilité en précision fixe |
| `pcntl` | Gestion des signaux système, requise par `queue:work` |
| `mbstring` | Traitement des chaînes accentuées des CV |
| `redis` | Communication avec la file d'attente |
| `opcache` | Mise en cache du bytecode PHP |

**Utilisateur non privilégié.** L'UID et le GID de l'utilisateur `www-data`
sont alignés dynamiquement, via des arguments de construction, sur ceux de
l'utilisateur de la machine hôte. Sans cet ajustement, les fichiers créés par
le conteneur sous Linux appartiendraient à `root` et deviendraient
inaccessibles en écriture depuis l'éditeur de code — difficulté rencontrée
lors de la mise en place et décrite en section 9 du rapport.

Composer est copié depuis son image officielle plutôt qu'installé par script,
ce qui garantit une version connue et raccourcit la construction.

---

## 3. `docker-compose.yml`

C'est le **fichier central de l'environnement**. Il déclare les sept services,
leurs dépendances mutuelles, les ports publiés, les volumes et le réseau. Une
seule commande, `docker compose up -d`, suffit ensuite à démarrer l'ensemble.

Plusieurs choix méritent d'être justifiés.

### 3.1 Exposition minimale des ports

Seuls quatre services publient un port sur la machine hôte : `nginx` (8000),
`phpmyadmin` (8081), `mailpit` (8025) et `mysql` (3307). Les services `app`,
`queue` et `redis` restent strictement internes au réseau Docker : ils ne sont
joignables que par les autres conteneurs. Cette configuration reproduit la
topologie d'un déploiement réel, où seul le serveur web est exposé.

Le port hôte de MySQL est volontairement fixé à **3307** et non 3306, afin
d'éviter tout conflit avec une instance MySQL déjà installée sur le poste des
membres de l'équipe.

### 3.2 Résolution par nom de service

Docker fournit un serveur DNS interne au réseau `airs_network`. Les
conteneurs se joignent donc par leur **nom de service** : la configuration
Laravel indique `DB_HOST=mysql` et `REDIS_HOST=redis`, jamais une adresse IP,
qui changerait à chaque redémarrage.

### 3.3 Contrôle de santé et ordre de démarrage

La directive `depends_on` seule ne garantit pas qu'un service soit
*opérationnel* : elle attend uniquement que le conteneur soit *démarré*. Or
MySQL met plusieurs secondes à initialiser ses fichiers avant d'accepter des
connexions, ce qui provoquait un échec systématique des premières migrations.

Un `healthcheck` fondé sur `mysqladmin ping` a donc été ajouté, couplé à une
condition `service_healthy`. Le service `app` n'est démarré qu'une fois la
base réellement disponible.

### 3.4 Un worker séparé pour les traitements asynchrones

Le service `queue` réutilise la même image que `app`, mais avec une commande
différente : `php artisan queue:work`. Cette duplication est intentionnelle.

L'analyse d'une candidature par intelligence artificielle — extraction du
texte du CV puis appel au modèle de langage — dure plusieurs secondes. La
placer dans le cycle de la requête HTTP dégraderait fortement l'expérience du
candidat au moment du dépôt de sa candidature. L'API se contente donc de
publier un job dans Redis et répond immédiatement ; le worker le traite en
arrière-plan et enregistre le résultat dans la table `analyses_ia`.

### 3.5 Persistance des données

Deux volumes nommés, `mysql_data` et `redis_data`, sont gérés par Docker en
dehors du système de fichiers du projet. Les données de la base survivent
ainsi à un `docker compose down`. Leur suppression n'intervient qu'avec
l'option explicite `-v`.

Le code source, lui, est monté en *bind mount* (`./backend:/var/www/html`) :
toute modification faite dans l'éditeur est immédiatement visible dans le
conteneur, sans reconstruction d'image.

---

## 4. `.env` et `.env.example`

Le fichier `.env` situé à la racine paramètre **l'orchestration Docker** : UID
de l'utilisateur, ports publiés, identifiants de la base. Il ne doit pas être
confondu avec `backend/.env`, qui configure **l'application Laravel**.

Seul `.env.example` est versionné. Le `.env` réel, qui contient les mots de
passe, est exclu par `.gitignore`. Cette séparation permet de documenter les
variables attendues sans exposer de secret dans l'historique Git.

Toutes les variables sont référencées dans `docker-compose.yml` avec une
valeur par défaut (`${APP_PORT:-8000}`), afin que l'environnement démarre même
si le fichier `.env` n'a pas encore été créé.

---

## 5. `.dockerignore`

Ce fichier exclut du **contexte de construction** les répertoires volumineux et
inutiles à la fabrication de l'image : `.git`, `node_modules`, `vendor`,
journaux. Sans lui, l'intégralité du dépôt — plusieurs centaines de mégaoctets
une fois les dépendances installées — serait transmise au démon Docker à
chaque construction, ce qui allongerait inutilement l'opération.

Il joue également un rôle de sécurité en empêchant le fichier `backend/.env`
d'être copié par inadvertance dans une couche de l'image.

---

## 6. `docker/php/php.ini`

Ce fichier surcharge la configuration PHP par défaut. Il est monté dans
`conf.d/99-app.ini`, le préfixe numérique garantissant qu'il est chargé en
dernier et que ses valeurs priment.

| Directive | Valeur | Motif |
|---|---|---|
| `upload_max_filesize` | 10M | La limite par défaut de 2 Mo rejetait les CV au format PDF |
| `post_max_size` | 12M | Doit rester supérieure à la précédente |
| `max_execution_time` | 120 | Marge pour les traitements longs exécutés en synchrone |
| `memory_limit` | 512M | Extraction et analyse de documents volumineux |
| `date.timezone` | Africa/Casablanca | Cohérence des dates de candidature et d'entretien |
| `opcache.validate_timestamps` | 1 | Prise en compte immédiate des modifications de code |

La dernière directive mérite une précision : en production, OPcache est
configuré pour ne jamais revalider les fichiers, ce qui maximise les
performances. En développement, ce réglage empêcherait toute modification
d'être visible sans redémarrage du conteneur.

---

## 7. `docker/nginx/default.conf`

Ce fichier définit l'hôte virtuel du serveur web. Trois éléments structurent
sa configuration.

**La racine est `public/`, et non le dossier du projet.** Cette restriction est
essentielle : elle rend inaccessibles depuis le navigateur le code source, les
fichiers de configuration et le fichier `.env`. C'est le modèle de sécurité
standard de Laravel.

**Toute URL non résolue est réécrite vers `index.php`.** La directive
`try_files $uri $uri/ /index.php?$query_string` permet au routeur de Laravel de
prendre en charge l'intégralité des routes de l'API.

**Les requêtes PHP sont déléguées au conteneur `app`.** La directive
`fastcgi_pass app:9000` s'appuie sur la résolution DNS interne de Docker. Le
`fastcgi_read_timeout` est relevé à 300 secondes pour absorber les traitements
d'analyse exécutés en mode synchrone lors des phases de test.

Une règle finale bloque l'accès à tous les fichiers cachés, protection
complémentaire contre l'exposition accidentelle de `.env` ou `.git`.

---

## 8. `docker/mysql/my.cnf`

Ce fichier force le jeu de caractères `utf8mb4` et la collation
`utf8mb4_unicode_ci` au niveau du serveur.

Le motif est directement lié au domaine du projet : les données manipulées
sont en français et comportent des caractères accentués (intitulés de postes,
lettres de motivation, noms de candidats). La configuration par défaut de
certaines images MySQL utilise `latin1`, ce qui corrompt ces caractères de
manière irréversible à l'insertion. Fixer l'encodage au niveau du serveur, et
non seulement au niveau de la connexion applicative, garantit un comportement
correct y compris pour les scripts d'import et les requêtes exécutées depuis
phpMyAdmin.

La journalisation des requêtes lentes y est également activée. Elle permettra,
en phase de recette, d'objectiver la pertinence des index définis lors du
passage du MCD au MLD.

---

## 9. `Makefile`

Ce fichier n'est pas nécessaire au fonctionnement de l'environnement : il
constitue une couche de confort. Il abrège les commandes récurrentes —
`make up` pour `docker compose up -d`, `make migrate` pour
`docker compose exec app php artisan migrate` — et fait office de
documentation exécutable des opérations courantes.

La cible `make help` liste automatiquement les commandes disponibles à partir
des commentaires du fichier.

---

## 10. Synthèse

L'ensemble de ces fichiers représente environ trois cents lignes de
configuration versionnées. Elles remplacent une procédure d'installation
manuelle qui aurait mobilisé chaque membre de l'équipe pendant plusieurs
heures, avec un risque élevé de divergence entre les postes.

Le bénéfice se mesure concrètement : l'installation complète du projet sur une
machine vierge se résume aujourd'hui à cloner le dépôt, copier deux fichiers
d'exemple et exécuter deux commandes Docker.