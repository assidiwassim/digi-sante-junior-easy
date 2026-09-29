# Formation — Phase 01 : Environnement Docker, squelette Symfony et paquets du projet

## 1. Objectifs pédagogiques

À la fin de cette leçon, vous devez être capable de :

- expliquer ce qu'est un **framework** et ce que Symfony fait à votre place ;
- décrire le **cycle d'une requête** : du navigateur au contrôleur, puis retour ;
- comprendre le rôle de **Docker** ici, et pourquoi rien n'est installé sur votre
  machine ;
- comprendre ce que fait **Composer** et à quoi sert le dossier `vendor/` ;
- installer **tous les paquets** du projet et dire à quoi sert chacun, et dans
  quelle phase ;
- comprendre ce que fait une **recette Flex** et pourquoi on laisse ses fichiers
  tels quels ;
- ouvrir **phpMyAdmin** pour regarder la base de données ;
- écrire une **route** et un **contrôleur** qui répondent à une URL ;
- lancer une commande Symfony dans un conteneur et lire sa sortie.

## 2. Prérequis

- Docker Desktop installé et lancé.
- Savoir ouvrir un terminal et se déplacer dans les dossiers (`cd`, `ls`).
- Savoir lire une classe PHP (propriétés, méthodes, types de retour).

Aucune connaissance de Symfony n'est nécessaire : on commence ici.

---

## 3. Concepts à apprendre

### Concept 1 — Le framework

**Pourquoi ?** Sans framework, chaque projet PHP réécrit les mêmes choses :
analyser l'URL, décider quel code exécuter, gérer les sessions, produire une
réponse HTTP, gérer les erreurs. C'est long et c'est là que naissent les failles.

**Comment ça fonctionne ?** Symfony fournit cette plomberie. Vous écrivez
seulement le code qui distingue **votre** application : les URL, les pages, les
règles métier.

**Exemple.** En PHP « nu », répondre à `/bonjour` demande de lire
`$_SERVER['REQUEST_URI']`, de comparer des chaînes, d'appeler `header()`, etc.
Avec Symfony, vous écrivez :

```php
#[Route('/bonjour')]
public function bonjour(): Response
{
    return new Response('Bonjour !');
}
```

**Dans ce projet.** Symfony gère les URL, les formulaires, la sécurité, la
session, la base de données. Nous écrivons les pages, les entités et **une
seule** classe de service métier sur tout le projet.

---

### Concept 2 — Le conteneur Docker

**Pourquoi ?** Le projet a besoin de PHP 8.4, de MySQL 8 et d'extensions PHP
précises. Les installer sur votre machine crée des conflits avec vos autres
projets, et « ça marche chez moi » devient la règle.

**Comment ça fonctionne ?**

- une **image** est un modèle figé (« PHP 8.4 avec ces extensions ») ;
- un **conteneur** est une instance qui tourne à partir d'une image ;
- un **volume** conserve les données quand le conteneur est recréé ;
- un **port publié** relie un port de votre machine à un port du conteneur :
  `8081:80` veut dire « le port 8081 chez moi = le port 80 dans le conteneur ».

**Exemple.**

```yaml
services:
  app:
    build: .
    ports:
      - '8081:80'
    volumes:
      - ./:/app
```

Le volume `./:/app` monte votre dossier de projet **dans** le conteneur : vous
éditez un fichier chez vous, il change immédiatement dans le conteneur.

**Dans ce projet.** Trois services, tous déclarés dès cette phase : `app`
(FrankenPHP = PHP + serveur web), `database` (MySQL) et `phpmyadmin` (une
interface web pour regarder la base, concept 7). Conséquence importante :
**toute commande PHP passe par le conteneur**.

```bash
docker compose exec app php bin/console
#              ^^^ service  ^^^ commande exécutée à l'intérieur
```

---

### Concept 3 — Composer et l'autoload

**Pourquoi ?** Une application utilise des dizaines de bibliothèques. Il faut les
télécharger, gérer leurs versions et leurs dépendances, et pouvoir charger
n'importe quelle classe sans `require` manuel.

**Comment ça fonctionne ?** `composer.json` liste ce dont vous avez besoin,
`composer install` télécharge tout dans `vendor/` et génère un **autoloader** :
un fichier qui sait trouver une classe à partir de son nom.

La règle **PSR-4** relie un préfixe de namespace à un dossier :

```text
App\Controller\HomeController   →   src/Controller/HomeController.php
```

C'est pour cela que les noms de dossiers et de fichiers doivent correspondre
exactement aux namespaces et aux noms de classes.

**Dans ce projet.** `composer.json` déclare le namespace `App\` pour `src/`. On
ne modifie jamais `vendor/` à la main : il est régénéré par Composer et exclu de
Git.

---

### Concept 4 — Le point d'entrée unique et le `Kernel`

**Pourquoi ?** Un seul point d'entrée permet d'appliquer partout la même
configuration, la même sécurité et la même gestion d'erreurs.

**Comment ça fonctionne ?** **Toutes** les URL arrivent sur
`public/index.php`. Ce fichier crée le `Kernel` (le cœur de l'application), qui
transforme une **requête** HTTP en **réponse** HTTP.

```text
GET /bonjour  →  public/index.php  →  Kernel  →  routage  →  contrôleur  →  Response
```

**Dans ce projet.** `public/` est le seul dossier exposé par le serveur web.
Tout le reste (votre code, la configuration, `vendor/`) est **hors d'atteinte**
depuis le navigateur. C'est une protection, pas un détail d'organisation.

---

### Concept 5 — Route et contrôleur

**Pourquoi ?** Il faut relier une URL à du code.

**Comment ça fonctionne ?** Une **route** associe un chemin (`/bonjour`) à une
méthode de contrôleur. Un **contrôleur** est une classe dont les méthodes
renvoient une `Response`. La route se déclare dans un **attribut PHP**, juste
au-dessus de la méthode.

**Exemple commenté.**

```php
namespace App\Controller;                    // must match the src/Controller folder

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController
{
    #[Route('/', name: 'app_home', methods: ['GET'])]
    //       │          │                   └── accepted HTTP verbs
    //       │          └── route name, used to generate links
    //       └── URL path
    public function index(): Response
    {
        return new Response('Digi-Santé Junior — bientôt disponible');
    }
}
```

**Dans ce projet.** Toutes les routes sont en attributs (jamais en YAML) et leur
nom est **préfixé par l'espace** : `app_` pour le public, puis `parent_`,
`child_`, `admin_` dans les phases suivantes.

---

### Concept 6 — Les variables d'environnement

**Pourquoi ?** L'adresse de la base de données n'est pas la même chez vous, chez
un collègue et en production. Et un mot de passe n'a rien à faire dans le code.

**Comment ça fonctionne ?** Les réglages vivent dans des variables
d'environnement, lues depuis des fichiers `.env` :

| Fichier | Rôle | Versionné ? |
|---|---|---|
| `.env` | valeurs par défaut du projet | oui |
| `.env.local` | **vos** réglages personnels | non |

**Dans ce projet.** Avec Docker, `compose.yaml` fournit directement la bonne
`DATABASE_URL` au conteneur : vous n'avez rien à régler pour démarrer. Son
contenu sera expliqué en phase 02.

---

### Concept 7 — phpMyAdmin, pour regarder la base

**Pourquoi ?** Doctrine (phase 02) va créer des tables, des clés étrangères, des
index, sans que vous écriviez de SQL. Pour **comprendre** ce qu'il fabrique, il
faut pouvoir le **voir**. Regarder une table après une migration est le meilleur
moyen de comprendre le mapping.

**Comment ça fonctionne ?** phpMyAdmin est une application web qui se connecte à
MySQL et affiche les bases, les tables, leur structure et leurs lignes. On
l'ajoute comme **troisième service** Docker, avec une image officielle : rien à
installer.

```yaml
  # Development tool only: never in production.
  phpmyadmin:
    image: phpmyadmin:5.2
    depends_on:
      database:
        condition: service_healthy   # waits until MySQL is ready
    environment:
      PMA_HOST: database             # name of the MySQL SERVICE, in the Docker network
      PMA_PORT: 3306                 # MySQL's internal port (not 3308)
      PMA_USER: digisante            # identifiants fournis :
      PMA_PASSWORD: digisante        # automatic login, no login screen
      TZ: Europe/Paris
    ports:
      - '8082:80'
```

**Dans ce projet.** `http://localhost:8082` s'ouvre **directement** sur la base
`digisante_junior`, sans mot de passe à saisir. Pour l'instant elle est **vide** :
les tables arrivent en phase 02.

⚠️ Deux règles :

1. phpMyAdmin sert à **regarder**, jamais à modifier le schéma : toute
   modification passe par une migration (phase 02) ;
2. c'est un outil de **développement** : il ne doit jamais être exposé en
   production (la connexion automatique donnerait la base à n'importe qui).

---

### Concept 8 — Les paquets du projet et les recettes Flex

**Pourquoi ?** Le squelette Symfony est volontairement minuscule : il sait
répondre à une URL, rien de plus. Twig, Doctrine, la sécurité, les formulaires…
sont des **paquets** à ajouter. On les installe **tous maintenant**, en deux
commandes, pour que les phases suivantes se concentrent sur le code et non sur
l'outillage.

**Comment ça fonctionne ?** `composer require` télécharge le paquet et l'ajoute
à `composer.json`. Pour un paquet Symfony, **Flex** exécute en plus sa
**recette** : il enregistre le bundle dans `config/bundles.php` et crée des
fichiers de configuration par défaut.

| Paquet | À quoi il sert | Utilisé en |
|---|---|---|
| `twig` (alias Flex de `symfony/twig-pack`) | les gabarits HTML | phase 03 |
| `symfony/asset` | la fonction `asset()` pour les fichiers de `public/` | phase 03 |
| `symfony/orm-pack` | Doctrine : ORM, DBAL, migrations | phase 02 |
| `symfony/security-bundle` | les interfaces de `User` (phase 02), puis connexion, rôles, hachage | phases 02 et 04 |
| `symfony/form` | les formulaires (`*Type`) | phase 04 |
| `symfony/validator` | les contraintes `#[Assert\…]` | phases 02 (déclarées) et 04 (appliquées) |
| `symfony/translation` | les messages de Symfony en français | phase 04 |
| `twig/extra-bundle` + `twig/intl-extra` | le filtre `format_date` (date en français) | phase 06 |
| `symfony/maker-bundle` (`--dev`) | les générateurs `make:entity`, `make:migration`, `make:voter`… | dès la phase 02 |
| `symfony/profiler-pack` + `symfony/debug-bundle` (`--dev`) | barre de debug, profiler, `dump()` | dès la phase 02, visible en phase 03 |
| `doctrine/doctrine-fixtures-bundle` (`--dev`) | les données de démonstration | phase 12 |

`--dev` : outil de **développement**, inutile en production, rangé dans la
section `require-dev` de `composer.json`.

**Les fichiers créés par les recettes** : `config/packages/doctrine.yaml`,
`security.yaml`, `csrf.yaml`, `translation.yaml`, `twig.yaml`,
`templates/base.html.twig`, les dossiers `migrations/` et `translations/`,
`src/DataFixtures/AppFixtures.php` (vide, rempli en phase 12), une
`DATABASE_URL` dans `.env`… **On les laisse tels quels** : chaque phase configure ce dont elle a
besoin, au moment où elle en a besoin (Doctrine en phase 02, Twig en phase 03,
sécurité, CSRF et traduction en phase 04).

⚠️ **Avant** ces installations, réglez `"docker": false` dans la section
`extra.symfony` de `composer.json`. Sans cela, certaines recettes (Doctrine
notamment) **ajoutent leur propre service** à `compose.yaml` et écrasent votre
configuration.

```json
"extra": {
    "symfony": {
        "allow-contrib": false,
        "require": "7.4.*",
        "docker": false
    }
}
```

---

## 4. Explications avec exemples

### Le `Dockerfile`, ligne par ligne

```dockerfile
FROM dunglas/frankenphp:1-php8.4-bookworm
# Base image: PHP 8.4 + a web server (Caddy) already configured
# to serve the public/ folder. No Apache/Nginx configuration file.

RUN install-php-extensions pdo_mysql intl opcache zip
# pdo_mysql: talk to MySQL
# intl     : display dates in French
# opcache  : keep compiled PHP in memory (performance)

COPY --from=composer/composer:2-bin /composer /usr/bin/composer
# Composer is copied from its official image: no manual installation

COPY docker/php.ini $PHP_INI_DIR/conf.d/app.ini
# Development PHP settings (Europe/Paris time zone, memory…)

ENV SERVER_NAME=":80"
# Without this line, FrankenPHP serves HTTPS: port 8081:80 would not answer

WORKDIR /app
# Default working folder in the container
```

### Le fichier `compose.yaml`, service par service

```text
app          FrankenPHP, port 8081:80, dossier du projet monté sur /app,
             DATABASE_URL fournie, démarre quand database est « healthy »
database     MySQL 8, port 3308:3306, volume db_data pour garder les données,
             TZ: Europe/Paris, healthcheck (mysqladmin ping)
phpmyadmin   phpMyAdmin 5.2, port 8082:80, connexion automatique (concept 7)
```

On écrit un `healthcheck` pour `database` : c'est le service dont les autres
doivent attendre qu'il soit **prêt** (MySQL met quelques secondes à démarrer),
d'où les `depends_on: … condition: service_healthy`. (L'image FrankenPHP de
`app` embarque aussi son propre healthcheck : `app` s'affiche donc « healthy »
lui aussi.)

### Un contrôleur minimal, et ce qui se passe quand vous ouvrez l'URL

```text
1. Le navigateur demande  GET http://localhost:8081/
2. Docker transmet au conteneur app, sur son port 80
3. FrankenPHP exécute public/index.php
4. Le Kernel démarre et lit la configuration
5. Le routeur compare « / » aux routes connues → trouve app_home
6. Symfony appelle HomeController::index()
7. La méthode renvoie une Response
8. Le Kernel renvoie cette réponse au navigateur
```

Si l'étape 5 échoue, vous obtenez une **404**. Si l'étape 6 lève une exception,
vous obtenez une **500** avec, en développement, une page d'erreur détaillée.

---

## 5. Commandes

### `docker compose up -d --build`

- **Ce qu'elle fait** : construit les images puis démarre les conteneurs en
  arrière-plan (`-d`).
- **Pourquoi** : c'est ce qui allume votre environnement de travail.
- **Quand** : la première fois, et après chaque modification du `Dockerfile` ou
  de `compose.yaml`.
- **À observer** : la fin de la sortie doit indiquer les conteneurs `Started`.

### `docker compose ps`

- **Ce qu'elle fait** : liste les conteneurs, leur état et leurs ports.
- **À observer** : `Up (healthy)` pour `database` (et pour `app`, dont l'image
  FrankenPHP a son propre healthcheck), `Up` pour `phpmyadmin`. Un conteneur qui redémarre en boucle signale une erreur de configuration.

### Installer le squelette Symfony

```bash
docker compose exec app sh -c 'composer create-project symfony/skeleton:"7.4.*" /tmp/skeleton && cp -rn /tmp/skeleton/. /app/ && rm -rf /tmp/skeleton'
```

- **Pourquoi ce détour** : `composer create-project` refuse un dossier non vide,
  or le vôtre contient déjà `Dockerfile` et `compose.yaml`. On installe donc
  dans `/tmp/skeleton`, puis on copie sans écraser (`cp -rn`).
- **À observer** : `bin/console`, `config/`, `public/index.php` et `src/Kernel.php`
  apparaissent. Le squelette contient déjà `console`, `dotenv`, `flex`,
  `runtime`, `framework-bundle` et `yaml` : rien d'autre à installer.

### `docker compose exec app composer install`

- **Ce qu'elle fait** : installe les dépendances PHP **dans le conteneur**.
- **À observer** : la création du dossier `vendor/`. Sans lui, toutes les pages
  échouent sur « `vendor/autoload.php` introuvable ».

### Installer tous les paquets du projet

```bash
docker compose exec app composer require twig symfony/asset symfony/orm-pack symfony/security-bundle symfony/form symfony/validator symfony/translation twig/extra-bundle twig/intl-extra
docker compose exec app composer require --dev symfony/maker-bundle symfony/profiler-pack symfony/debug-bundle doctrine/doctrine-fixtures-bundle
```

- **Ce qu'elles font** : installent les paquets de l'application, puis les
  outils de développement (concept 8).
- **Quand** : une seule fois, juste après le squelette, et **après** avoir réglé
  `extra.symfony.docker: false`.
- **À observer** : des lignes « Configuring symfony/… » (les recettes Flex), puis
  de nouveaux fichiers dans `config/packages/`. Ne les modifiez pas.
- **Vérification** : `git diff compose.yaml` ne doit rien montrer.
- **La barre de debug**, bien qu'installée, **n'apparaît pas** sur la page
  d'accueil. C'est normal : elle ne s'injecte que dans une page qui contient
  `</body>`, or `HomeController` renvoie une simple phrase. Elle apparaîtra en
  phase 03 avec le premier gabarit.

### `docker compose exec app composer show`

- **Ce qu'elle fait** : liste les paquets installés et leur version.
- **Quand** : « ce paquet est-il installé ? ». Ajoutez un nom pour filtrer :
  `composer show symfony/form`.
- **À observer** : les paquets de la section précédente. Flex **dépaquète**
  les « packs » : au lieu de `symfony/twig-pack`, `symfony/orm-pack` et
  `symfony/profiler-pack`, vous verrez leurs composants (`symfony/twig-bundle`,
  `twig/twig`, `doctrine/orm`, `doctrine/doctrine-bundle`,
  `doctrine/doctrine-migrations-bundle`, `symfony/web-profiler-bundle`,
  `symfony/stopwatch`). C'est aussi ce qu'on lit dans `composer.json`.

### `docker compose exec app php bin/console`

- **Ce qu'elle fait** : liste toutes les commandes Symfony disponibles.
- **Quand** : dès que vous cherchez « comment faire X » en ligne de commande.

### `docker compose exec app php bin/console debug:router`

- **Ce qu'elle fait** : affiche **toutes** les routes, leur nom, leurs méthodes
  HTTP et leur chemin.
- **À observer** : votre route `app_home` doit y figurer. C'est le premier
  réflexe quand une URL renvoie 404.

### `docker compose logs -f app`

- **Ce qu'elle fait** : affiche en direct les journaux du conteneur PHP.
- **Quand** : page blanche, erreur 500 inexpliquée.

---

## 6. Concepts Symfony de la phase

| Composant | Rôle ici |
|---|---|
| **HttpKernel** | transforme une requête HTTP en réponse |
| **Routing** | associe une URL à une méthode de contrôleur (`#[Route]`) |
| **HttpFoundation** | les objets `Request` et `Response` |
| **Console** | les commandes `bin/console` |
| **Dotenv** | lit les fichiers `.env` |
| **Flex** | installe les paquets Symfony et exécute leurs recettes (fichiers de config) |
| **Composer** | télécharge les paquets, génère l'autoload |

---

## 7. Architecture et organisation du code

```text
projet/
├── compose.yaml          services Docker (app, database, phpmyadmin)
├── Dockerfile            image PHP du projet
├── Makefile              raccourcis (help, install, start, stop, bash, cc)
├── docker/
│   └── php.ini           réglages PHP de développement
├── .env                  réglages par défaut (versionné)
├── composer.json         dépendances PHP
├── bin/console           point d'entrée des commandes Symfony
├── config/               configuration de l'application
│   └── packages/         un fichier par paquet, créé par sa recette Flex
├── public/
│   └── index.php         SEUL fichier exposé au navigateur
├── migrations/           vide pour l'instant (créé par la recette Doctrine)
├── src/
│   ├── Kernel.php        le cœur de l'application
│   └── Controller/       vos contrôleurs
├── templates/            créé par la recette Twig (utilisé en phase 03)
├── var/                  cache et journaux (jamais versionné)
└── vendor/               dépendances installées (jamais versionné)
```

Pourquoi cette organisation :

- `public/` isolé = votre code source n'est pas téléchargeable ;
- `src/` = votre code, et rien d'autre ;
- `config/` = le comportement de l'application, modifiable sans toucher au code ;
- `var/` et `vendor/` = régénérables, donc exclus de Git.

---

## 8. Flux de fonctionnement

```text
Navigateur
    ↓  http://localhost:8081/
Docker (port 8081 → 80)
    ↓
FrankenPHP
    ↓
public/index.php
    ↓
Kernel (charge la configuration)
    ↓
Routing (quelle route correspond ?)
    ↓
HomeController::index()
    ↓
Response
    ↓
Navigateur
```

---

## 9. Application au projet

**Pourquoi cette phase ?** Sans environnement qui démarre, aucune autre phase
n'est possible. On installe donc la fondation, et **seulement** elle.

**Ce que vous allez créer** : `Dockerfile`, `compose.yaml` (trois services),
`docker/php.ini`, le squelette Symfony avec **tous les paquets** du projet,
`src/Controller/HomeController.php` avec la route `/`, un `Makefile` et un
`README.md` court.

**Pourquoi cette architecture ?** Le projet vise la simplicité : un conteneur
pour PHP + serveur web (FrankenPHP évite de configurer Nginx), un conteneur pour
MySQL, un conteneur phpMyAdmin pour regarder la base, et des raccourcis `make`
qui affichent les commandes réelles pour que vous puissiez les taper vous-même.

**Pourquoi tous les paquets maintenant ?** Pour que l'outillage soit réglé une
fois pour toutes : à partir de la phase 02, plus aucune phase ne lance de
`composer require`. Chaque paquet reste pourtant **inutilisé** tant que sa phase
n'est pas arrivée — ses fichiers de configuration par défaut suffisent.

**Ce que la phase ne fait pas encore** : pas de table en base (phase 02), pas
de gabarit Twig (phase 03), pas de sécurité configurée (phase 04). Les paquets
sont là, mais on ne s'en sert pas encore. La page d'accueil renvoie du texte
brut, et c'est normal.

---

## 10. Erreurs fréquentes

**`Failed to open stream: vendor/autoload.php`**
→ Les dépendances ne sont pas installées.
→ Signe : erreur dès la première page, avant tout code applicatif.
→ Solution : `docker compose exec app composer install`.

**`port is already allocated` au démarrage**
→ Un autre programme utilise déjà le port 8081, 8082 ou 3308 — souvent le projet
de référence, qui utilise les mêmes ports et les mêmes noms de conteneurs.
→ Signe : le conteneur refuse de démarrer, le message cite le port.
→ Solution : arrêter l'autre projet (`docker compose down` dans son dossier), ou
changer le port **de gauche** dans `compose.yaml` (`'8090:80'`). Si le dépôt de
référence (ou un autre projet utilisant le nom `digisante-junior`) existe sur la
même machine, choisissez un autre nom de projet compose, par exemple
`digisante-junior-etudiant`, et d'autres noms de conteneurs : sinon le volume
MySQL serait partagé entre les deux.

**La page ne répond pas sur `http://localhost:8081`, le conteneur est `Up`**
→ FrankenPHP sert du HTTPS par défaut.
→ Solution : vérifier `ENV SERVER_NAME=":80"` dans le `Dockerfile`, puis
`docker compose up -d --build`.

**404 sur une URL que vous venez de créer**
→ La route n'est pas reconnue (faute de frappe, mauvais dossier, cache).
→ Signe : `debug:router` ne liste pas votre route.
→ Solution : vérifier le namespace et l'emplacement du fichier, puis
`php bin/console cache:clear`.

**Une modification de fichier reste sans effet**
→ Le volume n'est pas monté, ou vous éditez un fichier hors du projet.
→ Signe : `docker compose exec app cat src/Controller/HomeController.php` montre
l'ancienne version.
→ Solution : vérifier la ligne `volumes: - ./:/app` dans `compose.yaml`.

**phpMyAdmin affiche « Impossible de se connecter » ou un écran de login**
→ `PMA_HOST` ne vaut pas `database`, ou `PMA_USER` / `PMA_PASSWORD` manquent.
→ Signe : `docker compose ps` montre `database` pas encore *healthy*, ou une
erreur « mysqli::real_connect ».
→ Solution : vérifier le bloc `phpmyadmin` de `compose.yaml` (hôte `database`,
port `3306` — pas `3308`), attendre que MySQL soit prêt, puis
`docker compose up -d`.

**Après un `composer require`, `compose.yaml` a changé (service `database` en double, PostgreSQL…)**
→ `extra.symfony.docker` n'était pas à `false` : la recette a ajouté sa
configuration Docker.
→ Solution : `git checkout compose.yaml` (ou remettre votre version), supprimer
un éventuel `compose.override.yaml` créé par la recette, régler
`"docker": false`.

**`Could not find package …` ou erreur de version lors d'un `composer require`**
→ Faute de frappe dans le nom du paquet.
→ Solution : recopier exactement la commande de la section 5 ; vérifier avec
`composer show` ce qui est déjà installé.

**`Permission denied` sur `var/`**
→ Les fichiers de cache ont été créés par un autre utilisateur.
→ Solution : `docker compose exec app rm -rf var/cache/*` puis relancer.

---

## 11. Bonnes pratiques

- **Une commande PHP = une commande dans le conteneur.** Ne cédez jamais à la
  tentation d'installer PHP sur votre machine « juste pour tester ».
- **Nommez vos routes** dès le départ (`app_home`) : les URL changent, les noms
  restent.
- **Préfixez les noms de routes par espace** : `app_`, `parent_`, `child_`,
  `admin_`. On sait d'un coup d'œil où l'on est.
- **Ne versionnez ni `vendor/` ni `var/`.**
- **Écrivez les commandes en clair dans le `Makefile`** : un raccourci qui cache
  ce qu'il fait empêche d'apprendre.
- **Commentez le pourquoi, pas le comment.** `// installe Composer` est inutile ;
  « Composer copié depuis son image officielle pour éviter une installation
  manuelle » est utile.

---

## 12. Exercice pratique

1. Ajoutez une seconde route dans `HomeController` :

```php
#[Route('/bonjour/{prenom}', name: 'app_bonjour', methods: ['GET'])]
public function bonjour(string $firstName): Response
{
    return new Response('Bonjour '.$firstName.' !');
}
```

2. Ouvrez `http://localhost:8081/bonjour/lea`.
3. Lancez `docker compose exec app php bin/console debug:router` et retrouvez vos
   deux routes.
4. Renommez volontairement la route en `app_bonjour_v2`, rechargez la page :
   que se passe-t-il ? (Réponse : rien du côté de l'URL — le **nom** ne sert
   qu'à générer des liens, le **chemin** seul détermine l'URL.)
5. **Supprimez ensuite cette route** : elle ne fait pas partie du projet.

Vous devez savoir expliquer la différence entre le **chemin** et le **nom**
d'une route.

---

## 13. Scénario de test manuel

1. Lancer `docker compose up -d --build`, installer le squelette et les paquets
   (commandes de la section 5) si ce n'est pas déjà fait, puis
   `docker compose exec app composer install`.
2. Lancer `docker compose ps` : `app`, `database` (*healthy*) et `phpmyadmin` sont `Up`.
3. Ouvrir `http://localhost:8081` dans le navigateur et vérifier que la page de
   votre contrôleur s'affiche (pas une erreur 404 ni 500).
4. Ouvrir `http://localhost:8081/page-qui-nexiste-pas`.
5. Ouvrir `http://localhost:8082` : phpMyAdmin s'ouvre sans écran de connexion
   et montre la base `digisante_junior`, vide.
6. Lancer `docker compose exec app composer show` et retrouver les paquets installés.
7. **Résultat attendu** : la page d'accueil répond, l'URL inconnue affiche une page d'erreur 404 de Symfony, phpMyAdmin montre la base vide, et `compose.yaml` n'a pas été modifié par les installations.

---

## Checklist

- [ ] J'ai compris les concepts principaux
- [ ] Je comprends le rôle des fichiers créés
- [ ] Je comprends les commandes utilisées
- [ ] Je peux expliquer le fonctionnement de cette phase
- [ ] J'ai réalisé l'exercice pratique
- [ ] J'ai exécuté le scénario de test manuel
- [ ] Le résultat attendu est obtenu

### Aller plus loin

➡️ [Phase suivante](./phase-02.md)

➡️ [Phase de développement](../README.md#phase-01--environnement-docker-squelette-symfony-et-paquets-du-projet)

➡️ [Prompt Claude Code](../prompts/phase-01.md)
