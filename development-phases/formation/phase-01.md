# Formation — Phase 01 : Environnement Docker et squelette Symfony

## 1. Objectifs pédagogiques

À la fin de cette leçon, vous devez être capable de :

- expliquer ce qu'est un **framework** et ce que Symfony fait à votre place ;
- décrire le **cycle d'une requête** : du navigateur au contrôleur, puis retour ;
- comprendre le rôle de **Docker** ici, et pourquoi rien n'est installé sur votre
  machine ;
- comprendre ce que fait **Composer** et à quoi sert le dossier `vendor/` ;
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

**Dans ce projet.** Trois services : `app` (FrankenPHP = PHP + serveur web),
`database` (MySQL), et à partir de la phase 03 `phpmyadmin`. Conséquence
importante : **toute commande PHP passe par le conteneur**.

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
namespace App\Controller;                    // doit correspondre au dossier src/Controller

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController
{
    #[Route('/', name: 'app_home', methods: ['GET'])]
    //       │          │                   └── verbes HTTP acceptés
    //       │          └── nom de la route, utilisé pour générer des liens
    //       └── chemin de l'URL
    public function index(): Response
    {
        return new Response('Digi-Santé Junior — bientôt disponible');
    }
}
```

**Dans ce projet.** Toutes les routes sont en attributs (jamais en YAML) et leur
nom est **préfixé par l'espace** : `app_` pour le public, puis `parent_`,
`enfant_`, `admin_` dans les phases suivantes.

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
| `.env.test` | réglages des tests | oui |

**Dans ce projet.** Avec Docker, `compose.yaml` fournit directement la bonne
`DATABASE_URL` au conteneur : vous n'avez rien à régler pour démarrer.

---

## 4. Explications avec exemples

### Le `Dockerfile`, ligne par ligne

```dockerfile
FROM dunglas/frankenphp:1-php8.4-bookworm
# Image de départ : PHP 8.4 + un serveur web (Caddy) déjà configuré
# pour servir le dossier public/. Aucun fichier de configuration Apache/Nginx.

RUN install-php-extensions pdo_mysql intl opcache zip
# pdo_mysql : parler à MySQL
# intl      : afficher les dates en français
# opcache   : garder le PHP compilé en mémoire (performance)

COPY --from=composer/composer:2-bin /composer /usr/bin/composer
# Composer est copié depuis son image officielle : pas d'installation manuelle

WORKDIR /app
# Dossier de travail par défaut dans le conteneur
```

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
- **À observer** : `Up` pour `app`, et `Up (healthy)` pour `database`. Un
  conteneur qui redémarre en boucle signale une erreur de configuration.

### `docker compose exec app composer install`

- **Ce qu'elle fait** : installe les dépendances PHP **dans le conteneur**.
- **À observer** : la création du dossier `vendor/`. Sans lui, toutes les pages
  échouent sur « `vendor/autoload.php` introuvable ».

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
| **Flex** | installe et configure automatiquement les paquets Symfony |

---

## 7. Architecture et organisation du code

```text
projet/
├── compose.yaml          services Docker (app, database)
├── Dockerfile            image PHP du projet
├── Makefile              raccourcis de commandes
├── .env                  réglages par défaut (versionné)
├── composer.json         dépendances PHP
├── bin/console           point d'entrée des commandes Symfony
├── config/               configuration de l'application
├── public/
│   └── index.php         SEUL fichier exposé au navigateur
├── src/
│   ├── Kernel.php        le cœur de l'application
│   └── Controller/       vos contrôleurs
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

**Ce que vous allez créer** : `Dockerfile`, `compose.yaml`, le squelette
Symfony, `src/Controller/HomeController.php` avec la route `/`, un `Makefile` et
un `README.md` court.

**Pourquoi cette architecture ?** Le projet vise la simplicité : un conteneur
pour PHP + serveur web (FrankenPHP évite de configurer Nginx), un conteneur pour
MySQL, et des raccourcis `make` qui affichent les commandes réelles pour que vous
puissiez les taper vous-même.

**Ce que la phase ne fait pas encore** : pas de Twig (phase 02), pas de base de
données (phase 03), pas de sécurité (phase 04). La page d'accueil renvoie du
texte brut, et c'est normal.

---

## 10. Erreurs fréquentes

**`Failed to open stream: vendor/autoload.php`**
→ Les dépendances ne sont pas installées.
→ Signe : erreur dès la première page, avant tout code applicatif.
→ Solution : `docker compose exec app composer install`.

**`port is already allocated` au démarrage**
→ Un autre programme utilise déjà le port 8081 ou 3308.
→ Signe : le conteneur refuse de démarrer, le message cite le port.
→ Solution : changer le port **de gauche** dans `compose.yaml` (`'8090:80'`).

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

**`Permission denied` sur `var/`**
→ Les fichiers de cache ont été créés par un autre utilisateur.
→ Solution : `docker compose exec app rm -rf var/cache/*` puis relancer.

---

## 11. Bonnes pratiques

- **Une commande PHP = une commande dans le conteneur.** Ne cédez jamais à la
  tentation d'installer PHP sur votre machine « juste pour tester ».
- **Nommez vos routes** dès le départ (`app_home`) : les URL changent, les noms
  restent.
- **Préfixez les noms de routes par espace** : `app_`, `parent_`, `enfant_`,
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
public function bonjour(string $prenom): Response
{
    return new Response('Bonjour '.$prenom.' !');
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

1. Lancer `docker compose up -d --build`, puis `docker compose exec app composer install`.
2. Ouvrir `http://localhost:8081` dans le navigateur.
3. Vérifier que la page de votre contrôleur s'affiche (pas une erreur 404 ni 500).
4. Ouvrir `http://localhost:8081/page-qui-nexiste-pas`.
5. **Résultat attendu** : la page d'accueil répond, et l'URL inconnue affiche une page d'erreur 404 de Symfony.

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

➡️ [Phase de développement](../README.md#phase-01--environnement-docker-et-squelette-symfony)

➡️ [Prompt Claude Code](../prompts/phase-01.md)
