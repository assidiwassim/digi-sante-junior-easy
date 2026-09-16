# Formation — Phase 04 : Inscription, connexion et rôles

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

- Phases 01 à 03 terminées : l'entité `User` existe en base.
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

**Dans ce projet.** Le jeton est **adossé à la session** (`config/packages/csrf.yaml`) :
ne revenez pas à la variante « stateless » par défaut.

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

Le gabarit `base.html.twig` (phase 02) les affiche déjà pour toutes les pages.

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
    if ($this->isGranted(User::ROLE_ADMIN))  { return $this->redirectToRoute('admin_contenus'); }
    if ($this->isGranted(User::ROLE_PARENT)) { return $this->redirectToRoute('parent_dashboard'); }
    if ($this->isGranted(User::ROLE_CHILD))  { return $this->redirectToRoute('enfant_accueil'); }

    return $this->render('home/index.html.twig');
}
```

Pourquoi ici ? Parce que `security.yaml` renvoie **toujours** vers `app_home`
après connexion (`default_target_path` + `always_use_default_target_path`).
Résultat : une seule règle de redirection dans tout le projet, facile à lire et
à modifier. Pas de `LoginSuccessHandler`, pas d'événement.

---

## 5. Commandes

### `docker compose exec app composer require symfony/security-bundle symfony/form symfony/validator`

- **Ce qu'elle fait** : installe la sécurité, les formulaires et le validateur ;
  Flex crée `config/packages/security.yaml` avec une configuration de départ.
- **À observer** : ouvrez le fichier généré et comparez-le à celui attendu.

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
| **HttpFoundation** | session, cookies, redirections |
| **Twig** | thème de formulaire Bootstrap, affichage des flash |

---

## 7. Architecture et organisation du code

```text
config/packages/
├── security.yaml         firewall, provider, access_control
├── csrf.yaml             jeton CSRF adossé à la session
└── twig.yaml             thème de formulaire Bootstrap

src/
├── Controller/
│   ├── SecurityController.php    /login, /logout, /inscription
│   └── HomeController.php        redirection par rôle
├── Entity/User.php               (phase 03)
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

**Composants utilisés** : Security, Form, Validator, Twig.

**Fichiers créés** : `security.yaml`, `SecurityController`, `InscriptionType`,
les gabarits de connexion et d'inscription, la redirection dans `HomeController`,
et deux pages d'attente pour `/parent` et `/admin`.

**Pourquoi ces choix ?**

- `form_login` plutôt qu'un authenticator : moins de code, moins de risques.
- Rôles **sans hiérarchie** : les trois espaces sont réellement séparés ; un
  administrateur n'a rien à faire dans l'espace d'un enfant.
- Redirection dans `HomeController` : une seule règle, lisible.
- Case de **consentement** à l'inscription : le projet enregistre des données de
  santé d'enfants, le parent doit l'accepter explicitement.

**Ce qui n'est pas encore là** : la connexion enfant (phase 06), les profils
enfants (phase 05), et tout contenu réel dans `/parent` et `/admin`.

---

## 10. Erreurs fréquentes

**Vous êtes redirigé vers `/login` en boucle**
→ La page de connexion est elle-même protégée par `access_control`.
→ Solution : vérifier que `^/login` n'est couvert par aucune règle de rôle.

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
5. Passez temporairement `ROLE_ADMIN` à votre compte en base, rechargez `/` et
   observez la redirection. Remettez `ROLE_PARENT` ensuite.

Vous devez savoir expliquer la différence entre authentification et autorisation,
et pourquoi `plainPassword` n'est pas une propriété de `User`.

---

## 13. Scénario de test manuel

1. Ouvrir `/inscription` et créer un compte avec un email et un mot de passe de 6 caractères minimum.
2. Se connecter sur `/login` avec ce compte.
3. Vérifier la redirection automatique vers `/parent`.
4. Se déconnecter, puis ouvrir `/parent` directement dans la barre d'adresse.
5. **Résultat attendu** : l'inscription et la connexion fonctionnent, et l'accès à `/parent` déconnecté renvoie vers `/login`.

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

➡️ [Phase suivante](./phase-05.md)

➡️ [Phase de développement](../README.md#phase-04--inscription-connexion-et-rôles)

➡️ [Prompt Claude Code](../prompts/phase-04.md)
