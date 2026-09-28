# Prompt Claude Code — Phase 01 : Environnement Docker, squelette Symfony et paquets du projet

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

Mettre en place l'environnement Docker (application, base MySQL et phpMyAdmin)
et le squelette Symfony, installer **tous les paquets dont le projet aura
besoin**, jusqu'à obtenir une page qui répond dans le navigateur sur
`http://localhost:8081`. Les phases suivantes n'auront plus aucun paquet à
installer : elles configureront seulement ce dont elles ont besoin.

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
- `compose.yaml` avec trois services :
  - `app` : construit depuis le `Dockerfile`, port `8081:80`, volume `./:/app`,
    variable `DATABASE_URL` pointant sur le service `database`, et dépendance
    `condition: service_healthy` ;
  - `database` : image `mysql:8.0.36`, port `3308:3306`, base `digisante_junior`,
    utilisateur et mot de passe `digisante`, mot de passe root `root`, variable
    `TZ: Europe/Paris`, volume nommé pour les données, `healthcheck` avec
    `mysqladmin ping` ;
  - `phpmyadmin` : image `phpmyadmin:5.2`, port `8082:80`, `PMA_HOST: database`,
    `PMA_PORT: 3306`, `PMA_USER` et `PMA_PASSWORD` renseignés (`digisante`) pour
    que la connexion soit **automatique** (aucun écran de login),
    `TZ: Europe/Paris`, `depends_on` sur `database` avec
    `condition: service_healthy`. Note dans un commentaire que c'est un outil
    **de développement uniquement** : il permettra de regarder la base sans
    écrire de SQL dès la phase 02.
- Nom de projet compose `digisante-junior` et noms de conteneurs explicites.
  ⚠️ Si le dépôt de référence (ou un autre projet utilisant ce nom) existe sur
  la même machine, choisis un autre nom de projet compose, par exemple
  `digisante-junior-etudiant`, et d'autres noms de conteneurs, et signale-le
  moi : deux dossiers qui partagent le même nom de projet compose partagent
  aussi le volume MySQL.

### 2. Squelette Symfony

- Installer le squelette **Symfony 7.4** dans le conteneur (`symfony/skeleton`),
  avec Flex. ⚠️ `composer create-project` refuse un dossier qui n'est pas vide
  (le `Dockerfile` et `compose.yaml` y sont déjà) : crée le squelette dans un
  dossier temporaire du conteneur, puis copie-le à la racine sans écraser les
  fichiers existants (voir les commandes attendues).
- Le squelette contient déjà `symfony/console`, `dotenv`, `flex`, `runtime`,
  `framework-bundle` et `yaml`.
- Dans `composer.json`, régler `extra.symfony.docker` à `false` **avant**
  d'installer le moindre paquet : sinon les recettes de Doctrine ajoutent leur
  propre service de base de données et écrasent `compose.yaml`.
- Créer `src/Controller/HomeController.php` avec une route `/` nommée
  `app_home`, en attribut `#[Route]`, qui renvoie pour l'instant une réponse
  texte simple (« Digi-Santé Junior — bientôt disponible »).

### 3. Paquets du projet

Installer **en deux commandes** tous les paquets du projet (voir les commandes
attendues), puis m'expliquer en une ligne à quoi sert chacun et dans quelle
phase il servira :

| Paquet | Rôle | Utilisé à partir de |
|---|---|---|
| `twig` (alias Flex de `symfony/twig-pack`, dépaqueté en `symfony/twig-bundle` + `twig/twig`), `symfony/asset` | gabarits HTML, liens vers `public/css` | phase 03 |
| `symfony/orm-pack` | Doctrine ORM, migrations, connexion MySQL | phase 02 |
| `symfony/security-bundle` | interfaces de `User` (phase 02), connexion et rôles (phase 04) | phases 02 et 04 |
| `symfony/form` | formulaires | phase 04 |
| `symfony/validator` | contraintes `#[Assert\…]` sur les entités (phase 02), validation des formulaires (phase 04) | phases 02 et 04 |
| `symfony/translation` | messages de Symfony en français | phase 04 |
| `twig/extra-bundle`, `twig/intl-extra` | date affichée en français | phase 06 |
| `symfony/maker-bundle` (dev) | générateurs `make:entity`, `make:migration`, `make:voter` | phase 02 |
| `symfony/profiler-pack`, `symfony/debug-bundle` (dev) | barre de debug, profiler, `dump()` | dès la phase 02 (barre visible en phase 03) |
| `doctrine/doctrine-fixtures-bundle` (dev) | données de démonstration | phase 12 |

Les recettes Flex créent des fichiers de configuration (`doctrine.yaml`,
`security.yaml`, `csrf.yaml`, `translation.yaml`, `twig.yaml`,
`templates/base.html.twig`…) : **laisse-les tels quels**, chaque phase
configurera ce dont elle a besoin. Vérifie que `compose.yaml` n'a pas été
modifié par ces installations.

La barre de debug n'apparaît pas encore : c'est normal, elle ne s'injecte que
dans une page qui contient `</body>`, et `HomeController` renvoie une simple
phrase.

### 4. Confort de travail

- Un `Makefile` avec au minimum : `help` (cible par défaut, qui liste les
  commandes), `install`, `start`, `stop`, `bash`, `cc`.
- Chaque cible doit afficher la commande complète qu'elle exécute, pour que je
  puisse la taper moi-même.
- Un `README.md` court : prérequis, installation en 2 commandes, adresses de
  l'application et de phpMyAdmin.
- Un `.gitignore` adapté (`/vendor/`, `/var/`, `.env.local`).

## Contraintes techniques et architecturales

- **Rien ne tourne sur ma machine** : toute commande PHP passe par
  `docker compose exec app …`.
- Routes en attributs `#[Route]`, jamais en YAML.
- Noms de routes préfixés : `app_…` pour le public.
- Commentaires utiles uniquement : expliquer **pourquoi**, pas répéter le code.
- Ne crée aucun fichier dont je n'ai pas besoin dans cette phase (les fichiers
  générés par les recettes Flex sont attendus).

## Commandes attendues

```bash
docker compose up -d --build
docker compose exec app sh -c 'composer create-project symfony/skeleton:"7.4.*" /tmp/skeleton && cp -rn /tmp/skeleton/. /app/ && rm -rf /tmp/skeleton'
docker compose exec app composer install
# après avoir réglé extra.symfony.docker à false dans composer.json :
docker compose exec app composer require twig symfony/asset symfony/orm-pack symfony/security-bundle symfony/form symfony/validator symfony/translation twig/extra-bundle twig/intl-extra
docker compose exec app composer require --dev symfony/maker-bundle symfony/profiler-pack symfony/debug-bundle doctrine/doctrine-fixtures-bundle
docker compose exec app composer show
docker compose exec app php bin/console debug:router
docker compose ps
```

## Ce qui n'est PAS dans cette phase

- Pas de configuration de Doctrine, pas d'entité, pas de migration (phase 02).
- Pas de gabarit HTML ni de charte graphique (phase 03).
- Pas de configuration de la sécurité ni de formulaire (phase 04).
- Aucune modification des fichiers de configuration générés par les recettes.

## Scénario de test manuel

1. Lancer `docker compose up -d --build`, puis `docker compose exec app composer install`.
2. Ouvrir `http://localhost:8081` dans le navigateur et vérifier que la réponse du contrôleur s'affiche (ni 404, ni 500).
3. Ouvrir `http://localhost:8081/page-qui-nexiste-pas`.
4. Ouvrir phpMyAdmin sur `http://localhost:8082`.
5. Lancer `docker compose exec app composer show` et retrouver les paquets installés.
6. **Résultat attendu** : la page d'accueil répond, l'URL inconnue affiche la page d'erreur 404 de Symfony, phpMyAdmin s'ouvre **sans écran de connexion** et montre la base `digisante_junior` (vide), `docker compose ps` montre les trois conteneurs démarrés, et `compose.yaml` n'a pas été modifié par les installations.

## Critères de validation

- [ ] `docker compose ps` : `app`, `database` et `phpmyadmin` démarrés, `database` *healthy*
      (`app` peut aussi afficher *healthy* : l'image FrankenPHP a son propre healthcheck).
- [ ] `http://localhost:8081` répond en 200 avec le texte du contrôleur.
- [ ] `http://localhost:8082` ouvre phpMyAdmin sans demander de mot de passe.
- [ ] `composer show` liste les paquets du tableau ci-dessus. ⚠️ Flex
      **dépaquète** les « packs » : `twig` apparaît sous `symfony/twig-bundle`
      + `twig/twig`, `symfony/orm-pack` sous `doctrine/orm`,
      `doctrine/doctrine-bundle` et `doctrine/doctrine-migrations-bundle`, et
      `symfony/profiler-pack` sous `symfony/web-profiler-bundle`
      + `symfony/stopwatch` (c'est aussi ce qu'on lit dans `composer.json`).
- [ ] `compose.yaml` n'a pas été modifié par les recettes Flex.
- [ ] `php bin/console debug:router` liste la route `app_home`.
- [ ] `make` (sans argument) affiche la liste des raccourcis.
- [ ] Le projet ne contient que les fichiers nécessaires à cette phase.

## Enfin

- N'écris **aucun test automatisé** dans cette phase : je valide au navigateur.
- Termine en me résumant en 5 lignes maximum ce qui a été créé et comment
  redémarrer l'environnement demain matin.
