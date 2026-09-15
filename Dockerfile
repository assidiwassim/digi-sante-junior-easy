# Digi-Santé Junior — image de développement
#
# FrankenPHP = PHP 8.4 + serveur web (Caddy) dans un seul conteneur.
# La configuration par défaut de l'image sert déjà le dossier `public/`
# du répertoire de travail /app : aucun fichier de serveur web à écrire.

FROM dunglas/frankenphp:1-php8.4-bookworm

# Outils utilisés par Composer pour télécharger les dépendances
RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip \
    && rm -rf /var/lib/apt/lists/*

# Extensions PHP nécessaires au projet
#  - pdo_mysql : connexion à MySQL
#  - intl      : dates en français dans Twig (format_date)
RUN install-php-extensions pdo_mysql intl opcache zip

# Composer
COPY --from=composer/composer:2-bin /composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1

# Réglages PHP de développement
COPY docker/php.ini $PHP_INI_DIR/conf.d/zz-app.ini

# Le serveur écoute en HTTP simple sur le port 80 du conteneur
ENV SERVER_NAME=":80"

WORKDIR /app
