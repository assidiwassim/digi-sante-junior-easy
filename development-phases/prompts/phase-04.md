# Prompt Claude Code — Phase 04 : Inscription, connexion et rôles

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

L'entité `User` existe déjà (email et username nullables, rôles en JSON, mot de
passe haché). Stack : PHP 8.4, Symfony 7.4, Doctrine ORM 3, Twig, Bootstrap 5.3
par CDN, MySQL 8, Docker.

Je débute avec Symfony : code simple, en français, sans sur-ingénierie.

## Objectif de la phase

Permettre à un **parent** de créer son compte et de se connecter, protéger les
trois espaces par rôle, et rediriger chaque utilisateur connecté vers son
espace.

## Avant de coder

1. Lis `src/Entity/User.php`, `src/Repository/UserRepository.php`,
   `src/Controller/HomeController.php` et `templates/base.html.twig`.
2. Vérifie quels paquets sont déjà installés avant d'en ajouter.
3. Dis-moi ce que tu comptes modifier dans l'existant avant de le faire.

## À implémenter

### 1. Configuration de la sécurité

`config/packages/security.yaml` :

- `password_hashers` : algorithme `auto` ;
- `providers` : un provider **entity** sur `App\Entity\User`, **sans** option
  `property` (la phase 06 chargera l'utilisateur par email *ou* par identifiant) ;
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

⚠️ Garde la protection **CSRF adossée à la session** (`config/packages/csrf.yaml`) :
ne bascule pas sur la variante « stateless ».

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
`ROLE_PARENT`, enregistre, ajoute un **message flash** de succès et redirige
vers `/login`.

Messages de validation **en français, écrits pour l'utilisateur** :
« Merci de saisir votre email. », « Les deux mots de passe ne correspondent
pas. », « Vous devez accepter les conditions pour créer un compte. »

### 4. Redirection par rôle

`HomeController::index()` : si l'utilisateur est connecté, le rediriger vers son
espace (`admin_…`, `parent_…`, `enfant_…` selon le rôle) ; sinon afficher la
page d'accueil publique. Comme la connexion renvoie toujours ici, c'est **ce
seul endroit** qui décide où va chacun.

Crée des pages d'attente minimales pour `/parent` et `/admin` (un titre et un
message « bientôt ») afin que la redirection soit vérifiable dès maintenant.

### 5. Gabarits

- `templates/security/login.html.twig` : page sobre, sans barre de navigation,
  avec une case « Se souvenir de moi », un lien vers l'inscription et un bouton
  vers la future connexion enfant.
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
- Contraintes permanentes sur l'entité ; contraintes propres au formulaire
  (champ non mappé, email obligatoire) dans le `*Type`.

## Commandes attendues

```bash
docker compose exec app composer require symfony/security-bundle symfony/form symfony/validator
docker compose exec app php bin/console debug:router
docker compose exec app php bin/console lint:yaml config
docker compose exec app php bin/console cache:clear
```

Pour créer un administrateur de test (aucune interface ne le fait) :

```bash
docker compose exec app php bin/console dbal:run-sql "UPDATE users SET roles = '[\"ROLE_ADMIN\"]' WHERE email = 'admin@digisante.local'"
```

## Ce qui n'est PAS dans cette phase

- Pas de connexion enfant ni de page `/connexion-enfant` (phase 06).
- Pas d'entité `Enfant` ni de gestion des profils (phase 05).
- Pas de vrai contenu dans `/parent` et `/admin` : juste une page d'attente.
- Pas de récupération de mot de passe par email : **hors périmètre du projet**.

## Scénario de test manuel

1. Ouvrir `/inscription` et créer un compte parent (email valide, mot de passe de 6 caractères minimum, case cochée).
2. Vérifier le message de confirmation, puis se connecter sur `/login` avec ce compte.
3. Vérifier la redirection automatique vers `/parent`.
4. Se déconnecter, puis saisir directement `/parent` dans la barre d'adresse.
5. **Résultat attendu** : inscription et connexion fonctionnent, la redirection par rôle amène sur `/parent`, et l'accès déconnecté à `/parent` renvoie vers `/login`.

## Critères de validation

- [ ] Un mot de passe trop court, un email déjà pris ou la case non cochée
      affichent un message d'erreur clair, en français, sans erreur 500.
- [ ] Le mot de passe est haché en base (vérifiable dans phpMyAdmin).
- [ ] `/admin` renvoie 403 pour un parent connecté.
- [ ] `lint:yaml config` affiche `[OK]`.
- [ ] Aucun authenticator maison dans `src/`.

## Enfin

- N'écris **aucun test automatisé** : je valide au navigateur.
- Termine en m'expliquant en quelques lignes le chemin d'une connexion :
  formulaire → firewall → provider → hachage → session.
