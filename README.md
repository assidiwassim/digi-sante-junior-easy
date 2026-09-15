# Digi-Santé Junior

Application web de suivi du **bien-être numérique des enfants de 8 à 14 ans**.

- L'**enfant** remplit chaque jour un petit journal en 2 étapes : son temps
  d'écran, puis les endroits où il a mal (sur un schéma du corps). Il reçoit
  aussitôt des conseils adaptés.
- Le **parent** crée les comptes de ses enfants, fixe une limite d'écran et suit
  l'évolution sur un tableau de bord avec graphique.
- L'**administrateur** gère la bibliothèque de contenus (fiches, vidéos, quiz…)
  et peut consulter ou supprimer les comptes parents.

Le code est volontairement simple, pensé pour un développeur qui débute avec
Symfony.

---

## Sommaire

1. [Technologies](#1-technologies)
2. [Prérequis](#2-prérequis)
3. [Installation rapide avec Docker (recommandée)](#3-installation-rapide-avec-docker-recommandée)
4. [Installation sans Docker](#4-installation-sans-docker)
5. [Configuration : `.env` et MySQL](#5-configuration--env-et-mysql)
6. [Comptes de démonstration](#6-comptes-de-démonstration)
7. [Commandes utiles](#7-commandes-utiles)
8. [Tests](#8-tests)
9. [Structure du projet](#9-structure-du-projet)
10. [Comment ça marche ?](#10-comment-ça-marche-)
11. [Problèmes fréquents](#11-problèmes-fréquents)

---

## 1. Technologies

| Rôle | Outil |
|---|---|
| Langage | PHP 8.4 |
| Framework | Symfony 7.4 (LTS) |
| Base de données | MySQL 8 |
| ORM | Doctrine (entités, migrations, fixtures) |
| Gabarits | Twig |
| Interface | Bootstrap 5.3 (CDN) + une feuille `public/css/app.css` |
| Graphiques | Chart.js (CDN) |
| Sécurité | Symfony Security (connexion, rôles, voter, CSRF) |
| Tests | PHPUnit |
| Environnement | Docker : FrankenPHP (PHP + serveur web), MySQL et phpMyAdmin |

Il n'y a **ni Node.js, ni npm, ni étape de compilation** : Bootstrap et
Chart.js sont chargés depuis un CDN.

---

## 2. Prérequis

**Avec Docker (recommandé)** : seulement [Docker Desktop](https://www.docker.com/products/docker-desktop/)
(qui fournit la commande `docker compose`). Rien d'autre à installer : PHP,
Composer et MySQL tournent dans les conteneurs.

**Sans Docker** : PHP 8.4 avec les extensions `pdo_mysql` et `intl`,
[Composer](https://getcomposer.org/), un serveur MySQL 8, et si possible la
[CLI Symfony](https://symfony.com/download).

---

## 3. Installation rapide avec Docker (recommandée)

Depuis le dossier du projet :

```bash
# 1. Construire et démarrer les conteneurs (PHP + MySQL + phpMyAdmin)
docker compose up -d --build

# 2. Installer les dépendances PHP
docker compose exec app composer install

# 3. Créer la base et ses tables
docker compose exec app php bin/console doctrine:database:create --if-not-exists
docker compose exec app php bin/console doctrine:migrations:migrate --no-interaction

# 4. Charger les données de démonstration
docker compose exec app php bin/console doctrine:fixtures:load --no-interaction
```

Ouvrez ensuite **<http://localhost:8081>**.

> Raccourci : `make install` enchaîne ces 4 étapes (tapez `make` pour voir
> toutes les commandes disponibles).

| Service | Adresse |
|---|---|
| Application | <http://localhost:8081> |
| phpMyAdmin (voir la base dans le navigateur) | <http://localhost:8082> — la connexion est déjà configurée, il n'y a rien à saisir |
| MySQL (depuis votre machine, ex. DBeaver) | hôte `127.0.0.1`, port `3308`, utilisateur `digisante`, mot de passe `digisante`, base `digisante_junior` |

Pour arrêter : `docker compose stop`. Les données MySQL sont conservées dans un
volume Docker ; `docker compose down -v` les efface définitivement.

---

## 4. Installation sans Docker

```bash
# 1. Dépendances
composer install

# 2. Configurer la connexion MySQL (voir section suivante)
#    -> créer un fichier .env.local avec votre DATABASE_URL

# 3. Base de données
php bin/console doctrine:database:create --if-not-exists
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console doctrine:fixtures:load --no-interaction

# 4. Lancer le serveur
symfony serve            # ou : php -S localhost:8000 -t public
```

---

## 5. Configuration : `.env` et MySQL

Symfony lit ses réglages dans des **variables d'environnement**, définies dans
des fichiers `.env` :

| Fichier | Rôle | Versionné ? |
|---|---|---|
| `.env` | valeurs par défaut du projet | oui |
| `.env.local` | **vos** réglages (mot de passe MySQL…) | non |
| `.env.test` | réglages de l'environnement de test | oui |

La variable importante est `DATABASE_URL` :

```dotenv
DATABASE_URL="mysql://UTILISATEUR:MOT_DE_PASSE@HÔTE:PORT/NOM_DE_LA_BASE?serverVersion=8.0.36&charset=utf8mb4"
```

- **Avec Docker**, rien à faire : `compose.yaml` fournit la bonne valeur au
  conteneur (`hôte = database`, `port = 3306`).
- **Sans Docker**, créez un fichier `.env.local` avec vos propres identifiants,
  par exemple :

  ```dotenv
  DATABASE_URL="mysql://root:monmotdepasse@127.0.0.1:3306/digisante_junior?serverVersion=8.0.36&charset=utf8mb4"
  ```

Ne mettez jamais de vrai mot de passe dans `.env` : ce fichier est partagé.

---

## 6. Comptes de démonstration

Chargés par `doctrine:fixtures:load`.

| Rôle | Page de connexion | Identifiant | Mot de passe |
|---|---|---|---|
| Administrateur | `/login` | `admin@digisante.local` | `admin123` |
| Parent (Léa, Tom) | `/login` | `parent@digisante.local` | `parent123` |
| Parent (Noah, Inès) | `/login` | `sofia@digisante.local` | `parent123` |
| Enfants | `/connexion-enfant` | `lea`, `tom`, `noah`, `ines` | `enfant123` |

Les enfants de démonstration ont **déjà rempli le journal du jour**. Pour tester
le formulaire en 2 étapes, supprimez le journal du jour :

```bash
docker compose exec app php bin/console dbal:run-sql "DELETE FROM journal_entree WHERE date = CURDATE()"
```

---

## 7. Commandes utiles

Avec Docker, préfixez chaque commande par `docker compose exec app`.

| Besoin | Commande |
|---|---|
| Vider le cache | `php bin/console cache:clear` |
| Lister les routes | `php bin/console debug:router` |
| Créer / modifier une entité | `php bin/console make:entity` |
| Générer une migration | `php bin/console make:migration` |
| Appliquer les migrations | `php bin/console doctrine:migrations:migrate` |
| Recharger les données de démo | `php bin/console doctrine:fixtures:load` |
| Vérifier le mapping Doctrine | `php bin/console doctrine:schema:validate` |
| Exécuter une requête SQL | `php bin/console dbal:run-sql "SELECT * FROM users"` |
| Vérifier les gabarits | `php bin/console lint:twig templates` |
| Vérifier la configuration | `php bin/console lint:yaml config` et `lint:container` |
| Ouvrir un terminal dans le conteneur | `docker compose exec app bash` |

En environnement de développement, la **barre de debug Symfony** s'affiche en
bas de chaque page : cliquez dessus pour voir les requêtes SQL, les formulaires,
l'utilisateur connecté…

---

## 8. Tests

Les tests utilisent une base séparée, `digisante_junior_test`, remplie avec les
données de démonstration. Chaque test s'exécute dans une transaction annulée à
la fin (bundle DAMA) : la base de test reste toujours identique.

Première fois (ou après une nouvelle migration) :

```bash
docker compose exec app php bin/console --env=test doctrine:database:create --if-not-exists
docker compose exec app php bin/console --env=test doctrine:migrations:migrate --no-interaction
docker compose exec app php bin/console --env=test doctrine:fixtures:load --no-interaction
```

Lancer les tests :

```bash
docker compose exec app php bin/phpunit
```

> Raccourci : `make tests` fait les deux.

| Fichier | Ce qui est testé |
|---|---|
| `tests/Controller/SecuriteTest.php` | pages publiques, connexion, inscription, accès par rôle |
| `tests/Controller/ParentEnfantTest.php` | création, validation, suppression d'un enfant ; un parent ne voit pas les enfants des autres |
| `tests/Controller/JournalTest.php` | journal en 2 étapes, vérification des données envoyées, conseils |
| `tests/Service/ConseilServiceTest.php` | les règles du moteur de conseils |
| `tests/Twig/DureeExtensionTest.php` | le filtre `duree` (« 2 h 30 ») |

---

## 9. Structure du projet

```text
.                            (racine du projet)
├── compose.yaml, Dockerfile, docker/   environnement Docker
├── config/                  configuration Symfony (security.yaml, twig.yaml…)
├── migrations/              historique des modifications de la base
├── public/
│   ├── index.php            point d'entrée de toutes les requêtes
│   ├── css/app.css          couleurs et composants propres au projet
│   └── js/graphique-ecran.js  graphique Chart.js partagé
├── src/
│   ├── Controller/          reçoit la requête, appelle Doctrine, affiche un gabarit
│   │   ├── Admin/           contenus, parents
│   │   ├── Enfant/          accueil, bibliothèque, profil, journal
│   │   └── Parent/          tableau de bord, enfants, profil
│   ├── DataFixtures/        données de démonstration
│   ├── Entity/              les tables de la base (User, Enfant, JournalEntree…)
│   ├── Form/                les formulaires
│   ├── Repository/          les requêtes Doctrine
│   ├── Security/            EnfantVoter : un parent ne gère que SES enfants
│   ├── Service/             ConseilService : le moteur de conseils
│   └── Twig/                le filtre « duree »
├── templates/
│   ├── base.html.twig       gabarit principal (navbar, messages, pied de page)
│   ├── _partials/           morceaux réutilisés (menu utilisateur, bouton supprimer)
│   ├── admin/ enfant/ parent/  un dossier par espace, chacun avec son layout.html.twig
│   ├── form/avatars.html.twig  affichage de la galerie d'avatars
│   ├── home/ security/      accueil, connexion, inscription
└── tests/                   tests PHPUnit
```

---

## 10. Comment ça marche ?

### Les données

```text
User (compte de connexion)
 ├── parent ──< Enfant >── compte (User de l'enfant)
 │                └──< JournalEntree (un par jour) ──< DouleurZone
 │
ContenuBienEtre (bibliothèque, indépendante)
```

- Un **parent** (`User` avec `ROLE_PARENT`) a plusieurs **enfants**.
- Chaque **enfant** a son propre **compte** `User` (`ROLE_CHILD`) pour se connecter.
- Un enfant a au plus **un journal par jour** (index unique en base).
- Supprimer un parent supprime ses enfants ; supprimer un enfant supprime son
  compte et ses journaux (cascades Doctrine).

### La connexion

- Deux pages : `/login` (parents, admin, par **email**) et `/connexion-enfant`
  (enfants, par **identifiant**). Les deux formulaires sont envoyés à `/login`,
  traité automatiquement par Symfony (`form_login` dans `config/packages/security.yaml`).
- `UserRepository::loadUserByIdentifier()` cherche l'utilisateur par email **ou** identifiant.
- Après connexion, la page d'accueil (`HomeController`) redirige chacun vers son espace.
- `access_control` protège les URL : `/admin` → `ROLE_ADMIN`, `/parent` →
  `ROLE_PARENT`, `/enfant` → `ROLE_CHILD`.

### Le journal en 2 étapes

1. **Étape 1** : les minutes d'écran sont gardées **en session**.
2. **Étape 2** : le schéma du corps (SVG + JavaScript) écrit les douleurs dans un
   champ caché, en JSON. Le contrôleur **revérifie** chaque zone et intensité,
   puis enregistre le journal complet.
3. **Conseils** : `ConseilService` applique 4 règles (plus de 2 h d'écran,
   limite dépassée, douleur au cou/épaules, douleur aux yeux). Le texte des
   exercices vient de la base : l'administrateur peut le modifier.

---

## 11. Problèmes fréquents

| Symptôme | Solution |
|---|---|
| Page blanche ou erreur `vendor/autoload.php` | `docker compose exec app composer install` |
| `Connection refused` / `Unknown database` | MySQL démarre encore : attendez quelques secondes, puis relancez `doctrine:database:create` |
| Le port 8081, 8082 ou 3308 est déjà utilisé | changez le port de gauche dans `compose.yaml` (ex. `'8090:80'`) |
| Les modifications CSS ne s'affichent pas | rechargez sans cache (Ctrl+Maj+R / Cmd+Maj+R) |
| Les tests échouent avec `Unknown database digisante_junior_test` | lancez les 3 commandes de préparation de la section [Tests](#8-tests) |
| Erreur sur les droits de `digisante_junior_test` | le volume MySQL a été créé avant `docker/mysql/init.sql` : `docker compose down -v` puis réinstallez |
