# Prompt Claude Code — Phase 01 : Environnement Docker et squelette Symfony

> Copiez tout ce qui suit dans Claude Code, à la racine de votre dossier de projet (vide).

---

## Contexte du projet

Je construis **Digi-Santé Junior**, une application web de suivi du bien-être
numérique des enfants de 8 à 14 ans. L'enfant déclarera chaque jour son temps
d'écran et ses éventuelles douleurs, recevra des conseils, et ses parents
suivront l'évolution. Trois rôles sans hiérarchie : administrateur, parent,
enfant.

Je débute avec Symfony : je veux du **code simple, lisible de haut en bas**, en
**français** (noms de classes métier, variables, commentaires, messages
utilisateur), sans sur-ingénierie. Les seuls mots anglais tolérés sont ceux
imposés par Symfony (`User`, `getRoles()`…).

La stack imposée : PHP 8.4, Symfony 7.4 (LTS), MySQL 8, Twig, Doctrine ORM 3,
Docker. **Pas de Node.js, pas de npm, pas de bundler** : les fichiers de
`public/` seront servis tels quels.

## Objectif de la phase

Mettre en place l'environnement Docker et le squelette Symfony, jusqu'à obtenir
une page qui répond dans le navigateur sur `http://localhost:8081`.

## Avant de coder

1. Liste le contenu du dossier courant : s'il n'est pas vide, dis-moi ce que tu
   y trouves avant de créer quoi que ce soit.
2. Vérifie que Docker est disponible (`docker compose version`).
3. Explique-moi en quelques lignes ce que tu vas créer et pourquoi, avant de le
   créer.

## À implémenter

### 1. Environnement Docker

- `Dockerfile` basé sur `dunglas/frankenphp:1-php8.4-bookworm` (PHP + serveur
  web dans un seul conteneur, qui sert déjà `public/`) :
  - installer `git` et `unzip` ;
  - installer les extensions PHP `pdo_mysql`, `intl`, `opcache`, `zip` ;
  - copier Composer depuis l'image officielle `composer/composer:2-bin` ;
  - copier un fichier `docker/php.ini` avec des réglages de développement
    (affichage des erreurs, `memory_limit`, fuseau `Europe/Paris`) ;
  - `SERVER_NAME=":80"` et `WORKDIR /app`.
- `compose.yaml` avec deux services :
  - `app` : construit depuis le `Dockerfile`, port `8081:80`, volume `./:/app`,
    variable `DATABASE_URL` pointant sur le service `database`, et dépendance
    `condition: service_healthy` ;
  - `database` : image `mysql:8.0.36`, port `3308:3306`, base `digisante_junior`,
    utilisateur et mot de passe `digisante`, mot de passe root `root`, variable
    `TZ: Europe/Paris`, volume nommé pour les données, `healthcheck` avec
    `mysqladmin ping`.
- Nom de projet compose `digisante-junior` et noms de conteneurs explicites.

### 2. Squelette Symfony

- Installer le squelette **Symfony 7.4** dans le conteneur (`symfony/skeleton`),
  avec Flex.
- Dans `composer.json`, régler `extra.symfony.docker` à `false` pour qu'un futur
  `composer require` n'écrase pas `compose.yaml`.
- Créer `src/Controller/HomeController.php` avec une route `/` nommée
  `app_home`, en attribut `#[Route]`, qui renvoie pour l'instant une réponse
  texte simple (« Digi-Santé Junior — bientôt disponible »).

### 3. Confort de travail

- Un `Makefile` avec au minimum : `help` (cible par défaut, qui liste les
  commandes), `install`, `start`, `stop`, `bash`, `cc`.
- Chaque cible doit afficher la commande complète qu'elle exécute, pour que je
  puisse la taper moi-même.
- Un `README.md` court : prérequis, installation en 2 commandes, adresse de
  l'application.
- Un `.gitignore` adapté (`/vendor/`, `/var/`, `.env.local`).

## Contraintes techniques et architecturales

- **Rien ne tourne sur ma machine** : toute commande PHP passe par
  `docker compose exec app …`.
- Routes en attributs `#[Route]`, jamais en YAML.
- Noms de routes préfixés : `app_…` pour le public.
- Commentaires utiles uniquement : expliquer **pourquoi**, pas répéter le code.
- Ne crée aucun fichier dont je n'ai pas besoin dans cette phase.

## Commandes attendues

```bash
docker compose up -d --build
docker compose exec app composer install
docker compose exec app php bin/console debug:router
docker compose ps
```

## Ce qui n'est PAS dans cette phase

- Pas de Twig ni de gabarit HTML (phase 02).
- Pas de Doctrine, pas d'entité, pas de migration (phase 03).
- Pas de sécurité ni de formulaire (phase 04).
- Pas de phpMyAdmin (il arrive en phase 03).

## Scénario de test manuel

1. Lancer `docker compose up -d --build`, puis `docker compose exec app composer install`.
2. Ouvrir `http://localhost:8081` dans le navigateur.
3. Vérifier que la réponse du contrôleur s'affiche (ni 404, ni 500).
4. Ouvrir `http://localhost:8081/page-qui-nexiste-pas`.
5. **Résultat attendu** : la page d'accueil répond, l'URL inconnue affiche la page d'erreur 404 de Symfony, et `docker compose ps` montre les deux conteneurs démarrés.

## Critères de validation

- [ ] `docker compose ps` : `app` et `database` démarrés, `database` *healthy*.
- [ ] `http://localhost:8081` répond en 200 avec le texte du contrôleur.
- [ ] `php bin/console debug:router` liste la route `app_home`.
- [ ] `make` (sans argument) affiche la liste des raccourcis.
- [ ] Le projet ne contient que les fichiers nécessaires à cette phase.

## Enfin

- N'écris **aucun test automatisé** dans cette phase : je valide au navigateur.
- Termine en me résumant en 5 lignes maximum ce qui a été créé et comment
  redémarrer l'environnement demain matin.
