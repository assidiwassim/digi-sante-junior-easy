# Parcours de développement — Digi-Santé Junior

Guide pour construire **la plateforme entière, de zéro**, quand on débute avec
Symfony. Le projet est découpé en **12 phases** : chacune ajoute une brique
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

> ⚠️ **Si le dépôt de référence (ou un autre projet utilisant le nom
> `digisante-junior`) existe sur la même machine**, choisissez un autre nom de
> projet compose, par exemple `digisante-junior-etudiant`, et d'autres noms de
> conteneurs : deux dossiers qui partagent le même nom de projet compose
> partagent aussi le volume MySQL. Les ports (8081, 8082, 3308) étant les
> mêmes, arrêtez l'autre projet (`docker compose down` dans son dossier)
> pendant que vous travaillez sur le vôtre.

> Aucune phase ne demande de tests automatisés : la validation se fait
> toujours au navigateur, comme un vrai utilisateur.

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
| 01 | Environnement Docker, squelette Symfony et paquets du projet | L'appli répond sur `http://localhost:8081`, phpMyAdmin sur `8082`, tous les paquets sont installés | [Formation](./formation/phase-01.md) | [Prompt](./prompts/phase-01.md) |
| 02 | Base de données et entités | Les 5 tables du projet, leurs relations et leurs règles de validation | [Formation](./formation/phase-02.md) | [Prompt](./prompts/phase-02.md) |
| 03 | Gabarit de base, charte graphique et page d'accueil | Une page d'accueil publique habillée | [Formation](./formation/phase-03.md) | [Prompt](./prompts/phase-03.md) |
| 04 | Authentification, rôles, inscription et connexion parent | Se créer un compte parent et se connecter | [Formation](./formation/phase-04.md) | [Prompt](./prompts/phase-04.md) |
| 05 | Espace parent : profils enfants | Le parent crée le compte de son enfant | [Formation](./formation/phase-05.md) | [Prompt](./prompts/phase-05.md) |
| 06 | Espace enfant : connexion et accueil | L'enfant se connecte avec son identifiant | [Formation](./formation/phase-06.md) | [Prompt](./prompts/phase-06.md) |
| 07 | Espace enfant : journal quotidien en 2 étapes | Écrans + douleurs enregistrés chaque jour | [Formation](./formation/phase-07.md) | [Prompt](./prompts/phase-07.md) |
| 08 | Bibliothèque de contenus (admin + enfant) | L'admin publie, l'enfant consulte | [Formation](./formation/phase-08.md) | [Prompt](./prompts/phase-08.md) |
| 09 | Moteur de conseils | Des conseils calculés à partir du journal | [Formation](./formation/phase-09.md) | [Prompt](./prompts/phase-09.md) |
| 10 | Tableau de bord parent et enfant | Le suivi sur 7 ou 30 jours, côté parent et côté enfant | [Formation](./formation/phase-10.md) | [Prompt](./prompts/phase-10.md) |
| 11 | Administration des comptes parents | Liste, fiche et suppression en cascade | [Formation](./formation/phase-11.md) | [Prompt](./prompts/phase-11.md) |
| 12 | Données de démonstration et qualité | Fixtures, `make lint`, un README complet | [Formation](./formation/phase-12.md) | [Prompt](./prompts/phase-12.md) |

---

## Phase 01 — Environnement Docker, squelette Symfony et paquets du projet

### Objectif

Obtenir un projet Symfony qui répond dans le navigateur, entièrement dans
Docker, sans rien installer sur votre machine, avec **tous les paquets** dont
le projet aura besoin et phpMyAdmin pour regarder la base.

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
- **Composer** et les **recettes Flex** : un paquet installé peut créer ses
  fichiers de configuration.

### Concepts techniques

- Image Docker, conteneur, service, volume, port publié (`8081:80`).
- FrankenPHP = PHP + serveur web dans un seul conteneur.
- Composer : `composer.json`, `vendor/`, autoload PSR-4.
- phpMyAdmin pour regarder la base sans écrire de SQL (outil de développement
  uniquement).

### Dépendances à installer

`symfony/skeleton` (7.4) d'abord : il contient déjà `symfony/console`,
`symfony/dotenv`, `symfony/runtime`, `symfony/flex`, `framework-bundle` et
`yaml`, inutile de les installer à part.

Puis **tous les paquets du projet**, en deux commandes. Avant de les lancer,
régler `extra.symfony.docker` à `false` dans `composer.json` : sinon une
recette réécrit votre `compose.yaml`.

```bash
docker compose exec app composer require twig symfony/asset symfony/orm-pack symfony/security-bundle symfony/form symfony/validator symfony/translation twig/extra-bundle twig/intl-extra
docker compose exec app composer require --dev symfony/maker-bundle symfony/profiler-pack symfony/debug-bundle doctrine/doctrine-fixtures-bundle
```

| Paquet | À quoi il sert | Utilisé à partir de |
|---|---|---|
| `twig` (alias Flex de `symfony/twig-pack`), `symfony/asset` | Gabarits HTML, fonction `asset()` pour `public/css/app.css` | Phase 03 |
| `symfony/orm-pack` | Doctrine ORM, migrations, connexion MySQL | Phase 02 |
| `symfony/maker-bundle` (dev) | Générateurs `make:entity`, `make:migration`, `make:form`, `make:voter`… | Phase 02 |
| `symfony/security-bundle` | Interfaces de `User` (phase 02), puis connexion et rôles | Phases 02 et 04 |
| `symfony/validator` | Contraintes `#[Assert\…]` sur les entités, vérifiées par les formulaires | Phases 02 et 04 |
| `symfony/form` | Formulaires (`*Type`, `handleRequest()`, `isValid()`) | Phase 04 |
| `symfony/translation` | Messages de Symfony en français | Phase 04 |
| `twig/extra-bundle`, `twig/intl-extra` | Filtre `format_date` (date du jour en français) | Phase 06 |
| `symfony/profiler-pack`, `symfony/debug-bundle` (dev) | Barre de debug et `dump()` | Dès la phase 02 |
| `doctrine/doctrine-fixtures-bundle` (dev) | Données de démonstration | Phase 12 |

Les recettes Flex créent des fichiers de configuration (`doctrine.yaml`,
`security.yaml`, `csrf.yaml`, `translation.yaml`, `twig.yaml`,
`templates/base.html.twig`…) : on les laisse tels quels, chaque phase
configure ce dont elle a besoin.

### Commandes

`composer create-project` refuse un dossier non vide (il contient déjà
`Dockerfile` et `compose.yaml`) : on installe le squelette dans un dossier
temporaire, puis on le copie dans `/app`.

```bash
docker compose up -d --build                  # construire et démarrer
docker compose exec app sh -c 'composer create-project symfony/skeleton:"7.4.*" /tmp/skeleton && cp -rn /tmp/skeleton/. /app/ && rm -rf /tmp/skeleton'
docker compose exec app composer install      # dépendances PHP
docker compose exec app composer show         # liste des paquets installés
docker compose exec app php bin/console       # liste des commandes Symfony
docker compose exec app php bin/console debug:router
```

### Modules à développer

- `compose.yaml` : services `app` (FrankenPHP, port 8081), `database`
  (MySQL 8, port 3308, fuseau `Europe/Paris`, healthcheck) et `phpmyadmin`
  (image `phpmyadmin:5.2`, port 8082, `PMA_HOST: database`, `PMA_PORT: 3306`,
  `PMA_USER` / `PMA_PASSWORD` pour une connexion automatique, `TZ:
  Europe/Paris`, démarré une fois `database` **healthy** ; commentaire « outil
  de développement uniquement »).
- `Dockerfile` : PHP 8.4 + extensions `pdo_mysql`, `intl`, `opcache`, `zip`,
  avec `ENV SERVER_NAME=":80"` (sinon FrankenPHP sert du HTTPS et le port
  8081 ne répond pas) et `COPY docker/php.ini $PHP_INI_DIR/conf.d/app.ini`.
- Squelette Symfony + `HomeController` avec une route `/` qui renvoie un texte.
- Tous les paquets du projet (voir ci-dessus).
- `Makefile` avec les raccourcis (`help`, `install`, `start`, `stop`, `bash`, `cc`).
- Un `README.md` court : comment démarrer le projet.

### Fichiers importants

`compose.yaml`, `Dockerfile`, `docker/php.ini`, `.env`, `composer.json`,
`public/index.php`, `src/Controller/HomeController.php`, `Makefile`.

### Résultat attendu

`http://localhost:8081` affiche votre page (même en texte brut),
`http://localhost:8082` ouvre phpMyAdmin, et `docker compose ps` montre
`database` **healthy**, `app` **healthy** aussi (l'image FrankenPHP a son
propre healthcheck) et `phpmyadmin` **Up**. Dans `composer.json`, les « packs »
(`twig`, `orm-pack`, `profiler-pack`) apparaissent dépaquetés en leurs
composants. La barre de debug n'apparaît pas encore : c'est normal, la page
renvoie du texte et non du HTML.

### Scénario de test manuel

1. Lancer `docker compose up -d --build`, puis `docker compose exec app composer install`.
2. Ouvrir `http://localhost:8081` dans le navigateur.
3. Vérifier que la page de votre contrôleur s'affiche (pas une erreur 404 ni 500).
4. Ouvrir `http://localhost:8081/page-qui-nexiste-pas`.
5. Ouvrir `http://localhost:8082` : phpMyAdmin s'ouvre **sans écran de
   connexion** et montre la base `digisante_junior`, encore vide.
6. Lancer `docker compose exec app composer show` et retrouver les paquets installés.
7. **Résultat attendu** : la page d'accueil répond, l'URL inconnue affiche une page d'erreur 404 de Symfony, phpMyAdmin montre la base vide, et `compose.yaml` n'a pas été modifié par les installations.

### Formation

📘 [Formation Phase 01](./formation/phase-01.md) — les concepts à comprendre
avant (ou pendant) l'implémentation.

### Prompt Claude Code

👉 [Prompt Phase 01](./prompts/phase-01.md)

---

## Phase 02 — Base de données et entités

### Objectif

Connecter l'application à MySQL et créer **toutes les tables du projet** :
les comptes de connexion, les profils enfants, les journaux, les douleurs et
les contenus, avec leurs relations et leurs règles de validation. Aucune page
dans cette phase : on construit le modèle de données.

### Prérequis

Phase 01 terminée. Les conteneurs `database` et `phpmyadmin` tournent.

### À apprendre

- L'**ORM** : une classe PHP = une table, un objet = une ligne.
- Le **mapping** avec les attributs Doctrine `#[ORM\Entity]`,
  `#[ORM\Column]`, `#[ORM\Id]`.
- Les **relations** : `ManyToOne`, `OneToMany`, `OneToOne` ; côté
  propriétaire (qui porte la clé étrangère) et côté inverse (`mappedBy`).
- Les **cascades de suppression**, des deux côtés : `cascade: ['remove']` pour
  Doctrine **et** `onDelete: 'CASCADE'` sur la clé étrangère, filet de
  sécurité si une ligne est supprimée hors Doctrine.
- Les **contraintes de validation** `#[Assert\…]` posées sur l'entité : elles
  seront vérifiées par les formulaires à partir de la phase 04.
- Les **migrations** : décrire un changement de schéma dans un fichier versionné.
- Le **repository** : là où vivront toutes les requêtes.

### Concepts techniques

- `DATABASE_URL` et la différence entre `.env` (partagé) et `.env.local` (le
  vôtre) ; une vraie variable d'environnement (fournie ici par `compose.yaml`)
  l'emporte sur les deux.
- Types de colonnes : `string`, `text`, `integer`, `json`,
  `date_immutable` (un jour), `datetime_immutable` (un instant).
- Contrainte d'unicité et **index unique**, y compris sur deux colonnes
  (un seul journal par enfant et par jour).
- Des **constantes d'entité** plutôt que des enums PHP (`Enfant::AVATARS`,
  `DouleurZone::ZONES`, `ContenuBienEtre::TYPES`…), avec des getters
  d'affichage (`getAvatarEmoji()`, `getZoneLabel()`…).
- Chaîne de suppression du projet : parent → enfants → compte + journaux → douleurs.

### Dépendances à installer

Aucune (installées en phase 01).

### Commandes

```bash
docker compose exec app php bin/console make:entity
docker compose exec app php bin/console make:migration
docker compose exec app php bin/console doctrine:migrations:migrate
docker compose exec app php bin/console doctrine:schema:validate
docker compose exec app php bin/console doctrine:mapping:info
```

Relisez **chaque** migration générée avant de l'appliquer.

### Modules à développer

- Configuration : `DATABASE_URL` vers le conteneur `database`,
  `doctrine.yaml` commenté.
- Entité **`User`** (table `users`) : `email` (unique, nullable), `username`
  (unique, nullable), `roles`, `password`, `pays`, `ville`, `createdAt` ;
  constantes de rôles, `isParent()`, `setEmail()` / `setUsername()` qui
  passent en minuscules, `#[UniqueEntity]`, `eraseCredentials()` vide avec
  l'attribut `#[\Deprecated]` (dépréciée depuis Symfony 7.3) ; relations
  `enfants` (`OneToMany`, `cascade: ['remove']`) et `profilEnfant`
  (`OneToOne`, côté inverse).
- Entité **`Enfant`** : `parent` (`ManyToOne`, `onDelete: 'CASCADE'`),
  `compte` (`OneToOne` **non nullable**, `cascade: ['persist', 'remove']`),
  prénom, nom, `dateNaissance` (`date_immutable`, 8 à 14 ans), avatar,
  `maxMinutesJour` (15 à 480 min par pas de 15, 120 par défaut),
  `journalEntrees` (`OneToMany`, `cascade: ['remove']`) ; constantes
  `AVATARS` et `LIMITE_*` ; `getNomComplet()`, `getAge()`,
  `getAvatarEmoji()`, `getAvatarNom()` ; messages de validation en français.
- Entité **`JournalEntree`** : `enfant` (`ManyToOne`, `onDelete: 'CASCADE'`),
  `date` (`date_immutable`, aujourd'hui par défaut), 6 durées d'écran
  (entiers, 0 par défaut), `douleurs` (`OneToMany`,
  `cascade: ['persist', 'remove']`), **index unique** `(enfant_id, date)` ;
  constante `ECRANS`, `getTotalEcran()`, `niveauPourMinutes()` (statique) et
  `getNiveauEcran()` (seuils 120 et 240 min).
- Entité **`DouleurZone`** : `journalEntree` (`ManyToOne`,
  `onDelete: 'CASCADE'`), `zone`, `intensite` (1 à 5), constructeur
  `(zone, intensite)` ; constante `ZONES` (yeux, cou, épaule, dos, poignet,
  main), `getZoneLabel()`, `getZoneEmoji()`.
- Entité **`ContenuBienEtre`** : `type` (« fiche » par défaut), `titre`
  (160 caractères, obligatoire), `contenu` (`text`), `url` (500 caractères,
  facultative, `#[Assert\Url(requireTld: true, …)]` : l'omettre est déprécié
  depuis Symfony 7.1), `declencheur` (facultatif), `createdAt` ; constantes
  `TYPES` et `DECLENCHEURS` (limitées aux 3 règles réellement appliquées :
  20-20-20, étirement cervical, yoga des yeux) ; `getTypeLabel()`,
  `getTypeEmoji()`, `getDeclencheurLabel()`.
- Les **repositories** générés par `make:entity`, laissés **vides** : chaque
  requête sera écrite dans la phase qui l'utilise.
- Une ou plusieurs migrations, relues puis appliquées.
- Cible `migrate` dans le `Makefile`
  (`php bin/console doctrine:migrations:migrate --no-interaction`), utilisée
  par les scénarios à partir d'ici.

### Fichiers importants

`compose.yaml`, `.env`, `config/packages/doctrine.yaml`, `src/Entity/*.php`,
`src/Repository/*.php`, `migrations/`, `Makefile`.

### Résultat attendu

Les 5 tables existent dans `digisante_junior`, avec leurs clés étrangères et
leurs index uniques ; `doctrine:schema:validate` est au vert et
`doctrine:mapping:info` liste les 5 entités.

### Scénario de test manuel

1. Lancer `make migrate`.
2. Ouvrir phpMyAdmin sur `http://localhost:8082` et sélectionner la base `digisante_junior`.
3. Vérifier la présence des tables `users`, `enfant`, `journal_entree`, `douleur_zone` et `contenu_bien_etre`.
4. Ouvrir l'onglet « Structure » puis « Vue relationnelle » de `enfant`, `journal_entree` et `douleur_zone` : `parent_id`, `enfant_id` et `journal_entree_id` sont en `ON DELETE CASCADE` ; `compte_id` ne l'est pas (le compte est supprimé par la cascade Doctrine `Enfant::compte`).
5. Vérifier les index uniques : `email` et `username` sur `users`, `compte_id` sur `enfant`, `(enfant_id, date)` sur `journal_entree`.
6. **Résultat attendu** : les 5 tables existent avec les bonnes colonnes, `doctrine:schema:validate` affiche deux `[OK]` et `doctrine:mapping:info` liste 5 entités.

### Formation

📘 [Formation Phase 02](./formation/phase-02.md) — les concepts à comprendre
avant (ou pendant) l'implémentation.

### Prompt Claude Code

👉 [Prompt Phase 02](./prompts/phase-02.md)

---

## Phase 03 — Gabarit de base, charte graphique et page d'accueil

### Objectif

Remplacer le texte brut par de vraies pages HTML : un gabarit commun, la charte
graphique du projet, et une page d'accueil qui présente le service.

### Prérequis

Phase 02 terminée (l'appli répond sur le port 8081, la base est en place).

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
- La barre de debug (profiler installé en phase 01) apparaît dès la première
  page HTML.

### Dépendances à installer

Aucune (installées en phase 01).

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
  « Je suis un enfant » / « Je suis un parent » (ils pointent vers `#`
  jusqu'aux phases 04 et 06).

### Résultat attendu

Une page d'accueil soignée et responsive, avec la barre de navigation et le
pied de page communs à tout le site.

### Scénario de test manuel

1. Ouvrir `http://localhost:8081`.
2. Vérifier que les polices, les couleurs et les boutons arrondis s'affichent.
3. Réduire la fenêtre à la largeur d'un téléphone (~400 px).
4. Vérifier que le contenu reste lisible, sans barre de défilement horizontale.
5. **Résultat attendu** : la page est habillée, responsive, le menu se replie sur mobile, et la barre de debug s'affiche en bas.

### Formation

📘 [Formation Phase 03](./formation/phase-03.md) — les concepts à comprendre
avant (ou pendant) l'implémentation.

### Prompt Claude Code

👉 [Prompt Phase 03](./prompts/phase-03.md)

---

## Phase 04 — Authentification, rôles, inscription et connexion parent

### Objectif

Permettre à un parent de créer son compte, à un parent ou un administrateur de
se connecter, et envoyer chacun vers son espace selon son rôle.

### Prérequis

Phase 03 terminée. L'entité `User` et son `UserRepository` existent depuis la
phase 02.

### À apprendre

- Le composant **Security** : firewall, provider, `access_control`.
- `form_login` : Symfony vérifie le mot de passe, vous n'écrivez pas d'authenticator.
- Le **hachage** des mots de passe avec `UserPasswordHasherInterface`.
- Les **formulaires Symfony** : un `*Type`, `handleRequest()`, `isValid()`.
- La **validation** : les contraintes `#[Assert\…]` posées sur les entités en
  phase 02 prennent vie dans les formulaires.
- Les **messages flash**.

### Concepts techniques

- Rôles sans hiérarchie : `ROLE_ADMIN`, `ROLE_PARENT`, `ROLE_CHILD`.
- Protection **CSRF** des formulaires (activée par défaut, adossée à la session).
- Champ non mappé (`plainPassword`) : présent dans le formulaire, absent de l'entité.

### Dépendances à installer

Aucune (installées en phase 01).

### Commandes

```bash
docker compose exec app php bin/console debug:router
docker compose exec app php bin/console lint:yaml config
```

### Modules à développer

- `config/packages/security.yaml` : hachage auto, provider sur l'entité `User`,
  `form_login`, `logout`, `remember_me` (7 jours), `access_control` sur
  `/admin`, `/parent`, `/enfant`.
- `config/packages/csrf.yaml` : la recette génère la variante **stateless** ;
  **remplacer** le fichier par `csrf_protection: { enabled: true }` et
  `form: { csrf_protection: { enabled: true } }` (CSRF adossé à la session).
- `config/packages/translation.yaml` : `default_locale: fr` et
  `fallbacks: [fr]`, pour des messages Symfony en français.
- `config/packages/twig.yaml` : thème de formulaire `bootstrap_5_layout.html.twig`.
- `UserRepository` implémente `UserLoaderInterface` : `loadUserByIdentifier()`
  cherche par **email** (minuscules, sans espaces) ; sinon toute connexion
  renvoie une erreur 500. La phase 06 l'étendra à l'identifiant.
- `SecurityController` : `/login`, `/logout`, `/inscription`.
- `InscriptionType` : email, pays, ville, mot de passe répété, case de consentement.
- Pages d'attente `Parent\DashboardController` (`/parent`, route
  `parent_dashboard`) et `Admin\AccueilController` (`/admin`, route `admin_accueil`).
- `HomeController` : redirige ADMIN → `admin_accueil`, PARENT →
  `parent_dashboard` (l'enfant viendra en phase 06 : jamais de redirection vers
  une route qui n'existe pas encore).
- Page d'accueil : le bouton « Je suis un parent » pointe désormais vers
  `app_login` ; les boutons de connexion enfant (accueil et page de connexion)
  restent sur `#` jusqu'en phase 06.

### Résultat attendu

On peut créer un compte parent, se connecter, être redirigé vers `/parent`, et
se déconnecter. Un visiteur non connecté est renvoyé vers `/login`.

### Scénario de test manuel

1. Ouvrir `/inscription` et créer un compte avec un email et un mot de passe de 6 caractères minimum.
2. Se connecter sur `/login` avec ce compte.
3. Vérifier la redirection automatique vers `/parent`.
4. Se déconnecter, puis ouvrir `/parent` directement dans la barre d'adresse.
5. Créer le compte administrateur : **d'abord** s'inscrire via `/inscription`
   avec `admin@digisante.local` / `admin123`, **puis** le promouvoir :
   `docker compose exec app php bin/console dbal:run-sql "UPDATE users SET roles = '[\"ROLE_ADMIN\"]' WHERE email = 'admin@digisante.local'"`.
   Si vous étiez connecté avec ce compte, Symfony vous **déconnecte** (rôle
   changé) : reconnectez-vous, vous arrivez sur `/admin`.
6. **Résultat attendu** : l'inscription et la connexion fonctionnent, l'accès à `/parent` déconnecté renvoie vers `/login`, et l'admin est redirigé vers `/admin`.

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

Phase 04 terminée (un parent peut se connecter). L'entité `Enfant` et ses
relations existent depuis la phase 02.

### À apprendre

- **Utiliser** les relations créées en phase 02 : un enfant et son compte
  enregistrés ensemble grâce à `cascade: ['persist']`.
- Le **Voter** : autoriser une action sur **un objet précis**.
- Une **extension Twig** : créer le filtre `duree` (« 2 h 30 »).
- Le `CallbackTransformer` : un curseur envoie du texte, l'entité attend un entier.

### Concepts techniques

- Suppression d'un enfant : les cascades posées en phase 02 suppriment aussi
  son compte (et, plus tard, ses journaux).
- Génération d'un identifiant unique à partir du prénom (`Léa` → `lea`, `lea2`…).
- CSRF sur une suppression hors formulaire : `isCsrfTokenValid()` + un partiel
  Twig ; jeton invalide → `createAccessDeniedException('Jeton CSRF invalide.')`
  (**403**, même règle pour toutes les suppressions du projet).
- Validation métier (âge 8-14 ans, limite de 15 à 480 min par pas de 15) :
  les contraintes de l'entité s'affichent dans le formulaire.

### Dépendances à installer

Aucune (installées en phase 01).

### Commandes

```bash
docker compose exec app php bin/console make:voter EnfantVoter
docker compose exec app php bin/console debug:router | grep parent
```

### Modules à développer

- `UserRepository::genererUsername()` et `EnfantRepository::findByParent()`.
- `Parent\EnfantController` : liste, création, modification, suppression.
- `EnfantType` (option `creation` pour le mot de passe, galerie d'avatars),
  `MotDePasseType`.
- `Parent\ProfilController` : `/parent/profil` (route `parent_profil`, vers
  laquelle mène le menu), `ProfilParentType` (email obligatoire, pays, ville)
  + `MotDePasseType` sur la même page ; après une saisie invalide,
  `$entityManager->refresh($user)`, sinon le parent est déconnecté.
- `EnfantVoter` (`ENFANT_GERER`), `Twig\DureeExtension`, partiel
  `_partials/bouton_supprimer.html.twig`, `templates/parent/layout.html.twig`.

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
- La date du jour en français avec `twig/intl-extra` :
  `{{ 'now'|format_date('full', locale: 'fr') }}`.

### Dépendances à installer

Aucune (installées en phase 01).

### Commandes

```bash
docker compose exec app php bin/console debug:router | grep enfant
docker compose exec app php bin/console lint:twig templates
```

### Modules à développer

- `UserRepository::loadUserByIdentifier()` : on étend la méthode de la
  phase 04 (email **ou** username).
- `HomeController` : ajout de la redirection ROLE_CHILD → `enfant_accueil`.
- Page `/connexion-enfant` (sans barre de navigation, message d'erreur bienveillant).
- `Enfant\AccueilController` : `/enfant` (salutation, avatar, limite du jour),
  `/enfant/profil` (carte d'identité + changement de mot de passe).
- `templates/enfant/layout.html.twig`.

### Résultat attendu

L'enfant se connecte avec son identifiant et arrive sur son accueil. La
jauge du jour et le journal arrivent en phase 07, le graphique en phase 10.

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

## Phase 07 — Espace enfant : journal quotidien en 2 étapes

### Objectif

Le cœur du produit : l'enfant déclare chaque jour son temps d'écran, puis les
zones où il a mal, en moins d'une minute.

### Prérequis

Phase 06 terminée (l'enfant se connecte à son espace). Les entités
`JournalEntree` et `DouleurZone` existent depuis la phase 02.

### À apprendre

- La **session** pour transporter l'étape 1 jusqu'à l'étape 2, sans rien écrire
  en base tant que le parcours n'est pas terminé.
- Un formulaire **non lié à une entité** (il renvoie un simple tableau).
- Une contrainte portant sur **plusieurs champs** : `Assert\Callback` dans
  l'option `constraints` du formulaire.
- Profiter de l'**index unique** `(enfant_id, date)` de la phase 02 : un seul
  journal par enfant et par jour.

### Concepts techniques

- SVG interactif + JavaScript vanilla, données transmises en JSON dans un champ caché.
- **Revalidation côté serveur** de tout ce qui vient du navigateur : zone
  inconnue ou intensité hors 1-5 → ignorée.
- `textContent` et jamais `innerHTML` pour insérer une donnée.
- Fuseau `Europe/Paris` côté PHP **et** MySQL, pour que « aujourd'hui » soit le
  même jour des deux côtés.

### Dépendances à installer

Aucune (installées en phase 01).

### Commandes

```bash
docker compose exec app php bin/console debug:router | grep journal
docker compose exec app php bin/console dbal:run-sql "SELECT * FROM journal_entree"
```

### Modules à développer

- `JournalEntreeRepository::findAujourdhui()`.
- `Enfant\JournalController` : `/enfant/journal`, `/enfant/journal/etape/1`,
  `/enfant/journal/etape/2`, `/enfant/journal/conseils`.
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

Phase 07 terminée. Le compte `ROLE_ADMIN` créé en phase 04 existe en base.
L'entité `ContenuBienEtre` existe depuis la phase 02.

### À apprendre

- Un **CRUD complet** en Symfony : liste, création, édition, suppression.
- Le **param converter** : `#[Route('/{id}')]` + argument typé `ContenuBienEtre $contenu`.
- `ChoiceType`, `TextareaType`, `UrlType` et l'option `help`.
- Restreindre une section entière par `access_control`.

### Concepts techniques

- Les **données métier vivent en base**, pas dans le code ni les gabarits :
  l'admin modifie les textes sans développeur.
- Un contenu peut être rattaché à une **règle déclencheuse** (utilisée à la
  phase 09) ; la liste ne contient que des règles réellement appliquées.
- Suppression en POST + jeton CSRF + confirmation navigateur.

### Dépendances à installer

Aucune (installées en phase 01).

### Commandes

```bash
docker compose exec app php bin/console make:form
docker compose exec app php bin/console debug:router | grep admin
```

### Modules à développer

- `ContenuBienEtreRepository::findTousTries()` (type puis titre, pas de
  réordonnancement par l'admin) et `findGroupesParType()`.
- `Admin\ContenuController` + `ContenuBienEtreType` + `admin/layout.html.twig`.
  Il porte désormais la route `admin_accueil` (`/admin` → redirection vers
  `admin_contenus`) : **supprimer** la page d'attente `Admin\AccueilController`
  de la phase 04 et son gabarit (sinon deux routes du même nom).
- Page enfant `/enfant/bibliotheque`, contenus groupés par type.

### Résultat attendu

L'admin publie un contenu ; l'enfant le voit aussitôt dans « Découvrir ».

### Scénario de test manuel

1. Se connecter avec le compte administrateur et ouvrir `/admin/contenus`.
2. Créer un contenu de type « Fiche », avec un titre, un texte et un lien.
3. Dans une **fenêtre de navigation privée** (deuxième session), se connecter en enfant et ouvrir « 📚 Découvrir ».
4. Dans la fenêtre admin, modifier le titre du contenu, puis rafraîchir la page enfant.
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

Aucune (installées en phase 01).

### Commandes

```bash
docker compose exec app php bin/console debug:container ConseilService
docker compose exec app php bin/console debug:autowiring Conseil --all
```

Sans `--all`, `debug:autowiring` n'affiche pas les services `App\`.

### Modules à développer

- `Service\ConseilService` avec ses seuils en constantes.
- `ContenuBienEtreRepository::findPremierPourDeclencheur()`.
- Page `/enfant/journal/conseils` : récapitulatif du jour + conseils + contenu associé.
- Sur l'accueil enfant, quand le journal est rempli, un bouton « 💡 Revoir mes
  conseils » vers `enfant_journal_conseils` (à ajouter dans cette phase).

### Résultat attendu

À la fin du journal, l'enfant reçoit des conseils cohérents avec ce qu'il a
saisi, et peut les revoir toute la journée depuis son accueil.

### Scénario de test manuel

1. Préparer : dans l'admin, créer un contenu rattaché à la règle « Yoga des
   yeux » (idéalement un par règle), puis supprimer le journal du jour de
   l'enfant pour pouvoir recommencer :
   `docker compose exec app php bin/console dbal:run-sql "DELETE FROM journal_entree WHERE date = CURDATE()"`.
2. Remplir un journal avec **plus de temps d'écran que la limite** du profil et
   une douleur aux yeux. Pour vérifier aussi le seuil « 2 h pile » (20-20-20),
   utiliser un enfant dont la limite est **≥ 2 h** (sinon c'est le dépassement
   qui s'affiche) : la Léa de la phase 05 a 1 h 30, montez sa limite à 3 h.
3. Lire la page de conseils affichée à la fin.
4. Revenir à l'accueil et cliquer sur « Revoir mes conseils ».
5. **Résultat attendu** : deux conseils apparaissent (dépassement de limite et yoga des yeux), avec le contenu de la bibliothèque associé, et ils sont identiques au retour depuis l'accueil.

### Formation

📘 [Formation Phase 09](./formation/phase-09.md) — les concepts à comprendre
avant (ou pendant) l'implémentation.

### Prompt Claude Code

👉 [Prompt Phase 09](./prompts/phase-09.md)

---

## Phase 10 — Tableau de bord parent et enfant

### Objectif

Donner au parent une vue claire : la journée en cours, les conseils reçus par
l'enfant, et la courbe du temps d'écran sur 7 ou 30 jours ; l'enfant retrouve
la même courbe sur son accueil.

### Prérequis

Phase 09 terminée (journaux et conseils disponibles).

### À apprendre

- Écrire des **requêtes dans le repository** avec le QueryBuilder, jamais dans
  le contrôleur.
- Lire des paramètres d'URL (`?enfant=…&periode=…`) avec `$request->query`,
  via `getInt()` et une liste blanche :
  `30 === $request->query->getInt('periode') ? 30 : 7`, et l'identifiant
  demandé comparé à `$enfant->getId()`. Même chose sur l'accueil enfant. En
  Symfony 7, une valeur non numérique (`?periode=abc`) répond **400**.
- Passer des données PHP au JavaScript : `{{ donnees|json_encode|raw }}`.
- Réutiliser un fichier JS dans `public/js/` quand il sert à plusieurs pages.

### Concepts techniques

- Construire une série de dates continue : un jour sans journal vaut 0 minute.
- Jauge colorée : vert sous 2 h, orange de 2 à 4 h, rouge au-delà.
- Sécurité : un `?enfant=` qui ne vous appartient pas retombe sur votre premier
  enfant — aucune donnée d'un autre foyer n'est exposée.

### Dépendances à installer

Aucune (installées en phase 01). Chart.js est chargé par CDN (aucun paquet
Composer, aucun NPM).

### Commandes

```bash
docker compose exec app php bin/console cache:clear
docker compose exec app php bin/console dbal:run-sql "SELECT date, ecran_tv FROM journal_entree ORDER BY date DESC LIMIT 5"
```

### Modules à développer

- `JournalEntreeRepository::getGraphiqueEcran()` (labels + minutes par jour).
- `Parent\TableauDeBordController` : sélecteur d'enfant, journal du jour,
  conseils, graphique ; écran d'accueil dédié si aucun enfant. Il remplace la
  page d'attente `Parent\DashboardController` de la phase 04 (même route
  `parent_dashboard`) : supprimer l'ancienne.
- Graphique 7 / 30 jours sur l'accueil enfant (`Enfant\AccueilController`).
- `public/js/graphique-ecran.js`, utilisé par le parent **et** par l'accueil enfant.

### Résultat attendu

Le parent suit chaque enfant sur 7 ou 30 jours, et l'enfant voit la même courbe
sur son accueil.

### Scénario de test manuel

1. Se connecter en parent et ouvrir `/parent`.
2. Vérifier le temps d'écran du jour, la jauge colorée, les douleurs signalées et les conseils reçus par l'enfant.
3. Basculer sur « 30 derniers jours » et vérifier que la courbe change.
4. Modifier l'URL avec l'identifiant d'un enfant qui ne vous appartient pas (`/parent?enfant=999`).
5. Se connecter en enfant : la même courbe s'affiche sur l'accueil.
6. **Résultat attendu** : les deux périodes s'affichent avec la ligne de limite en pointillés, l'identifiant étranger affiche simplement votre premier enfant, et l'enfant voit sa courbe.

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

- Chaîne de suppression : parent → enfants → comptes + journaux → douleurs,
  assurée par `cascade: ['remove']` (Doctrine) et `onDelete: 'CASCADE'` (clés
  étrangères), posés en phase 02.
- Le nombre d'enfants se compte **avant** la suppression : c'est plus clair,
  et après `flush()` l'objet ne représente plus rien en base.
- Confirmation navigateur + jeton CSRF sur chaque suppression.
- Un administrateur **ne gère pas** les profils enfants : ils relèvent de leur
  parent. Il les voit en lecture seule sur la fiche du parent.

### Dépendances à installer

Aucune (installées en phase 01).

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

1. Créer un compte parent de test via `/inscription`, s'y connecter et lui ajouter un enfant, puis remplir un journal pour cet enfant.
2. Se connecter en administrateur, ouvrir `/admin/parents` et vérifier que le compte de test apparaît avec « 1 » enfant.
3. Ouvrir sa fiche : l'enfant doit être listé, sans lien ni bouton d'action.
4. Supprimer le compte, confirmer, puis vérifier dans phpMyAdmin les tables `users`, `enfant`, `journal_entree` et `douleur_zone`.
5. **Résultat attendu** : le message indique le nombre de profils enfants supprimés, et plus aucune ligne liée à ce parent ne subsiste dans les quatre tables.

### Formation

📘 [Formation Phase 11](./formation/phase-11.md) — les concepts à comprendre
avant (ou pendant) l'implémentation.

### Prompt Claude Code

👉 [Prompt Phase 11](./prompts/phase-11.md)

---

## Phase 12 — Données de démonstration et qualité

### Objectif

Rendre le projet reprenable : un jeu de données réaliste en une commande, des
vérifications automatiques de la configuration, et un README qui permet
d'installer le projet sans aide. C'est la **dernière phase** du parcours.

### Prérequis

Phases 01 à 11 terminées (toutes les fonctionnalités existent).

### À apprendre

- Les **fixtures** Doctrine : des données de démonstration reproductibles.
- Les **linters** Symfony : `lint:twig`, `lint:yaml`, `lint:container`,
  `doctrine:schema:validate`.

### Concepts techniques

- Les fixtures **purgent** la base avant de la remplir : jamais sur des données
  réelles.
- Des données reproductibles : graine aléatoire fixe (`mt_srand()`) et dates de
  naissance relatives à aujourd'hui (les âges restent entre 8 et 14 ans).
- Les mots de passe des comptes de démonstration sont hachés comme les autres.
- Comme partout dans le parcours, la validation se fait **au navigateur** : on
  rejoue les scénarios des phases précédentes sur les données de démonstration.

### Dépendances à installer

Aucune : `doctrine/doctrine-fixtures-bundle` est installé depuis la phase 01.

### Commandes

```bash
make fixtures    # recharge les données de démonstration (vide d'abord la base)
make reset-db    # supprime, recrée, migre et recharge la base
make lint        # les 4 vérifications
```

### Modules à développer

- `AppFixtures` : 1 admin, 2 parents, 4 enfants, des journaux sur plusieurs
  semaines (dont celui du jour), une quinzaine de contenus dont un par règle
  déclencheuse.
- Cibles `fixtures`, `reset-db` et `lint` dans le `Makefile` (`migrate` existe
  depuis la phase 02), et une cible `install` complétée.
- README : installation, comptes de démonstration, commande pour supprimer le
  journal du jour, problèmes fréquents.

### Résultat attendu

`make install` remonte un environnement complet avec les données de
démonstration, et `make lint` passe au vert.

### Scénario de test manuel

1. Lancer `make reset-db` pour repartir d'une base propre remplie par les fixtures.
2. Se connecter avec chacun des trois comptes de démonstration (admin, parent, enfant).
3. Ouvrir le tableau de bord parent : les journaux des dernières semaines doivent être visibles dans la courbe des 30 jours.
4. Supprimer le journal du jour, puis rejouer le journal en 2 étapes en tant qu'enfant.
5. Lancer `make lint`.
6. **Résultat attendu** : les trois connexions fonctionnent, le graphique est rempli, le journal se rejoue sans erreur et les quatre linters affichent `[OK]`.

### Formation

📘 [Formation Phase 12](./formation/phase-12.md) — les concepts à comprendre
avant (ou pendant) l'implémentation.

### Prompt Claude Code

👉 [Prompt Phase 12](./prompts/phase-12.md)

---

## Après le parcours

- Reprenez le fichier `CLAUDE.md` du dépôt de référence : il résume les conventions, les
  pièges connus et l'historique des décisions.
- Relisez les [leçons de formation](./formation/README.md) : après avoir
  construit l'application, les concepts se lisent différemment.
- Comparez votre code avec ce dépôt : les écarts sont des occasions
  d'apprendre, pas des erreurs.
- Évolutions possibles, à ne lancer qu'une fois le parcours terminé :
  récupération de mot de passe, historique des douleurs côté parent, export
  des données, tests automatisés (PHPUnit), mise en production.
