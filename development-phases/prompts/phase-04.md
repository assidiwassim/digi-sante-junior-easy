# Prompt Claude Code — Phase 04 : Authentification, rôles, inscription et connexion parent

> Copiez tout ce qui suit dans Claude Code, à la racine du projet.

---

## Contexte du projet

**Digi-Santé Junior** : application Symfony de suivi du bien-être numérique des
enfants de 8 à 14 ans. Trois rôles **sans hiérarchie** — un administrateur
n'est ni parent ni enfant :

| Rôle | Espace | Connexion |
|---|---|---|
| `ROLE_ADMIN` | `/admin` | email sur `/login` |
| `ROLE_PARENT` | `/parent` | email sur `/login` |
| `ROLE_CHILD` | `/enfant` | identifiant sur `/connexion-enfant` (phase 06) |

Déjà en place : Docker et **tous les paquets du projet** (phase 01), les cinq
entités dont `User` (email et username nullables, rôles en JSON et en
constantes, mot de passe haché, contraintes de validation) et son
`UserRepository` encore vide (phase 02), gabarit `base.html.twig`, charte et
page d'accueil (phase 03).

Stack : PHP 8.4, Symfony 7.4, Doctrine ORM 3, Twig, Bootstrap 5.3 par CDN,
MySQL 8, Docker.

Je débute avec Symfony : code simple, en français, sans sur-ingénierie.

## Objectif de la phase

Permettre à un **parent** de créer son compte et de se connecter, protéger les
trois espaces par rôle, et rediriger chaque utilisateur connecté vers son
espace.

## Avant de coder

1. Lis `src/Entity/User.php`, `src/Repository/UserRepository.php`,
   `src/Controller/HomeController.php` et `templates/base.html.twig`.
2. Tous les paquets sont installés depuis la phase 01 (Security, Form,
   Validator, Translation) : n'en installe aucun ; s'il en manque un,
   signale-le moi. Ne recrée pas l'entité `User`.
3. Dis-moi ce que tu comptes modifier dans l'existant avant de le faire.

## À implémenter

### 1. Configuration de la sécurité

`config/packages/security.yaml` :

- `password_hashers` : algorithme `auto` ;
- `providers` : un provider **entity** sur `App\Entity\User`, **sans** option
  `property` (la phase 06 chargera l'utilisateur par email *ou* par identifiant) ;
  ⚠️ sans `property`, Symfony exige que `UserRepository` implémente
  `UserLoaderInterface`, **dès maintenant** — sinon toute connexion finit en
  erreur 500. Écris donc `loadUserByIdentifier(string $identifier): ?User`, qui
  cherche pour l'instant **par email** (valeur en minuscules, sans espaces
  autour) ; la phase 06 l'étendra à l'identifiant des enfants ;
- firewall `dev` désactivé pour `^/(_profiler|_wdt|css|js)/` ;
- firewall `main` avec :
  - `form_login` : `login_path` et `check_path` sur la route `app_login`,
    `enable_csrf: true`, `default_target_path: app_home` et
    `always_use_default_target_path: true` ;
  - `logout` vers la page d'accueil ;
  - `remember_me` (secret `%kernel.secret%`, 7 jours) ;
- `access_control`, dans cet ordre : `^/admin` → `ROLE_ADMIN`,
  `^/parent` → `ROLE_PARENT`, `^/enfant` → `ROLE_CHILD`.

⚠️ **Aucune hiérarchie de rôles** : ne configure pas `role_hierarchy`.

⚠️ Protection **CSRF adossée à la session** : la recette de Symfony 7.2+ génère
un `config/packages/csrf.yaml` en variante « stateless » (`stateless_token_ids`).
**Remplace** son contenu par la version classique, plus simple à comprendre :

```yaml
framework:
    csrf_protection:
        enabled: true
    form:
        csrf_protection:
            enabled: true
```

Messages de Symfony en français : `config/packages/translation.yaml` avec
`default_locale: fr` et `fallbacks: [fr]` (sinon « Invalid credentials. »
s'affiche en anglais sur la page de connexion).

### 2. `SecurityController`

- `/login` (GET + POST, route `app_login`) : affiche le formulaire de connexion
  **parent et administrateur** (email + mot de passe). Utilise
  `AuthenticationUtils` pour récupérer la dernière erreur et le dernier
  identifiant saisi. Un utilisateur déjà connecté est redirigé vers l'accueil.
- `/logout` (route `app_logout`) : méthode vide, avec un commentaire expliquant
  que Symfony l'intercepte.
- `/inscription` (GET + POST, route `app_register`) : création d'un compte
  **parent**.

⚠️ **N'écris pas d'authenticator maison** : la vérification du mot de passe est
faite par `form_login`.

### 3. Formulaire d'inscription

`InscriptionType` lié à `User` :

| Champ | Type | Règles |
|---|---|---|
| `email` | `EmailType` | obligatoire **dans le formulaire** (l'entité l'autorise vide pour les enfants) |
| `pays`, `ville` | `TextType` | facultatifs |
| `plainPassword` | `RepeatedType` non mappé | 6 caractères minimum, saisi deux fois |
| `conditions` | `CheckboxType` non mappé | doit être cochée (`Assert\IsTrue`) |

Le contrôleur hache le mot de passe avec `UserPasswordHasherInterface`, attribue
`ROLE_PARENT`, enregistre, ajoute le **message flash** `success` « Votre compte
est créé ! Connectez-vous pour ajouter vos enfants. » et redirige vers `/login`.

Messages de validation **exactement** comme ci-dessous :

- `email` : `NotBlank` « Merci de saisir votre email. » (l'entité ajoute
  « Cette adresse email n'est pas valide. » et « Cette adresse email est déjà
  utilisée. ») ;
- `plainPassword` : `invalid_message` « Les deux mots de passe ne correspondent
  pas. », `NotBlank` « Merci de choisir un mot de passe. »,
  `Length(min: 6, max: 4096)` « Le mot de passe doit contenir au moins
  {{ limit }} caractères. » ;
- `conditions` : `IsTrue` « Vous devez accepter les conditions pour créer un
  compte. »

### 4. Redirection par rôle

`HomeController::index()` : si l'utilisateur est connecté, le rediriger vers son
espace ; sinon afficher la page d'accueil publique. Comme la connexion renvoie
toujours ici, c'est **ce seul endroit** qui décide où va chacun.

Crée des pages d'attente minimales (un titre, un message « bientôt » et un lien
de déconnexion) afin que la redirection soit vérifiable dès maintenant :

- `/parent`, route `parent_dashboard`, dans `Parent\DashboardController` (la
  phase 10 la remplacera par le vrai tableau de bord, même nom de route) ;
- `/admin`, route `admin_accueil`, dans `Admin\AccueilController` (la phase 08
  la remplacera).

`ROLE_ADMIN` → `admin_accueil`, `ROLE_PARENT` → `parent_dashboard`. L'enfant
sera ajouté en phase 06, quand sa route `enfant_accueil` existera : ne redirige
jamais vers une route qui n'existe pas encore (erreur 500).

### 5. Gabarits

- `templates/security/login.html.twig` : page sobre, sans barre de navigation,
  avec une case « Se souvenir de moi », un lien vers l'inscription et un bouton
  vers la future connexion enfant, qui pointe vers `#` pour l'instant (la route
  `app_enfant_login` n'existe qu'à partir de la phase 06 : un `path()` vers elle
  provoquerait une erreur 500).
- `templates/home/index.html.twig` : le bouton « Je suis un parent » pointe
  désormais vers `path('app_login')` ; le bouton « Je suis un enfant » reste
  sur `#` jusqu'en phase 06.
- `templates/security/inscription.html.twig`.
- Thème de formulaire Bootstrap (`bootstrap_5_layout.html.twig`) dans
  `config/packages/twig.yaml`.
- Toujours `{{ form_errors(form) }}` **juste après** `form_start()`.

## Contraintes techniques et architecturales

- Injection des dépendances **en argument de l'action** du contrôleur
  (`EntityManagerInterface $entityManager`, etc.).
- Contrôleurs simples : lire la requête, appeler un formulaire ou Doctrine,
  rendre un gabarit.
- Mots de passe **jamais** stockés ni affichés en clair, **jamais** générés
  automatiquement.
- Routes en attributs `#[Route]`, noms préfixés (`app_…` pour le public).
- Contraintes permanentes sur l'entité (déjà posées en phase 02, elles
  s'appliquent maintenant au formulaire) ; contraintes propres au formulaire
  (champ non mappé, email obligatoire) dans le `*Type`.

## Commandes attendues

```bash
docker compose exec app php bin/console debug:router
docker compose exec app php bin/console lint:yaml config
docker compose exec app php bin/console cache:clear
```

Pour créer un administrateur de test (aucune interface ne le fait) : inscris
**d'abord** le compte `admin@digisante.local` / `admin123` sur `/inscription`,
puis change son rôle :

```bash
docker compose exec app php bin/console dbal:run-sql "UPDATE users SET roles = '[\"ROLE_ADMIN\"]' WHERE email = 'admin@digisante.local'"
```

## Ce qui n'est PAS dans cette phase

- Pas de connexion enfant ni de page `/connexion-enfant` (phase 06).
- Pas de gestion des profils enfants (phase 05) ; aucune entité ni migration
  nouvelle (le modèle est complet depuis la phase 02).
- Aucune installation de paquet (tout est installé depuis la phase 01).
- Pas de vrai contenu dans `/parent` et `/admin` : juste une page d'attente.
- Pas de récupération de mot de passe par email : **hors périmètre du projet**.

## Scénario de test manuel

1. Ouvrir `/inscription` et créer un compte parent (email valide, mot de passe de 6 caractères minimum, case cochée).
2. Vérifier le message de confirmation, puis se connecter sur `/login` avec ce compte.
3. Vérifier la redirection automatique vers `/parent`.
4. Se déconnecter, puis saisir directement `/parent` dans la barre d'adresse.
5. Créer le compte administrateur : **d'abord** s'inscrire via `/inscription` avec `admin@digisante.local` / `admin123`, **puis** le promouvoir avec la commande `dbal:run-sql` ci-dessus. Si vous étiez connecté avec ce compte, Symfony vous **déconnecte** (rôle changé) : se reconnecter.
6. **Résultat attendu** : inscription et connexion fonctionnent, la redirection par rôle amène sur `/parent`, l'accès déconnecté à `/parent` renvoie vers `/login`, et l'admin est redirigé vers `/admin`.

## Critères de validation

- [ ] Un mot de passe trop court, un email déjà pris ou la case non cochée
      affichent un message d'erreur clair, en français, sans erreur 500.
- [ ] Le mot de passe est haché en base (vérifiable dans phpMyAdmin).
- [ ] `/admin` renvoie 403 pour un parent connecté.
- [ ] Un mauvais mot de passe affiche « Identifiants invalides. » (en français).
- [ ] L'administrateur connecté arrive sur sa page d'attente `/admin`.
- [ ] `lint:yaml config` affiche `[OK]`.
- [ ] Aucun authenticator maison dans `src/`.

## Enfin

- N'écris **aucun test automatisé** : je valide au navigateur.
- Termine en m'expliquant en quelques lignes le chemin d'une connexion :
  formulaire → firewall → provider → hachage → session.
