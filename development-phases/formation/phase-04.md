# Formation — Phase 04 : Authentification, rôles, inscription et connexion parent

## 1. Objectifs pédagogiques

À la fin de cette leçon, vous devez être capable de :

- expliquer ce qu'est un **firewall**, un **provider** et un **rôle** dans
  Symfony Security ;
- décrire ce qui se passe, étape par étape, quand quelqu'un se connecte ;
- expliquer pourquoi un mot de passe est **haché** et jamais chiffré ni stocké
  en clair ;
- construire un **formulaire Symfony** (`*Type`) et le traiter dans un
  contrôleur ;
- comprendre la différence entre un champ **mappé** et **non mappé** ;
- expliquer ce qu'est une attaque **CSRF** et comment Symfony la bloque ;
- protéger des URL par rôle avec `access_control`.

## 2. Prérequis

- Phases 01 à 03 terminées : tous les paquets sont installés (phase 01),
  l'entité `User` et son `UserRepository` existent en base depuis la phase 02,
  et `base.html.twig` affiche les pages (phase 03).
- Comprendre ce qu'est un cookie et une session HTTP (les bases suffisent).

---

## 3. Concepts à apprendre

### Concept 1 — Authentification et autorisation

Deux notions souvent confondues :

| | Question posée | Exemple |
|---|---|---|
| **Authentification** | « Qui êtes-vous ? » | se connecter avec email + mot de passe |
| **Autorisation** | « Avez-vous le droit ? » | un parent ne peut pas ouvrir `/admin` |

**Dans ce projet.** L'authentification est faite par `form_login`.
L'autorisation passe par `access_control` (par URL) en phase 04, puis par un
**voter** (par objet) en phase 05.

---

### Concept 2 — Le firewall

**Pourquoi ?** Il faut un endroit qui décide, pour chaque requête, si un
utilisateur est connecté et comment il peut le devenir.

**Comment ça fonctionne ?** Un **firewall** est une zone de l'application avec
ses règles d'authentification. Il n'a rien à voir avec un pare-feu réseau.

```yaml
firewalls:
    dev:
        pattern: ^/(_profiler|_wdt|css|js)/
        security: false          # zone technique : aucune sécurité
    main:
        lazy: true               # ne charge l'utilisateur que si on en a besoin
        provider: app_user_provider
        form_login:
            login_path: app_login
            check_path: app_login
```

**Dans ce projet.** Un seul firewall utile, `main`, couvre tout le site. Les
trois espaces sont ensuite distingués par leurs **rôles**, pas par des firewalls
séparés.

---

### Concept 3 — Le provider

**Pourquoi ?** Le firewall doit pouvoir retrouver un utilisateur à partir de ce
qu'il a saisi.

**Comment ça fonctionne ?** Le **provider** dit où chercher : ici, dans la table
`users` via Doctrine.

```yaml
providers:
    app_user_provider:
        entity:
            class: App\Entity\User
```

⚠️ Remarquez l'**absence** d'option `property`. Normalement on écrirait
`property: email`. Ici on ne le fait pas, car en phase 06 un enfant se
connectera avec son **identifiant** : le repository prendra en charge la
recherche sur les deux colonnes.

Conséquence **immédiate** : sans `property`, Symfony demande au repository de
trouver l'utilisateur. `UserRepository` doit donc implémenter
`UserLoaderInterface` **dès cette phase**, sinon toute connexion renvoie une
erreur 500. Pour l'instant, la recherche se fait par **email** ; la phase 06
étendra la même méthode à l'identifiant.

```php
use Symfony\Bridge\Doctrine\Security\User\UserLoaderInterface;

class UserRepository extends ServiceEntityRepository implements UserLoaderInterface
{
    public function loadUserByIdentifier(string $identifier): ?User
    {
        // Même normalisation que setEmail() : minuscules, sans espaces autour.
        return $this->findOneBy(['email' => mb_strtolower(trim($identifier))]);
    }
}
```

---

### Concept 4 — Le hachage des mots de passe

**Pourquoi ?** Si votre base fuite, les mots de passe ne doivent pas être
lisibles. Et vous n'avez **jamais** besoin de lire un mot de passe : seulement
de vérifier qu'il correspond.

**Comment ça fonctionne ?**

- **chiffrer** = réversible avec une clé. Mauvaise idée ici.
- **hacher** = irréversible. On recalcule l'empreinte à chaque connexion et on
  compare.

```yaml
password_hashers:
    Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface: 'auto'
```

`auto` choisit l'algorithme recommandé du moment (aujourd'hui bcrypt ou argon2)
et ajoute un **sel** aléatoire : deux comptes avec le même mot de passe ont des
empreintes différentes.

```php
$parent->setPassword($passwordHasher->hashPassword($parent, $motDePasse));
```

**Dans ce projet.** Règle absolue : mots de passe **jamais** stockés ni affichés
en clair, **jamais** générés automatiquement. Le parent choisit celui de son
enfant (phase 05).

---

### Concept 5 — Les rôles et `access_control`

**Pourquoi ?** Chaque espace doit être réservé à son public.

**Comment ça fonctionne ?** Un utilisateur porte une liste de rôles.
`access_control` associe un motif d'URL à un rôle requis.

```yaml
access_control:
    - { path: ^/admin, roles: ROLE_ADMIN }
    - { path: ^/parent, roles: ROLE_PARENT }
    - { path: ^/enfant, roles: ROLE_CHILD }
```

⚠️ Deux pièges :

1. **seule la première règle qui correspond s'applique** : l'ordre compte ;
2. sans hiérarchie configurée, `ROLE_ADMIN` **n'inclut pas** `ROLE_PARENT`.

**Dans ce projet.** C'est voulu : les trois rôles sont **indépendants**. Un
administrateur n'est ni parent ni enfant, il reçoit donc un **403** sur
`/parent`. Ne configurez pas `role_hierarchy`.

---

### Concept 6 — Les formulaires Symfony

**Pourquoi ?** Un formulaire HTML « à la main », c'est : afficher les champs,
relire `$_POST`, valider, réafficher les erreurs, garder les valeurs saisies,
protéger du CSRF. Symfony fait tout cela.

**Comment ça fonctionne ?** Une classe `*Type` décrit les champs. Le contrôleur
crée le formulaire, lui passe la requête, et regarde s'il est valide.

```php
$form = $this->createForm(InscriptionType::class, $parent);
$form->handleRequest($request);   // lit la requête et remplit l'objet

if ($form->isSubmitted() && $form->isValid()) {
    // ici, $parent contient déjà les données saisies et validées
}
```

**Champ mappé ou non mappé ?**

- **mappé** (par défaut) : le champ correspond à une propriété de l'entité ;
- **non mappé** (`'mapped' => false`) : le champ existe seulement dans le
  formulaire.

```php
->add('plainPassword', RepeatedType::class, [
    'type' => PasswordType::class,
    'mapped' => false,        // « password » contient le mot de passe HACHÉ,
                              // le mot de passe en clair ne doit jamais y aller
    'constraints' => [new Assert\Length(min: 6)],
])
```

C'est exactement pour cela que `plainPassword` n'est pas une propriété de
`User` : le contrôleur le récupère, le hache, puis appelle `setPassword()`.

---

### Concept 7 — La validation

**Pourquoi ?** Ne jamais faire confiance à ce qui vient du navigateur : un champ
`required` en HTML se contourne en trois secondes.

**Comment ça fonctionne ?** Des contraintes déclarées, vérifiées côté serveur au
moment du `isValid()`.

**Où les écrire ?** Règle du projet :

| Type de règle | Où | Exemple |
|---|---|---|
| Règle **permanente** de la donnée | sur l'**entité** | format d'email, unicité |
| Règle propre à **un formulaire** | dans le `*Type` | email obligatoire à l'inscription |
| Champ **non mappé** | dans le `*Type` | `plainPassword`, `conditions` |

**Les contraintes de la phase 02 prennent vie.** En phase 02, vous avez posé des
`#[Assert\…]` et un `#[UniqueEntity]` sur les entités, sans rien voir se passer :
une contrainte ne fait rien toute seule. C'est `$form->isValid()` qui appelle le
**validateur** : il lit les contraintes de l'entité liée au formulaire, **et**
celles déclarées dans le `*Type`, puis attache chaque message d'erreur au champ
concerné. Inscrivez-vous deux fois avec le même email : le message « Cette
adresse email est déjà utilisée. » vient directement de `#[UniqueEntity]` sur
`User`. Les phases suivantes profitent du même mécanisme : âge 8-14 ans et
limite d'écran (`Enfant`, phase 05), URL et titre (`ContenuBienEtre`, phase 08).

---

### Concept 8 — La protection CSRF

**Pourquoi ?** Sans elle, un site malveillant peut faire exécuter une action à
votre insu : vous êtes connecté sur l'application, une page piégée envoie un
formulaire vers `/parent/enfants/3/supprimer`, et votre navigateur y joint
gentiment votre cookie de session.

**Comment ça fonctionne ?** Le serveur place un **jeton** unique dans chaque
formulaire, et le vérifie à la réception. Le site malveillant ne peut pas le
deviner.

Avec les formulaires Symfony, c'est **automatique**. Pour un bouton hors
formulaire (la suppression, phase 05), on le fait à la main.

**Dans ce projet.** Le jeton est **adossé à la session**. La recette Symfony
7.2+ génère pourtant la variante **stateless** (`stateless_token_ids`) : il faut
**remplacer** tout le contenu de `config/packages/csrf.yaml` par :

```yaml
framework:
    csrf_protection:
        enabled: true
    form:
        csrf_protection:
            enabled: true
```

Ne revenez jamais à la variante « stateless ».

---

### Concept 9 — Les messages flash

**Pourquoi ?** Après une action réussie, on redirige (pour éviter qu'un F5
renvoie le formulaire). Mais on veut quand même afficher « Compte créé ! ».

**Comment ça fonctionne ?** Un message est stocké en session, affiché **une
seule fois**, puis effacé.

```php
$this->addFlash('success', 'Votre compte est créé ! Connectez-vous pour ajouter vos enfants.');
return $this->redirectToRoute('app_login');
```

Le gabarit `base.html.twig` (phase 03) les affiche déjà pour toutes les pages.

---

## 4. Explications avec exemples

### Le contrôleur de connexion : le moins de code possible

```php
#[Route('/login', name: 'app_login', methods: ['GET', 'POST'])]
public function login(AuthenticationUtils $authenticationUtils): Response
{
    if ($this->getUser()) {                       // déjà connecté ?
        return $this->redirectToRoute('app_home');
    }

    return $this->render('security/login.html.twig', [
        'last_username' => $authenticationUtils->getLastUsername(),
        'error' => $authenticationUtils->getLastAuthenticationError(),
    ]);
}
```

Remarquez ce qui **n'est pas** là : aucune comparaison de mot de passe, aucune
requête. `form_login` a intercepté le POST avant d'arriver ici. Le contrôleur ne
sert qu'à **afficher** le formulaire et l'éventuelle erreur.

Règle du projet : **pas d'authenticator maison**.

### L'inscription, étape par étape

```php
$parent = new User();
$parent->setRoles([User::ROLE_PARENT]);          // 1. on fixe le rôle

$form = $this->createForm(InscriptionType::class, $parent);
$form->handleRequest($request);                  // 2. on lit la requête

if ($form->isSubmitted() && $form->isValid()) {  // 3. on valide
    $motDePasse = $form->get('plainPassword')->getData();          // champ non mappé
    $parent->setPassword($passwordHasher->hashPassword($parent, $motDePasse)); // 4. hachage

    $entityManager->persist($parent);             // 5. enregistrement
    $entityManager->flush();

    $this->addFlash('success', 'Votre compte est créé !');          // 6. message
    return $this->redirectToRoute('app_login');   // 7. redirection
}
```

Les dépendances (`Request`, `EntityManagerInterface`, `UserPasswordHasherInterface`)
sont déclarées **en arguments de l'action** : Symfony les fournit
automatiquement. C'est la convention du projet.

### La redirection par rôle, à un seul endroit

```php
#[Route('/', name: 'app_home', methods: ['GET'])]
public function index(): Response
{
    if ($this->isGranted(User::ROLE_ADMIN))  { return $this->redirectToRoute('admin_accueil'); }
    if ($this->isGranted(User::ROLE_PARENT)) { return $this->redirectToRoute('parent_dashboard'); }

    return $this->render('home/index.html.twig');
}
```

`admin_accueil` (`/admin`, `Admin\AccueilController`) et `parent_dashboard`
(`/parent`, `Parent\DashboardController`) sont les deux **pages d'attente** de
cette phase. L'enfant sera ajouté en phase 06, quand `enfant_accueil` existera :
on ne redirige **jamais** vers une route qui n'existe pas encore (erreur 500).

Pourquoi ici ? Parce que `security.yaml` renvoie **toujours** vers `app_home`
après connexion (`default_target_path` + `always_use_default_target_path`).
Résultat : une seule règle de redirection dans tout le projet, facile à lire et
à modifier. Pas de `LoginSuccessHandler`, pas d'événement.

---

## 5. Commandes

> Aucun `composer require` dans cette phase : Security, Form, Validator et
> Translation sont installés depuis la phase 01, et leurs recettes Flex ont créé
> `security.yaml`, `csrf.yaml`, `translation.yaml` et `twig.yaml`. On se contente
> maintenant de **configurer** ces fichiers, laissés tels quels jusqu'ici.

### Configurer la traduction et le CSRF

- **Ce qu'il faut faire** : régler `config/packages/translation.yaml` sur
  `default_locale: fr` et `fallbacks: [fr]` ; remplacer le contenu de
  `csrf.yaml` (concept 8) ; déclarer le thème `bootstrap_5_layout.html.twig`
  dans `twig.yaml`.
- **À observer** : les messages de Symfony (« Identifiants invalides. »,
  messages de validation par défaut) s'affichent alors en français.

### `docker compose exec app php bin/console debug:router`

- **Quand** : après avoir ajouté `/login`, `/logout`, `/inscription`.
- **À observer** : `app_logout` doit exister, même si sa méthode est vide —
  c'est Symfony qui l'intercepte.

### `docker compose exec app php bin/console lint:yaml config`

- **Ce qu'elle fait** : vérifie la syntaxe YAML (indentation, deux-points).
- **Pourquoi** : une erreur dans `security.yaml` casse **tout** le site.

### `docker compose exec app php bin/console debug:firewall main`

- **Ce qu'elle fait** : détaille la configuration du firewall `main`.
- **Quand** : « pourquoi suis-je redirigé vers `/login` ? ».

### `docker compose exec app php bin/console security:hash-password`

- **Ce qu'elle fait** : calcule l'empreinte d'un mot de passe.
- **Quand** : pour créer un compte à la main en base pendant les essais.

---

## 6. Concepts Symfony de la phase

| Composant | Rôle ici |
|---|---|
| **Security** | firewall, provider, hachage, `access_control`, `isGranted()` |
| **Form** | `*Type`, `handleRequest()`, champs mappés/non mappés |
| **Validator** | contraintes `#[Assert\…]`, messages en français |
| **Translation** | messages de Symfony traduits en français |
| **HttpFoundation** | session, cookies, redirections |
| **Twig** | thème de formulaire Bootstrap, affichage des flash |

---

## 7. Architecture et organisation du code

```text
config/packages/
├── security.yaml         firewall, provider, access_control
├── csrf.yaml             jeton CSRF adossé à la session (fichier remplacé)
├── translation.yaml      langue française par défaut
└── twig.yaml             thème de formulaire Bootstrap

src/
├── Controller/
│   ├── SecurityController.php    /login, /logout, /inscription
│   ├── HomeController.php        redirection par rôle
│   ├── Admin/AccueilController.php      page d'attente /admin
│   └── Parent/DashboardController.php   page d'attente /parent
├── Entity/User.php               (phase 02, inchangée)
├── Repository/UserRepository.php  (phase 02) + UserLoaderInterface (recherche par email)
└── Form/
    └── InscriptionType.php       les champs du formulaire d'inscription

templates/security/
├── login.html.twig
└── inscription.html.twig
```

Un `*Type` **par formulaire**, dans `src/Form/` : c'est la convention du projet,
et elle rend chaque formulaire réutilisable (on le verra avec `MotDePasseType`
en phase 05).

---

## 8. Flux de fonctionnement

### Connexion

```text
Navigateur : POST /login (email + mot de passe + jeton CSRF)
    ↓
Firewall main : form_login intercepte la requête
    ↓
Vérification du jeton CSRF
    ↓
Provider : cherche l'utilisateur (UserRepository)
    ↓
Hasher : recalcule l'empreinte et la compare
    ↓  succès                               ↓  échec
Session : l'utilisateur est mémorisé        retour au formulaire avec l'erreur
    ↓
Redirection vers app_home
    ↓
HomeController : redirige selon le rôle
```

### Requête suivante

```text
Navigateur (cookie de session)
    ↓
Firewall : retrouve l'utilisateur en session
    ↓
access_control : l'URL demande-t-elle un rôle ?
    ↓  oui et le rôle manque → 403
    ↓  oui et le rôle est là → contrôleur
```

---

## 9. Application au projet

**Pourquoi cette phase ?** Sans comptes ni rôles, aucune des fonctionnalités
suivantes n'a de sens : le journal appartient à un enfant, le tableau de bord à
un parent, la bibliothèque à un administrateur.

**Composants utilisés** : Security, Form, Validator, Translation, Twig.

**Fichiers créés ou modifiés** : `security.yaml`, `csrf.yaml`,
`translation.yaml`, `twig.yaml`, `SecurityController`, `InscriptionType`,
les gabarits de connexion et d'inscription, la redirection dans `HomeController`,
`UserLoaderInterface` sur `UserRepository`, et deux pages d'attente pour
`/parent` (`parent_dashboard`) et `/admin` (`admin_accueil`). Sur l'accueil, le
bouton « Je suis un parent » pointe désormais vers `app_login` ; les boutons de
connexion enfant restent sur `#` jusqu'en phase 06.

**Pourquoi ces choix ?**

- `form_login` plutôt qu'un authenticator : moins de code, moins de risques.
- Rôles **sans hiérarchie** : les trois espaces sont réellement séparés ; un
  administrateur n'a rien à faire dans l'espace d'un enfant.
- Redirection dans `HomeController` : une seule règle, lisible.
- Case de **consentement** à l'inscription : le projet enregistre des données de
  santé d'enfants, le parent doit l'accepter explicitement.

**Ce qui n'est pas créé ici** : l'entité `User` (déjà là depuis la phase 02) et
aucun paquet (tous installés en phase 01).

**Ce qui n'est pas encore là** : la connexion enfant (phase 06), les profils
enfants (phase 05), et tout contenu réel dans `/parent` et `/admin`.

---

## 10. Erreurs fréquentes

**Vous êtes redirigé vers `/login` en boucle**
→ La page de connexion est elle-même protégée par `access_control`.
→ Solution : vérifier que `^/login` n'est couvert par aucune règle de rôle.

**Erreur 500 à chaque tentative de connexion**
→ Le provider n'a pas d'option `property` et `UserRepository` n'implémente pas
`UserLoaderInterface`.
→ Solution : ajouter l'interface et `loadUserByIdentifier()` (concept 3).

**403 au lieu d'une redirection vers `/login`**
→ Vous êtes connecté, mais avec le mauvais rôle. C'est le comportement attendu.
→ Vérification : la barre de debug affiche l'utilisateur courant et ses rôles.

**« Invalid CSRF token »**
→ Formulaire resté ouvert trop longtemps, session expirée, ou jeton absent du
gabarit.
→ Solution : recharger la page. Si cela persiste, vérifier que le champ `_token`
est bien rendu (`form_end()` s'en charge).

**Le mot de passe est enregistré en clair**
→ Le hachage a été oublié dans le contrôleur.
→ Signe : dans phpMyAdmin, la colonne `password` est lisible.
→ Solution : `hashPassword()` avant `setPassword()`. **Toujours.**

**Erreur 500 quand on soumet un champ vide**
→ Le setter de l'entité n'accepte pas `null`.
→ Solution : signature `setEmail(?string $email)` — l'erreur survenait **avant**
la validation.

**« This form should not contain extra fields »**
→ Le nom d'un champ HTML ne correspond pas au `*Type`.
→ Solution : laisser Symfony rendre les champs (`form_row`, `form_widget`) plutôt
que de les écrire à la main.

**La connexion réussit mais on revient toujours sur la page d'accueil publique**
→ `HomeController` ne connaît pas encore le rôle, ou le rôle n'a pas été
attribué à l'inscription.
→ Solution : vérifier la colonne `roles` en base.

---

## 11. Bonnes pratiques

- **Ne codez jamais la vérification d'un mot de passe vous-même.**
- **Un mot de passe est haché, jamais chiffré**, et n'est jamais journalisé.
- **Validez côté serveur**, toujours : le HTML ne protège rien.
- **Messages d'erreur utiles mais discrets** : « L'identifiant ou le mot de passe
  n'est pas le bon » ne dit pas lequel des deux est faux.
- **Écrivez les messages pour l'utilisateur**, en français, sans jargon.
- **Une action qui modifie des données se fait en POST**, jamais en GET.
- **Testez l'accès interdit** aussi souvent que l'accès autorisé : une
  fonctionnalité de sécurité qui n'a jamais été mise en échec n'est pas vérifiée.

---

## 12. Exercice pratique

1. Créez un compte parent via `/inscription`, puis regardez la colonne
   `password` dans phpMyAdmin : elle doit être illisible et commencer par `$2y$`
   ou `$argon`.
2. Créez un second compte avec **le même mot de passe** : les deux empreintes
   doivent être **différentes** (c'est le sel). Expliquez pourquoi c'est
   important.
3. Soumettez le formulaire avec un mot de passe de 3 caractères, puis sans cocher
   la case : notez les messages affichés et repérez **où** ils sont définis dans
   le code.
4. Ouvrez la barre de debug en bas de page, onglet « Security » : relevez
   l'utilisateur connecté, ses rôles et le firewall actif.
5. Créez le compte administrateur de test : **d'abord** inscrivez-vous via
   `/inscription` avec `admin@digisante.local` / `admin123`, **puis** donnez-lui
   le rôle en base :
   `docker compose exec app php bin/console dbal:run-sql "UPDATE users SET roles = '[\"ROLE_ADMIN\"]' WHERE email = 'admin@digisante.local'"`.
   Si vous étiez connecté avec ce compte, Symfony détecte que les rôles ont
   changé et vous **déconnecte** : reconnectez-vous, puis observez la
   redirection vers `/admin`.

Vous devez savoir expliquer la différence entre authentification et autorisation,
et pourquoi `plainPassword` n'est pas une propriété de `User`.

---

## 13. Scénario de test manuel

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

⬅️ [Phase précédente](./phase-03.md)

➡️ [Phase suivante](./phase-05.md)

➡️ [Phase de développement](../README.md#phase-04--authentification-rôles-inscription-et-connexion-parent)

➡️ [Prompt Claude Code](../prompts/phase-04.md)
