# Parcours de développement — Digi-Santé Junior

Guide pour construire **la plateforme entière, de zéro**, quand on débute avec
Symfony. Le projet est découpé en **13 phases** : chacune ajoute une brique
utilisable, s'appuie sur la précédente, et se termine par un test que vous
faites **à la main dans votre navigateur**.

Pour chaque phase, la démarche est toujours la même :

> **comprendre → apprendre → implémenter → tester manuellement → valider**

---

## Ce que vous allez construire

Une application de suivi du bien-être numérique des **enfants de 8 à 14 ans**.

- L'**enfant** remplit chaque jour un journal en 2 étapes : son temps d'écran,
  puis les endroits où il a mal sur un schéma du corps. Il reçoit aussitôt des
  conseils adaptés.
- Le **parent** crée les comptes de ses enfants, fixe une limite d'écran
  quotidienne et suit l'évolution sur un tableau de bord avec graphique.
- L'**administrateur** gère la bibliothèque de contenus (fiches, vidéos,
  quiz…) et les comptes parents.

Trois rôles **sans hiérarchie** : un administrateur n'est ni parent ni enfant.

| Rôle | Espace | Connexion |
|---|---|---|
| `ROLE_ADMIN` | `/admin` | email sur `/login` |
| `ROLE_PARENT` | `/parent` | email sur `/login` |
| `ROLE_CHILD` | `/enfant` | identifiant sur `/connexion-enfant` |

---

## Comment utiliser ce parcours

1. **Travaillez dans un dossier vide**, séparé de ce dépôt. Ce dépôt est la
   version terminée : vous pouvez l'ouvrir à côté pour comparer, mais ne
   recopiez pas un fichier sans l'avoir compris.
2. Lisez la phase dans ce README : objectif, notions à apprendre, commandes.
3. Lisez la **leçon de formation** de la phase (dossier
   [`formation/`](./formation/)) : elle explique les concepts Symfony dont vous
   avez besoin, avec des exemples commentés et un exercice.
4. Ouvrez le **prompt Claude Code** de la phase (dossier
   [`prompts/`](./prompts/)), copiez-le dans Claude Code et laissez-le
   implémenter la phase.
5. Relisez le code produit, puis **jouez le scénario de test manuel**.
6. Ne passez à la phase suivante que si le résultat attendu est atteint.

Trois documents, trois questions :

```text
ce README            →  QUOI développer dans cette phase ?
formation/phase-XX   →  QUELS concepts comprendre ? COMMENT ça marche ?
prompts/phase-XX     →  COMMENT demander l'implémentation à Claude Code ?
```

> Les prompts ne demandent **pas** de tests automatisés, sauf à la phase 12 qui
> leur est consacrée : la validation se fait au navigateur, comme un vrai
> utilisateur.

---

## Stack technique du parcours

| Rôle | Outil |
|---|---|
| Langage | PHP 8.4 |
| Framework | Symfony 7.4 (LTS) |
| Base de données | MySQL 8 |
| ORM | Doctrine ORM 3 (entités, migrations, fixtures) |
| Gabarits | Twig |
| Interface | Bootstrap 5.3 par CDN + une feuille `public/css/app.css` |
| Graphiques | Chart.js par CDN |
| Tests | PHPUnit 12 + `dama/doctrine-test-bundle` |
| Environnement | Docker : FrankenPHP, MySQL, phpMyAdmin |

**Ni Node.js, ni npm, ni build front** : Bootstrap et Chart.js sont chargés
depuis un CDN, et les fichiers de `public/` sont servis tels quels. C'est un
choix assumé pour rester simple — il n'y a donc aucune commande NPM dans ce
parcours.

**Hors périmètre du projet** (à ne pas ajouter) : emails, notifications,
badges, suivi du sport, du sommeil ou de l'humeur, et **API REST**. L'appli est
un site Twig classique, de bout en bout.

---

## Prérequis avant la phase 01

- [Docker Desktop](https://www.docker.com/products/docker-desktop/) installé
  (il fournit `docker compose`). **Rien d'autre** : PHP, Composer et MySQL
  tournent dans les conteneurs.
- Un éditeur de code, un terminal, et des bases en HTML/CSS.
- Savoir lire du PHP orienté objet (classes, méthodes, types).
- Connaître Symfony n'est **pas** un prérequis : c'est ce que vous apprenez ici.

---

## Vue d'ensemble des phases

| Phase | Nom | Ce que ça apporte | Formation | Prompt |
|---|---|---|---|---|
| 01 | Environnement Docker et squelette Symfony | L'appli répond sur `http://localhost:8081` | [Formation](./formation/phase-01.md) | [Prompt](./prompts/phase-01.md) |
| 02 | Gabarit de base, charte graphique et accueil | Une page d'accueil publique habillée | [Formation](./formation/phase-02.md) | [Prompt](./prompts/phase-02.md) |
| 03 | Base de données, Doctrine et entité `User` | Une table `users` et une migration | [Formation](./formation/phase-03.md) | [Prompt](./prompts/phase-03.md) |
| 04 | Inscription, connexion et rôles | Se créer un compte parent et se connecter | [Formation](./formation/phase-04.md) | [Prompt](./prompts/phase-04.md) |
| 05 | Espace parent : profils enfants | Le parent crée le compte de son enfant | [Formation](./formation/phase-05.md) | [Prompt](./prompts/phase-05.md) |
| 06 | Espace enfant : connexion et accueil | L'enfant se connecte avec son identifiant | [Formation](./formation/phase-06.md) | [Prompt](./prompts/phase-06.md) |
| 07 | Journal quotidien en 2 étapes | Écrans + douleurs enregistrés chaque jour | [Formation](./formation/phase-07.md) | [Prompt](./prompts/phase-07.md) |
| 08 | Bibliothèque de contenus (admin + enfant) | L'admin publie, l'enfant consulte | [Formation](./formation/phase-08.md) | [Prompt](./prompts/phase-08.md) |
| 09 | Moteur de conseils | Des conseils calculés à partir du journal | [Formation](./formation/phase-09.md) | [Prompt](./prompts/phase-09.md) |
| 10 | Tableau de bord parent et graphiques | Le suivi sur 7 ou 30 jours | [Formation](./formation/phase-10.md) | [Prompt](./prompts/phase-10.md) |
| 11 | Administration des comptes parents | Liste, fiche et suppression en cascade | [Formation](./formation/phase-11.md) | [Prompt](./prompts/phase-11.md) |
| 12 | Données de démonstration, qualité et tests | Fixtures, `make lint`, PHPUnit | [Formation](./formation/phase-12.md) | [Prompt](./prompts/phase-12.md) |
| 13 | Performance, robustesse et mise en production | Une appli prête à être déployée | [Formation](./formation/phase-13.md) | [Prompt](./prompts/phase-13.md) |

---

## Phase 01 — Environnement Docker et squelette Symfony

### Objectif

Obtenir un projet Symfony qui répond dans le navigateur, entièrement dans
Docker, sans rien installer sur votre machine.

### Prérequis

Docker Desktop lancé. Un dossier vide pour le projet.

### À apprendre

- Ce qu'est un **framework** et ce que Symfony fait à votre place.
- Le **cycle d'une requête** : `public/index.php` → `Kernel` → routage →
  contrôleur → réponse.
- La structure d'un projet Symfony (`src/`, `config/`, `templates/`, `public/`,
  `var/`, `vendor/`).
- Les **attributs de route** `#[Route]` et un premier contrôleur.
- Les variables d'environnement et le fichier `.env`.

### Concepts techniques

- Image Docker, conteneur, service, volume, port publié (`8081:80`).
- FrankenPHP = PHP + serveur web dans un seul conteneur.
- Composer : `composer.json`, `vendor/`, autoload PSR-4.

### Dépendances à installer

`symfony/skeleton` (base), puis `symfony/console`, `symfony/dotenv`,
`symfony/runtime`, `symfony/flex`. Le reste viendra phase par phase.

### Commandes

```bash
docker compose up -d --build                  # construire et démarrer
docker compose exec app composer install      # dépendances PHP
docker compose exec app php bin/console       # liste des commandes Symfony
docker compose exec app php bin/console debug:router
```

### Modules à développer

- `compose.yaml` : services `app` (FrankenPHP, port 8081) et `database`
  (MySQL 8, port 3308, fuseau `Europe/Paris`).
- `Dockerfile` : PHP 8.4 + extensions `pdo_mysql`, `intl`, `opcache`, `zip`.
- Squelette Symfony + `HomeController` avec une route `/` qui renvoie un texte.
- `Makefile` avec les raccourcis (`install`, `start`, `stop`, `cc`).

### Fichiers importants

`compose.yaml`, `Dockerfile`, `.env`, `public/index.php`,
`src/Controller/HomeController.php`, `Makefile`.

### Résultat attendu

`http://localhost:8081` affiche votre page (même en texte brut), et
`docker compose ps` montre les conteneurs **healthy**.

### Scénario de test manuel

1. Lancer `docker compose up -d --build`, puis `docker compose exec app composer install`.
2. Ouvrir `http://localhost:8081` dans le navigateur.
3. Vérifier que la page de votre contrôleur s'affiche (pas une erreur 404 ni 500).
4. Ouvrir `http://localhost:8081/page-qui-nexiste-pas`.
5. **Résultat attendu** : la page d'accueil répond, et l'URL inconnue affiche une page d'erreur 404 de Symfony.

### Formation

📘 [Formation Phase 01](./formation/phase-01.md) — les concepts à comprendre
avant (ou pendant) l'implémentation.

### Prompt Claude Code

👉 [Prompt Phase 01](./prompts/phase-01.md)

---

## Phase 02 — Gabarit de base, charte graphique et accueil

### Objectif

Remplacer le texte brut par de vraies pages HTML : un gabarit commun, la charte
graphique du projet, et une page d'accueil qui présente le service.

### Prérequis

Phase 01 terminée (l'appli répond sur le port 8081).

### À apprendre

- **Twig** : `{% extends %}`, `{% block %}`, `{{ variable }}`, `{% for %}`,
  `{% if %}`, la fonction `path()`.
- L'**héritage de gabarits** : un `base.html.twig`, puis un layout par espace.
- Le **routage nommé** : donner un nom à une route et l'utiliser dans Twig.
- Les fichiers publics (`public/css/app.css`) et la fonction `asset()`.

### Concepts techniques

- Bootstrap 5.3 par CDN : grille, `card`, `btn`, `navbar`, utilitaires.
- Variables CSS sur `:root` pour la palette ; classes maison du projet
  (`btn-marine`, `pastille`, `carte-titre`, `avatar-bulle`, `texte-doux`…).
- Polices Google Fonts : **Baloo 2** pour les titres, **Nunito** pour le texte.

### Dépendances à installer

```bash
docker compose exec app composer require twig
docker compose exec app composer require symfony/asset
```

### Commandes

```bash
docker compose exec app php bin/console lint:twig templates
docker compose exec app php bin/console cache:clear
```

### Modules à développer

- `templates/base.html.twig` avec les blocs `title`, `body_class`, `navbar`,
  `logo`, `marque_suffixe`, `menu`, `menu_utilisateur`, `body`, `javascripts`.
- `public/css/app.css` : palette, boutons en pilule, cartes arrondies.
- Page d'accueil publique : accroche, 3 arguments, deux boutons
  « Je suis un enfant » / « Je suis un parent ».

### Résultat attendu

Une page d'accueil soignée et responsive, avec la barre de navigation et le
pied de page communs à tout le site.

### Scénario de test manuel

1. Ouvrir `http://localhost:8081`.
2. Vérifier que les polices, les couleurs et les boutons arrondis s'affichent.
3. Réduire la fenêtre à la largeur d'un téléphone (~400 px).
4. Vérifier que le contenu reste lisible, sans barre de défilement horizontale.
5. **Résultat attendu** : la page est habillée, responsive, et le menu se replie sur mobile.

### Formation

📘 [Formation Phase 02](./formation/phase-02.md) — les concepts à comprendre
avant (ou pendant) l'implémentation.

### Prompt Claude Code

👉 [Prompt Phase 02](./prompts/phase-02.md)

---

## Phase 03 — Base de données, Doctrine et entité `User`

### Objectif

Connecter l'application à MySQL et créer la première table : les comptes de
connexion, communs aux trois rôles.

### Prérequis

Phase 02 terminée. Le conteneur `database` tourne.

### À apprendre

- L'**ORM** : une classe PHP = une table, un objet = une ligne.
- Les attributs Doctrine `#[ORM\Entity]`, `#[ORM\Column]`, `#[ORM\Id]`.
- Les **migrations** : décrire un changement de schéma dans un fichier versionné.
- Le **repository** : là où vivent toutes les requêtes.

### Concepts techniques

- `DATABASE_URL` et la différence entre `.env` (partagé) et `.env.local` (le vôtre).
- Types de colonnes : `string`, `json`, `datetime_immutable`.
- Contrainte d'unicité et index.
- phpMyAdmin pour regarder la base sans écrire de SQL.

### Dépendances à installer

```bash
docker compose exec app composer require symfony/orm-pack
docker compose exec app composer require --dev symfony/maker-bundle
```

### Commandes

```bash
docker compose exec app php bin/console make:entity
docker compose exec app php bin/console make:migration
docker compose exec app php bin/console doctrine:migrations:migrate
docker compose exec app php bin/console doctrine:schema:validate
```

### Modules à développer

- Service `phpmyadmin` dans `compose.yaml` (port 8082, connexion pré-configurée).
- Entité `User` : `email` (unique, nullable), `username` (unique, nullable),
  `roles`, `password`, `pays`, `ville`, `createdAt`.
- `UserRepository` et la première migration.

### Résultat attendu

La table `users` existe dans `digisante_junior`, et
`doctrine:schema:validate` est au vert.

### Scénario de test manuel

1. Lancer `make migrate` (ou `doctrine:migrations:migrate`).
2. Ouvrir phpMyAdmin sur `http://localhost:8082`.
3. Sélectionner la base `digisante_junior` et ouvrir la table `users`.
4. Vérifier la présence des colonnes `email`, `username`, `roles`, `password`, `created_at`.
5. **Résultat attendu** : la table existe avec les bonnes colonnes, et `doctrine:schema:validate` affiche deux `[OK]`.

### Formation

📘 [Formation Phase 03](./formation/phase-03.md) — les concepts à comprendre
avant (ou pendant) l'implémentation.

### Prompt Claude Code

👉 [Prompt Phase 03](./prompts/phase-03.md)

---

## Phase 04 — Inscription, connexion et rôles

### Objectif

Permettre à un parent de créer son compte, à un parent ou un administrateur de
se connecter, et envoyer chacun vers son espace selon son rôle.

### Prérequis

Phase 03 terminée (entité `User` en base).

### À apprendre

- Le composant **Security** : firewall, provider, `access_control`.
- `form_login` : Symfony vérifie le mot de passe, vous n'écrivez pas d'authenticator.
- Le **hachage** des mots de passe avec `UserPasswordHasherInterface`.
- Les **formulaires Symfony** : un `*Type`, `handleRequest()`, `isValid()`.
- La **validation** avec les contraintes `#[Assert\…]`.
- Les **messages flash**.

### Concepts techniques

- Rôles sans hiérarchie : `ROLE_ADMIN`, `ROLE_PARENT`, `ROLE_CHILD`.
- Protection **CSRF** des formulaires (activée par défaut, adossée à la session).
- Champ non mappé (`plainPassword`) : présent dans le formulaire, absent de l'entité.

### Dépendances à installer

```bash
docker compose exec app composer require symfony/security-bundle
docker compose exec app composer require symfony/form symfony/validator
```

### Commandes

```bash
docker compose exec app php bin/console make:user      # si vous partez de zéro
docker compose exec app php bin/console make:form
docker compose exec app php bin/console debug:router
```

### Modules à développer

- `config/packages/security.yaml` : hachage auto, provider sur l'entité `User`,
  `form_login`, `logout`, `remember_me` (7 jours), `access_control` sur
  `/admin`, `/parent`, `/enfant`.
- `SecurityController` : `/login`, `/logout`, `/inscription`.
- `InscriptionType` : email, pays, ville, mot de passe répété, case de consentement.
- `HomeController` : redirige l'utilisateur connecté vers son espace.

### Résultat attendu

On peut créer un compte parent, se connecter, être redirigé vers `/parent`, et
se déconnecter. Un visiteur non connecté est renvoyé vers `/login`.

### Scénario de test manuel

1. Ouvrir `/inscription` et créer un compte avec un email et un mot de passe de 6 caractères minimum.
2. Se connecter sur `/login` avec ce compte.
3. Vérifier la redirection automatique vers `/parent`.
4. Se déconnecter, puis ouvrir `/parent` directement dans la barre d'adresse.
5. **Résultat attendu** : l'inscription et la connexion fonctionnent, et l'accès à `/parent` déconnecté renvoie vers `/login`.

### Formation

📘 [Formation Phase 04](./formation/phase-04.md) — les concepts à comprendre
avant (ou pendant) l'implémentation.

### Prompt Claude Code

👉 [Prompt Phase 04](./prompts/phase-04.md)

---

## Phase 05 — Espace parent : profils enfants

### Objectif

Permettre au parent de créer, modifier et supprimer les profils de ses enfants,
chacun avec son compte de connexion et sa limite d'écran quotidienne.

### Prérequis

Phase 04 terminée (un parent peut se connecter).

### À apprendre

- Les **relations Doctrine** : `ManyToOne` (enfant → parent), `OneToOne`
  (enfant → compte), `OneToMany` avec `cascade: ['remove']`.
- Le **Voter** : autoriser une action sur **un objet précis**.
- Les **constantes d'entité** à la place des enums (`Enfant::AVATARS`).
- Une **extension Twig** : créer le filtre `duree` (« 2 h 30 »).
- Le `CallbackTransformer` : un curseur envoie du texte, l'entité attend un entier.

### Concepts techniques

- Cascades de suppression : enfant supprimé → compte + journaux supprimés.
- Génération d'un identifiant unique à partir du prénom (`Léa` → `lea`, `lea2`…).
- CSRF sur une suppression hors formulaire : `isCsrfTokenValid()` + un partiel Twig.
- Validation métier : âge 8-14 ans, limite de 15 à 480 min par pas de 15.

### Dépendances à installer

Aucune nouvelle : `make:entity`, `make:voter` et `make:form` suffisent.

### Commandes

```bash
docker compose exec app php bin/console make:entity Enfant
docker compose exec app php bin/console make:voter EnfantVoter
docker compose exec app php bin/console make:migration && make migrate
```

### Modules à développer

- Entité `Enfant` (prénom, nom, date de naissance, avatar, limite) + migration.
- `Parent\EnfantController` : liste, création, modification, suppression.
- `EnfantType` (option `creation` pour le mot de passe), `MotDePasseType`.
- `EnfantVoter` (`ENFANT_GERER`), `Twig\DureeExtension`, partiel
  `_partials/bouton_supprimer.html.twig`.

### Résultat attendu

Le parent gère ses enfants et voit l'identifiant de connexion généré. Un enfant
d'un autre parent renvoie une erreur 403.

### Scénario de test manuel

1. Connecté en parent, ouvrir `/parent/enfants` puis « Ajouter un enfant ».
2. Saisir un prénom, un nom, une date de naissance d'un enfant de 10 ans, un avatar, une limite de 1 h 30 et un mot de passe.
3. Valider et lire le message : il annonce l'identifiant généré (ex. « lea »).
4. Essayer de créer un deuxième enfant avec une date de naissance d'un enfant de 4 ans.
5. **Résultat attendu** : le premier enfant apparaît dans la liste avec son identifiant ; le second est refusé avec le message « L'application est réservée aux enfants de 8 à 14 ans. »

### Formation

📘 [Formation Phase 05](./formation/phase-05.md) — les concepts à comprendre
avant (ou pendant) l'implémentation.

### Prompt Claude Code

👉 [Prompt Phase 05](./prompts/phase-05.md)

---

## Phase 06 — Espace enfant : connexion et accueil

### Objectif

Donner à l'enfant sa propre page de connexion (par identifiant, pas par email),
son accueil et son profil.

### Prérequis

Phase 05 terminée (un profil enfant existe avec son compte).

### À apprendre

- Charger un utilisateur par **email ou identifiant** : `UserLoaderInterface`
  et `loadUserByIdentifier()`.
- Deux pages de connexion pour **un seul** `check_path`.
- Le champ caché `_failure_path` (un **chemin**, pas un nom de route).
- `#[CurrentUser]` pour récupérer l'utilisateur connecté dans une action.

### Concepts techniques

- Adapter le ton et l'ergonomie à l'enfant : tutoiement, emojis, gros boutons,
  messages bienveillants.
- Un layout par espace (`enfant/layout.html.twig`) et un fond de page dédié.
- Changement de mot de passe par l'enfant lui-même.

### Dépendances à installer

Aucune.

### Commandes

```bash
docker compose exec app php bin/console debug:router | grep enfant
docker compose exec app php bin/console lint:twig templates
```

### Modules à développer

- `UserRepository::loadUserByIdentifier()` (email **ou** username).
- Page `/connexion-enfant` (sans barre de navigation, message d'erreur bienveillant).
- `Enfant\AccueilController` : `/enfant` (salutation, avatar, limite du jour),
  `/enfant/profil` (carte d'identité + changement de mot de passe).
- `templates/enfant/layout.html.twig`.

### Résultat attendu

L'enfant se connecte avec son identifiant et arrive sur son accueil. Le
tableau de bord du jour et le journal viendront à la phase suivante.

### Scénario de test manuel

1. Se déconnecter, puis ouvrir `/connexion-enfant`.
2. Saisir l'identifiant créé en phase 05 et son mot de passe.
3. Vérifier l'arrivée sur `/enfant` avec le prénom et l'avatar de l'enfant.
4. Ouvrir « Mon profil », changer le mot de passe, se déconnecter et se reconnecter avec le nouveau.
5. **Résultat attendu** : la connexion par identifiant fonctionne et le nouveau mot de passe est accepté.

### Formation

📘 [Formation Phase 06](./formation/phase-06.md) — les concepts à comprendre
avant (ou pendant) l'implémentation.

### Prompt Claude Code

👉 [Prompt Phase 06](./prompts/phase-06.md)

---

## Phase 07 — Journal quotidien en 2 étapes

### Objectif

Le cœur du produit : l'enfant déclare chaque jour son temps d'écran, puis les
zones où il a mal, en moins d'une minute.

### Prérequis

Phase 06 terminée (l'enfant se connecte à son espace).

### À apprendre

- La **session** pour transporter l'étape 1 jusqu'à l'étape 2, sans rien écrire
  en base tant que le parcours n'est pas terminé.
- Un formulaire **non lié à une entité** (il renvoie un simple tableau).
- Une contrainte portant sur **plusieurs champs** : `Assert\Callback` dans
  l'option `constraints` du formulaire.
- Une contrainte d'**unicité en base** : un seul journal par enfant et par jour.

### Concepts techniques

- SVG interactif + JavaScript vanilla, données transmises en JSON dans un champ caché.
- **Revalidation côté serveur** de tout ce qui vient du navigateur : zone
  inconnue ou intensité hors 1-5 → ignorée.
- `textContent` et jamais `innerHTML` pour insérer une donnée.
- Fuseau `Europe/Paris` côté PHP **et** MySQL, pour que « aujourd'hui » soit le
  même jour des deux côtés.

### Dépendances à installer

Aucune.

### Commandes

```bash
docker compose exec app php bin/console make:entity JournalEntree
docker compose exec app php bin/console make:entity DouleurZone
docker compose exec app php bin/console make:migration && make migrate
```

### Modules à développer

- Entités `JournalEntree` (6 durées d'écran, date, index unique
  `enfant_id + date`) et `DouleurZone` (zone, intensité 1-5).
- `Enfant\JournalController` : `/enfant/journal`, `/etape/1`, `/etape/2`.
- `JournalEcransType` (6 curseurs, total plafonné à 16 h, valeurs à zéro au
  départ) et `JournalDouleursType` (champ caché + CSRF).
- Schéma corporel SVG avec ses 6 zones et la fenêtre de choix d'intensité.

### Résultat attendu

Un journal par jour et par enfant, enregistré d'un seul coup à la fin de
l'étape 2, avec des données vérifiées côté serveur.

### Scénario de test manuel

1. Connecté en enfant, cliquer sur « Mon journal ».
2. Étape 1 : bouger les curseurs jusqu'à environ 2 h 30 au total, vérifier que le total se met à jour en direct, puis continuer.
3. Étape 2 : cliquer sur le cou, choisir l'intensité 4, puis terminer le journal.
4. Revenir sur « Mon journal » une seconde fois dans la même journée.
5. **Résultat attendu** : le journal est enregistré avec 2 h 30 et la douleur au cou, et la seconde visite ne propose plus le formulaire (un seul journal par jour).

### Formation

📘 [Formation Phase 07](./formation/phase-07.md) — les concepts à comprendre
avant (ou pendant) l'implémentation.

### Prompt Claude Code

👉 [Prompt Phase 07](./prompts/phase-07.md)

---

## Phase 08 — Bibliothèque de contenus (admin + enfant)

### Objectif

Donner à l'administrateur un CRUD complet sur les contenus pédagogiques, et à
l'enfant une page pour les découvrir.

### Prérequis

Phase 07 terminée. Un compte `ROLE_ADMIN` existe en base (créé à la main ou en
fixtures).

### À apprendre

- Un **CRUD complet** en Symfony : liste, création, édition, suppression.
- Le **param converter** : `#[Route('/{id}')]` + argument typé `ContenuBienEtre $contenu`.
- `ChoiceType`, `TextareaType`, `UrlType` et l'option `help`.
- Restreindre une section entière par `access_control`.

### Concepts techniques

- Les **données métier vivent en base**, pas dans le code ni les gabarits :
  l'admin modifie les textes sans développeur.
- Un contenu peut être rattaché à une **règle déclencheuse** (utilisée à la
  phase 09) ; la liste ne doit contenir que des règles réellement appliquées.
- Suppression en POST + jeton CSRF + confirmation navigateur.

### Dépendances à installer

Aucune.

### Commandes

```bash
docker compose exec app php bin/console make:entity ContenuBienEtre
docker compose exec app php bin/console make:migration && make migrate
docker compose exec app php bin/console debug:router | grep admin
```

### Modules à développer

- Entité `ContenuBienEtre` (type, titre, contenu, url, déclencheur, createdAt)
  avec ses constantes `TYPES` et `DECLENCHEURS`.
- `Admin\ContenuController` + `ContenuBienEtreType` + `admin/layout.html.twig`.
- Page enfant `/enfant/bibliotheque`, contenus groupés par type.

### Résultat attendu

L'admin publie un contenu ; l'enfant le voit aussitôt dans « Découvrir ».

### Scénario de test manuel

1. Se connecter avec le compte administrateur et ouvrir `/admin/contenus`.
2. Créer un contenu de type « Fiche », avec un titre, un texte et un lien.
3. Se déconnecter, se connecter en enfant et ouvrir « 📚 Découvrir ».
4. Retourner en admin, modifier le titre du contenu, puis rafraîchir la page enfant.
5. **Résultat attendu** : le contenu apparaît côté enfant dans le bon groupe, et le titre modifié s'affiche après rafraîchissement.

### Formation

📘 [Formation Phase 08](./formation/phase-08.md) — les concepts à comprendre
avant (ou pendant) l'implémentation.

### Prompt Claude Code

👉 [Prompt Phase 08](./prompts/phase-08.md)

---

## Phase 09 — Moteur de conseils

### Objectif

Transformer le journal du jour en conseils personnalisés, formulés de manière
positive.

### Prérequis

Phases 07 et 08 terminées (journal enregistré, contenus en base).

### À apprendre

- Créer un **service métier** et l'injecter dans un contrôleur.
- L'**injection de dépendances** et l'autowiring.
- Quand créer un service : seulement si le code est partagé par plusieurs
  contrôleurs (ici l'espace enfant et, phase 10, le tableau de bord parent).

### Concepts techniques

- Des règles simples et lisibles, dans une seule méthode :
  1. temps d'écran ≥ 2 h → règle du 20-20-20 ;
  2. temps d'écran > limite du parent → conseil de pause (remplace la règle 1) ;
  3. douleur au cou ou aux épaules ≥ 3 → étirements ;
  4. douleur aux yeux → yoga des yeux ;
  5. aucune règle déclenchée → félicitations.
- Le service renvoie des **tableaux simples**, pas des DTO.
- Le texte des exercices vient de la base : chaque conseil peut pointer vers un
  contenu de la bibliothèque.

### Dépendances à installer

Aucune.

### Commandes

```bash
docker compose exec app php bin/console debug:container ConseilService
docker compose exec app php bin/console debug:autowiring
```

### Modules à développer

- `Service\ConseilService` avec ses seuils en constantes.
- `ContenuBienEtreRepository::findPremierPourDeclencheur()`.
- Page `/enfant/journal/conseils` : récapitulatif du jour + conseils + contenu associé.

### Résultat attendu

À la fin du journal, l'enfant reçoit des conseils cohérents avec ce qu'il a
saisi, et peut les revoir toute la journée depuis son accueil.

### Scénario de test manuel

1. Supprimer le journal du jour de l'enfant pour pouvoir recommencer :
   `docker compose exec app php bin/console dbal:run-sql "DELETE FROM journal_entree WHERE date = CURDATE()"`.
2. Remplir un journal avec **plus de temps d'écran que la limite** du profil et une douleur aux yeux.
3. Lire la page de conseils affichée à la fin.
4. Revenir à l'accueil et cliquer sur « Revoir mes conseils ».
5. **Résultat attendu** : deux conseils apparaissent (dépassement de limite et yoga des yeux), avec le contenu de la bibliothèque associé, et ils sont identiques au retour depuis l'accueil.

### Formation

📘 [Formation Phase 09](./formation/phase-09.md) — les concepts à comprendre
avant (ou pendant) l'implémentation.

### Prompt Claude Code

👉 [Prompt Phase 09](./prompts/phase-09.md)

---

## Phase 10 — Tableau de bord parent et graphiques

### Objectif

Donner au parent une vue claire : la journée en cours, les conseils reçus par
l'enfant, et la courbe du temps d'écran sur 7 ou 30 jours.

### Prérequis

Phase 09 terminée (journaux et conseils disponibles).

### À apprendre

- Écrire des **requêtes dans le repository** avec le QueryBuilder, jamais dans
  le contrôleur.
- Lire des paramètres d'URL (`?enfant=…&periode=…`) avec `$request->query`.
- Passer des données PHP au JavaScript : `{{ donnees|json_encode|raw }}`.
- Réutiliser un fichier JS dans `public/js/` quand il sert à plusieurs pages.

### Concepts techniques

- Construire une série de dates continue : un jour sans journal vaut 0 minute.
- Jauge colorée : vert sous 2 h, orange de 2 à 4 h, rouge au-delà.
- Sécurité : un `?enfant=` qui ne vous appartient pas retombe sur votre premier
  enfant — aucune donnée d'un autre foyer n'est exposée.

### Dépendances à installer

Chart.js par CDN (aucun paquet Composer, aucun NPM).

### Commandes

```bash
docker compose exec app php bin/console cache:clear
docker compose exec app php bin/console dbal:run-sql "SELECT * FROM journal_entree LIMIT 5"
```

### Modules à développer

- `JournalEntreeRepository::getGraphiqueEcran()` (labels + minutes par jour).
- `Parent\TableauDeBordController` : sélecteur d'enfant, journal du jour,
  conseils, graphique ; écran d'accueil dédié si aucun enfant.
- `public/js/graphique-ecran.js`, utilisé par le parent **et** par l'accueil enfant.

### Résultat attendu

Le parent suit chaque enfant sur 7 ou 30 jours, et l'enfant voit la même courbe
sur son accueil.

### Scénario de test manuel

1. Se connecter en parent et ouvrir `/parent`.
2. Vérifier le temps d'écran du jour, la jauge colorée et les douleurs signalées.
3. Basculer sur « 30 derniers jours » et vérifier que la courbe change.
4. Modifier l'URL avec l'identifiant d'un enfant qui ne vous appartient pas (`/parent?enfant=999`).
5. **Résultat attendu** : les deux périodes s'affichent avec la ligne de limite en pointillés, et l'identifiant étranger affiche simplement votre premier enfant.

### Formation

📘 [Formation Phase 10](./formation/phase-10.md) — les concepts à comprendre
avant (ou pendant) l'implémentation.

### Prompt Claude Code

👉 [Prompt Phase 10](./prompts/phase-10.md)

---

## Phase 11 — Administration des comptes parents

### Objectif

Terminer l'espace d'administration : voir les comptes parents, consulter leur
fiche et supprimer un compte avec toutes ses données.

### Prérequis

Phase 10 terminée.

### À apprendre

- Filtrer sur une colonne **JSON** (les rôles) dans un repository.
- Renvoyer une **404** volontaire (`createNotFoundException`) quand un
  identifiant ne désigne pas le bon type de compte.
- Vérifier ce que font vraiment les **cascades Doctrine** avant de s'y fier.

### Concepts techniques

- Chaîne de suppression : parent → enfants → comptes + journaux → douleurs.
- Confirmation navigateur + jeton CSRF sur chaque suppression.
- Un administrateur **ne gère pas** les profils enfants : ils relèvent de leur
  parent. Il les voit en lecture seule sur la fiche du parent.

### Dépendances à installer

Aucune.

### Commandes

```bash
docker compose exec app php bin/console dbal:run-sql "SELECT id, email, roles FROM users"
docker compose exec app php bin/console debug:router | grep admin
```

### Modules à développer

- `UserRepository::findParents()`.
- `Admin\ParentController` : liste, fiche, suppression.
- Fiche parent : informations du compte + ses enfants en lecture seule.

### Résultat attendu

L'administrateur voit tous les comptes parents et peut en supprimer un ; toutes
les données liées disparaissent avec lui.

### Scénario de test manuel

1. Créer un compte parent de test via `/inscription`, puis lui ajouter un enfant.
2. Se connecter en administrateur et ouvrir `/admin/parents`.
3. Ouvrir la fiche de ce parent et vérifier que son enfant y figure.
4. Supprimer le compte, confirmer, puis vérifier dans phpMyAdmin les tables `users`, `enfant` et `journal_entree`.
5. **Résultat attendu** : le message indique le nombre de profils enfants supprimés, et plus aucune ligne liée à ce parent ne subsiste en base.

### Formation

📘 [Formation Phase 11](./formation/phase-11.md) — les concepts à comprendre
avant (ou pendant) l'implémentation.

### Prompt Claude Code

👉 [Prompt Phase 11](./prompts/phase-11.md)

---

## Phase 12 — Données de démonstration, qualité et tests

### Objectif

Rendre le projet reprenable : un jeu de données réaliste en une commande, des
vérifications automatiques, et une suite de tests.

### Prérequis

Phases 01 à 11 terminées (toutes les fonctionnalités existent).

### À apprendre

- Les **fixtures** Doctrine : des données de démonstration reproductibles.
- Les **linters** Symfony : `lint:twig`, `lint:yaml`, `lint:container`,
  `doctrine:schema:validate`.
- **PHPUnit** avec Symfony : `WebTestCase` pour un parcours, `KernelTestCase`
  pour un service, `TestCase` pour une classe pure.
- `dama/doctrine-test-bundle` : chaque test s'exécute dans une transaction
  annulée, la base de test reste intacte.

### Concepts techniques

> C'est **la seule phase où l'on écrit des tests automatisés** : les autres se
> valident au navigateur. Ici, les tests servent de filet pour la suite.

- Une base de test séparée (`digisante_junior_test`) et ses droits MySQL.
- `$client->loginUser()` pour se connecter sans passer par le formulaire.
- Un formulaire invalide renvoie **422**, pas 200 : les tests doivent en tenir compte.

### Dépendances à installer

```bash
docker compose exec app composer require --dev doctrine/doctrine-fixtures-bundle
docker compose exec app composer require --dev phpunit/phpunit symfony/browser-kit symfony/css-selector
docker compose exec app composer require --dev dama/doctrine-test-bundle
```

### Commandes

```bash
make fixtures    # recharge les données de démonstration
make lint        # les 4 vérifications
make tests       # prépare la base de test et lance PHPUnit
```

### Modules à développer

- `AppFixtures` : 1 admin, 2 parents, 4 enfants, des journaux sur plusieurs
  semaines, une quinzaine de contenus.
- Cibles `lint`, `tests`, `fixtures` et `reset-db` dans le `Makefile`.
- Tests : sécurité et accès par rôle, gestion des enfants, parcours du journal,
  règles du moteur de conseils, filtre `duree`.

### Résultat attendu

`make install` remonte un environnement complet, `make lint` et `make tests`
passent au vert.

### Scénario de test manuel

1. Lancer `make reset-db` pour repartir d'une base propre remplie par les fixtures.
2. Se connecter avec chacun des trois comptes de démonstration (admin, parent, enfant).
3. Lancer `make lint`, puis `make tests`.
4. Ouvrir le tableau de bord parent : les journaux des dernières semaines doivent être visibles dans la courbe.
5. **Résultat attendu** : les trois connexions fonctionnent, les quatre linters affichent `[OK]`, la suite PHPUnit est verte, et le graphique est rempli.

### Formation

📘 [Formation Phase 12](./formation/phase-12.md) — les concepts à comprendre
avant (ou pendant) l'implémentation.

### Prompt Claude Code

👉 [Prompt Phase 12](./prompts/phase-12.md)

---

## Phase 13 — Performance, robustesse et mise en production

### Objectif

Passer d'un projet qui marche sur votre machine à un projet prêt à être
déployé, sans fuite d'information ni requête inutile.

### Prérequis

Phase 12 terminée (tests et fixtures en place).

### À apprendre

- La différence entre les environnements **dev** et **prod** (cache, profiler,
  affichage des erreurs).
- Le problème des **requêtes N+1** et comment le repérer dans le profiler.
- `composer install --no-dev --optimize-autoloader` et le préchargement OPcache.
- Les en-têtes et réglages de sécurité d'une application publique.

### Concepts techniques

- `APP_ENV=prod`, `APP_DEBUG=0`, `APP_SECRET` sorti du dépôt.
- Jointures explicites (`addSelect`) pour éviter les allers-retours SQL.
- Sauvegarde de la base et politique de conservation des données (il s'agit de
  données de santé d'enfants).
- Journalisation : ce que Monolog écrit en prod, et ce qu'il ne doit pas écrire.

### Dépendances à installer

Aucune nouvelle ; on retire au contraire les paquets de développement à
l'installation de production.

### Commandes

```bash
docker compose exec app php bin/console cache:clear --env=prod
docker compose exec app composer install --no-dev --optimize-autoloader
docker compose exec app php bin/console debug:container --env=prod
```

### Modules à développer

- Un fichier d'exemple `.env.prod.local.dist` (sans secret réel) et la
  documentation du déploiement dans le `README`.
- Relecture des requêtes des repositories (jointures, tris, limites).
- Vérification finale des accès : chaque route sensible protégée, chaque
  suppression en POST + CSRF.

### Résultat attendu

L'application tourne en environnement `prod` sans barre de debug, sans page
d'erreur bavarde, et avec des pages rapides.

### Scénario de test manuel

1. Passer `APP_ENV=prod` et `APP_DEBUG=0`, puis vider le cache en prod.
2. Ouvrir l'application : la barre de debug ne doit plus apparaître.
3. Provoquer une erreur volontaire (URL inexistante, puis une suppression sans jeton CSRF).
4. Se connecter en parent et ouvrir le tableau de bord, puis la liste des enfants.
5. **Résultat attendu** : aucune trace technique n'est affichée à l'utilisateur (page 404 et 403 sobres), les pages s'affichent rapidement, et les erreurs détaillées ne sont visibles que dans `var/log/`.

### Formation

📘 [Formation Phase 13](./formation/phase-13.md) — les concepts à comprendre
avant (ou pendant) l'implémentation.

### Prompt Claude Code

👉 [Prompt Phase 13](./prompts/phase-13.md)

---

## Après le parcours

- Reprenez le fichier `CLAUDE.md` du projet : il résume les conventions, les
  pièges connus et l'historique des décisions.
- Relisez les [leçons de formation](./formation/README.md) : après avoir
  construit l'application, les concepts se lisent différemment.
- Comparez votre code avec ce dépôt : les écarts sont des occasions
  d'apprendre, pas des erreurs.
- Les évolutions possibles (récupération de mot de passe, historique des
  douleurs côté parent, export des données) sont listées dans le cahier des
  charges, section « Points d'attention ».
