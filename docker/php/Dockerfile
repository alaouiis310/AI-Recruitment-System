### AI Recruitment System — image applicative (Laravel / PHP-FPM)
### Base Debian slim : compilation des extensions plus fiable qu'Alpine.
### PHP 8.4 : version requise par les dépendances de Laravel 12.
FROM php:8.4-fpm

# UID/GID passes en argument pour aligner l'utilisateur du conteneur
# sur celui de l'hote (evite les fichiers root sous Linux).
ARG UID=1000
ARG GID=1000

# ---------------------------------------------------------------------------
# 1. Dependances systeme necessaires a la compilation des extensions PHP
# ---------------------------------------------------------------------------
RUN apt-get update && apt-get install -y --no-install-recommends \
        git \
        curl \
        unzip \
        zip \
        libpng-dev \
        libjpeg62-turbo-dev \
        libfreetype6-dev \
        libonig-dev \
        libxml2-dev \
        libzip-dev \
        libicu-dev \
        default-mysql-client \
    && rm -rf /var/lib/apt/lists/*

# ---------------------------------------------------------------------------
# 2. Extensions PHP requises par Laravel et par le module d'analyse IA
#    - pdo_mysql : acces a la base de donnees
#    - gd        : traitement des photos de profil des candidats
#    - zip       : dependance de Composer
#    - intl      : formatage des dates/nombres en francais
#    - bcmath    : calculs de scores en precision fixe
#    - pcntl     : signaux systeme, requis par queue:work
# ---------------------------------------------------------------------------
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_mysql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        zip \
        intl \
        opcache

# Extension Redis (files d'attente + cache) via PECL
RUN pecl install redis && docker-php-ext-enable redis

# ---------------------------------------------------------------------------
# 3. Composer (copie depuis l'image officielle, pas d'installation manuelle)
# ---------------------------------------------------------------------------
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# ---------------------------------------------------------------------------
# 4. Utilisateur non-root
# ---------------------------------------------------------------------------
RUN groupmod -o -g ${GID} www-data \
    && usermod  -o -u ${UID} -g www-data www-data

# Répertoire de cache Composer accessible en écriture par www-data.
# Sans cela, Composer émet un avertissement et réinstalle les paquets
# depuis le réseau à chaque exécution.
ENV COMPOSER_HOME=/var/www/.composer
RUN mkdir -p /var/www/.composer \
    && chown -R www-data:www-data /var/www

WORKDIR /var/www/html

# Configuration PHP personnalisee (voir docker/php/php.ini)
COPY docker/php/php.ini /usr/local/etc/php/conf.d/99-app.ini

USER www-data

EXPOSE 9000
CMD ["php-fpm"]