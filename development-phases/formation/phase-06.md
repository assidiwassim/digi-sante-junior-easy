# Formation — Phase 06 : Espace enfant, connexion par identifiant et accueil

## 1. Objectifs pédagogiques

À la fin de cette leçon, vous devez être capable de :

- personnaliser le **chargement d'un utilisateur** avec `UserLoaderInterface` ;
- expliquer pourquoi **deux pages de connexion** peuvent partager un seul
  `check_path` ;
- utiliser `_failure_path` et comprendre pourquoi c'est un **chemin**, pas un nom
  de route ;
- récupérer l'utilisateur connecté avec `#[CurrentUser]` ;
- construire un **layout par espace** et comprendre l'intérêt de cette
  organisation ;
- adapter interface et vocabulaire à un utilisateur de 8 à 14 ans.

## 2. Prérequis

- Phases 01 à 05 terminées : un profil enfant existe, avec son compte.
- Comprendre le firewall, le provider et le hachage (phase 04).
- Comprendre l'héritage de gabarits (phase 02).

---

## 3. Concepts à apprendre

### Concept 1 — Charger un utilisateur autrement

**Pourquoi ?** Les parents se connectent avec leur **email**, les enfants avec un
**identifiant**. Un seul provider doit savoir gérer les deux.

**Comment ça fonctionne ?** Par défaut, on écrirait `property: email` dans
`security.yaml` : Symfony chercherait alors uniquement sur cette colonne. En
implémentant `UserLoaderInterface` dans le repository, **vous** décidez comment
retrouver l'utilisateur.

```php
class UserRepository extends ServiceEntityRepository implements UserLoaderInterface
{
    /** Appelée par Symfony Security à la connexion. */
    public function loadUserByIdentifier(string $identifier): ?User
    {
        return $this->createQueryBuilder('u')
            ->where('u.email = :identifiant OR u.username = :identifiant')
            ->setParameter('identifiant', mb_strtolower(trim($identifier)))
            ->getQuery()
            ->getOneOrNullResult();
    }
}
```

Trois choses à noter :

- une **seule** valeur liée (`:identifiant`), utilisée deux fois : jamais de
  concaténation, donc pas d'injection SQL possible ;
- `mb_strtolower(trim(...))` : cohérent avec la normalisation faite dans les
  setters de `User` (phase 03) — d'où la casse sans importance ;
- `getOneOrNullResult()` : l'unicité des deux colonnes garantit qu'il n'y a
  jamais deux résultats.

**Dans ce projet.** C'est précisément pour cela que le provider de
`security.yaml` n'a **pas** d'option `property`.

---

### Concept 2 — Deux formulaires, un seul point de traitement

**Pourquoi ?** On veut deux pages très différentes : sobre pour le parent,
ludique pour l'enfant. Mais dupliquer la logique d'authentification serait une
mauvaise idée.

**Comment ça fonctionne ?** Les deux pages **affichent** un formulaire ; toutes
deux l'**envoient** à `/login`, seule adresse traitée par `form_login`.

```twig
{# templates/security/login_enfant.html.twig #}
<form method="post" action="{{ path('app_login') }}">
    <input type="text" name="_username" value="{{ last_username }}" required autofocus>
    <input type="password" name="_password" required>
    <input type="hidden" name="_csrf_token" value="{{ csrf_token('authenticate') }}">
    <input type="hidden" name="_failure_path" value="{{ path('app_enfant_login') }}">
    <button type="submit">C'est parti ! 🚀</button>
</form>
```

Les noms `_username`, `_password`, `_csrf_token` sont ceux qu'attend
`form_login` : ce sont des conventions, pas des choix libres.

---

### Concept 3 — `_failure_path`

**Pourquoi ?** Sans ce champ, un enfant qui se trompe de mot de passe se
retrouverait sur la page de connexion **des parents** : déroutant.

**Comment ça fonctionne ?** `_failure_path` indique où retourner en cas d'échec.

```twig
<input type="hidden" name="_failure_path" value="{{ path('app_enfant_login') }}">
```

⚠️ **Piège du projet** : il faut un **chemin** (`/connexion-enfant`, produit par
`path()`), pas un **nom de route** (`app_enfant_login`). Avec le nom, la
redirection échoue silencieusement.

---

### Concept 4 — `#[CurrentUser]`

**Pourquoi ?** Presque toutes les pages de l'espace enfant ont besoin de savoir
qui est connecté.

**Comment ça fonctionne ?** Un attribut sur un argument de l'action ; Symfony
injecte l'utilisateur.

```php
#[Route('/enfant', name: 'enfant_accueil', methods: ['GET'])]
public function accueil(#[CurrentUser] User $user): Response
{
    $enfant = $user->getProfilEnfant();   // le profil rattaché au compte
    // …
}
```

C'est équivalent à `$this->getUser()`, mais **typé** : l'éditeur connaît la
classe, l'autocomplétion fonctionne, et l'intention est explicite.

**Dans ce projet.** C'est la convention : `#[CurrentUser] User $user` dans la
signature, comme les autres dépendances (phase 04).

---

### Concept 5 — Du compte au profil

**Pourquoi ?** Le compte (`User`) sert à se connecter ; le profil (`Enfant`)
porte le prénom, l'avatar, la limite. Deux objets, deux rôles.

**Comment ça fonctionne ?** La relation `OneToOne` créée en phase 05 se lit dans
les deux sens :

```php
$enfant = $user->getProfilEnfant();   // du compte vers le profil
$compte = $enfant->getCompte();       // du profil vers le compte
```

Une petite méthode privée évite de répéter la vérification :

```php
private function getEnfant(User $user): Enfant
{
    $enfant = $user->getProfilEnfant();

    if (null === $enfant) {
        throw $this->createNotFoundException('Aucun profil enfant n\'est rattaché à ce compte.');
    }

    return $enfant;
}
```

Ce cas ne devrait jamais arriver (le profil crée toujours son compte), mais le
code reste honnête : il échoue proprement plutôt que d'appeler une méthode sur
`null`.

---

### Concept 6 — Un layout par espace

**Pourquoi ?** Les trois espaces partagent la charte, mais pas le menu, ni le
ton, ni les couleurs de fond.

**Comment ça fonctionne ?** Un niveau intermédiaire dans l'héritage de gabarits :

```text
base.html.twig                    charte, navigation, flash, pied de page
    └── enfant/layout.html.twig   fond coloré, menu ludique, avatar
            └── enfant/accueil.html.twig
```

```twig
{% extends 'base.html.twig' %}

{% block body_class %}enfant-body{% endblock %}
{% block logo %}🚀{% endblock %}

{% block menu %}
    {% set route = app.request.attributes.get('_route') %}
    <li class="nav-item">
        <a class="nav-link {{ route == 'enfant_accueil' ? 'active' }}"
           href="{{ path('enfant_accueil') }}">🏠 Accueil</a>
    </li>
{% endblock %}
```

`app.request` est disponible partout dans Twig : on s'en sert ici pour mettre en
évidence le lien de la page courante.

---

### Concept 7 — Écrire pour un enfant

Ce n'est pas de la décoration : c'est une **exigence du projet**.

| Principe | Contre-exemple | À écrire |
|---|---|---|
| Tutoiement | « Veuillez saisir vos identifiants » | « Écris ton identifiant et ton mot de passe » |
| Message bienveillant | « Identifiants invalides » | « Oups ! … Essaie encore. » |
| Pas de jargon | « Erreur d'authentification 401 » | « Ce n'est pas le bon mot de passe » |
| Repères visuels | texte seul | emoji, avatar, gros boutons |

Sur la page de connexion enfant, la barre de navigation est **retirée**
(`{% block navbar %}{% endblock %}`) : moins d'éléments, moins de confusion.

---

## 4. Explications avec exemples

### La page de connexion enfant, côté contrôleur

```php
#[Route('/connexion-enfant', name: 'app_enfant_login', methods: ['GET'])]
public function loginEnfant(AuthenticationUtils $authenticationUtils): Response
{
    if ($this->getUser()) {
        return $this->redirectToRoute('app_home');
    }

    return $this->render('security/login_enfant.html.twig', [
        'last_username' => $authenticationUtils->getLastUsername(),
        'error' => $authenticationUtils->getLastAuthenticationError(),
    ]);
}
```

Notez `methods: ['GET']` : cette route **affiche** seulement. Le POST part vers
`/login`.

Et le message d'erreur, réécrit plutôt que traduit littéralement :

```twig
{% if error %}
    <div class="alert alert-danger">
        🙈 Oups ! L'identifiant ou le mot de passe n'est pas le bon. Essaie encore.
    </div>
{% endif %}
```

On n'affiche **pas** `error.messageKey` ici : le message technique de Symfony
serait incompréhensible pour un enfant. Et il ne dit pas lequel des deux champs
est faux, ce qui est aussi une bonne pratique de sécurité.

### Le changement de mot de passe par l'enfant

```php
$form = $this->createForm(MotDePasseType::class);   // réutilisé depuis la phase 05
$form->handleRequest($request);

if ($form->isSubmitted() && $form->isValid()) {
    $user->setPassword($passwordHasher->hashPassword($user, $form->get('plainPassword')->getData()));
    $entityManager->flush();      // pas de persist() : l'objet est déjà suivi par Doctrine

    $this->addFlash('success', 'Ton nouveau mot de passe est enregistré. Pense à bien le retenir !');

    return $this->redirectToRoute('enfant_profil');
}
```

Deux détails utiles :

- **pas de `persist()`** : l'utilisateur vient de la base, Doctrine le suit déjà ;
  `flush()` suffit ;
- les libellés du formulaire peuvent être adaptés dans le gabarit
  (`form_row(form.plainPassword.first, {label: 'Ton nouveau mot de passe'})`) :
  un seul `*Type`, deux tons de voix.

---

## 5. Commandes

### `docker compose exec app php bin/console debug:router`

- **Quand** : après avoir ajouté `/connexion-enfant`, `/enfant`, `/enfant/profil`.
- **À observer** : `app_enfant_login` doit être en **GET** uniquement.

### `docker compose exec app php bin/console lint:twig templates`

- **Quand** : après la création du layout enfant.
- **Pourquoi** : une erreur dans un layout casse **toutes** les pages qui en
  héritent.

### `docker compose exec app php bin/console cache:clear`

- **Quand** : après modification de `security.yaml`.

### `docker compose exec app php bin/console dbal:run-sql "SELECT id, username, roles FROM users WHERE username IS NOT NULL"`

- **Ce qu'elle fait** : liste les comptes enfants et leurs rôles.
- **Quand** : « pourquoi cet enfant ne peut-il pas se connecter ? ». Vérifiez
  l'identifiant exact et la présence de `ROLE_CHILD`.

---

## 6. Concepts Symfony de la phase

| Composant | Rôle ici |
|---|---|
| **Security** | `UserLoaderInterface`, `_failure_path`, `#[CurrentUser]` |
| **Doctrine** | relation `OneToOne` parcourue dans les deux sens |
| **Twig** | layout par espace, `app.request`, mise en évidence du menu |
| **Form** | réutilisation de `MotDePasseType` |

---

## 7. Architecture et organisation du code

```text
src/
├── Controller/
│   ├── SecurityController.php        + /connexion-enfant
│   └── Enfant/
│       └── AccueilController.php     /enfant et /enfant/profil
└── Repository/
    └── UserRepository.php            + loadUserByIdentifier()

templates/
├── security/login_enfant.html.twig   page de connexion sans navigation
└── enfant/
    ├── layout.html.twig              fond coloré, menu ludique
    ├── accueil.html.twig
    └── profil.html.twig
```

Pourquoi `Controller/Enfant/` : un dossier par espace, comme `Controller/Parent/`.
Quand le projet grandira (journal en phase 07), le contrôleur du journal se
rangera naturellement à côté.

---

## 8. Flux de fonctionnement

```text
Enfant : ouvre /connexion-enfant
    ↓
SecurityController::loginEnfant() → affiche le formulaire
    ↓
Enfant : saisit « lea » + mot de passe, POST vers /login
    ↓
form_login intercepte
    ↓
UserRepository::loadUserByIdentifier('lea')
    ↓  cherche sur email OU username
User trouvé → vérification du mot de passe haché
    ↓  échec                              ↓  succès
retour vers _failure_path                session ouverte
(/connexion-enfant)                           ↓
                                         redirection vers app_home
                                              ↓
                                         HomeController : ROLE_CHILD
                                              ↓
                                         /enfant
```

---

## 9. Application au projet

**Pourquoi cette phase ?** L'enfant est l'utilisateur principal du produit : il
lui faut une porte d'entrée à sa mesure. Et techniquement, rien de ce qui suit
(journal, conseils, bibliothèque) n'est accessible sans cette connexion.

**Composants utilisés** : Security (chargement sur mesure), Doctrine, Twig, Form.

**Fichiers créés** : voir l'arborescence ci-dessus.

**Pourquoi ces choix ?**

- **Un identifiant, pas un email** : un enfant de 8 ans n'a en général pas
  d'adresse email, et n'a pas à en créer une pour ce service.
- **Deux pages, un seul traitement** : l'ergonomie diffère, la sécurité reste
  unique — donc une seule chose à maintenir et à auditer.
- **L'enfant peut changer son mot de passe**, et son parent peut le
  réinitialiser (phase 05) : c'est le seul recours en cas d'oubli, puisque le
  projet n'envoie pas d'emails.

**Ce qui n'est pas encore là** : l'accueil reste volontairement simple.
La jauge du jour et le journal arrivent en phase 07, la bibliothèque en phase 08,
le graphique en phase 10. N'ajoutez pas au menu des entrées dont les routes
n'existent pas encore.

---

## 10. Erreurs fréquentes

**La connexion enfant échoue toujours, même avec le bon mot de passe**
→ `loadUserByIdentifier()` ne cherche que sur l'email, ou le provider a gardé
`property: email`.
→ Vérification : lancez la même requête en SQL pour voir si le compte est trouvé.

**Après une erreur, l'enfant atterrit sur la page des parents**
→ `_failure_path` absent, ou renseigné avec un **nom de route**.
→ Solution : `value="{{ path('app_enfant_login') }}"`.

**« Cannot read property … on null » sur l'accueil**
→ `getProfilEnfant()` renvoie `null` : le compte n'est rattaché à aucun profil.
→ Solution : la méthode privée qui lève une 404 explicite ; en base, vérifiez
`enfant.compte_id`.

**Un enfant accède à `/parent`**
→ `access_control` mal ordonné, ou une hiérarchie de rôles a été ajoutée.
→ Rappel : dans ce projet, **aucune** hiérarchie.

**« Unable to find template "enfant/layout.html.twig" »**
→ Nom ou emplacement erroné (Twig est sensible à la casse).

**Le lien actif du menu ne se met jamais en évidence**
→ Comparaison avec un nom de route inexact.
→ Astuce : `route starts with 'enfant_journal'` couvre toutes les étapes d'un
même parcours.

---

## 11. Bonnes pratiques

- **Ne codez pas l'authentification vous-même**, même pour un cas particulier :
  ici, une méthode de repository a suffi.
- **Un message d'erreur ne révèle pas** si c'est l'identifiant ou le mot de passe
  qui est faux.
- **Adaptez le vocabulaire au lecteur** : tutoiement côté enfant, vouvoiement
  côté parent. Le projet impose cette cohérence.
- **Réutilisez les formulaires** (`MotDePasseType`) plutôt que d'en dupliquer un
  presque identique.
- **N'ajoutez au menu que des routes existantes** : un lien mort est une erreur
  500 au rendu du gabarit.
- **`flush()` sans `persist()`** pour un objet déjà en base : `persist()` ne sert
  qu'aux objets nouveaux.

---

## 12. Exercice pratique

1. Connectez-vous en enfant avec l'identifiant **en majuscules** (`LEA`) :
   la connexion doit fonctionner. Retrouvez les deux endroits du code qui le
   permettent.
2. Saisissez volontairement un mauvais mot de passe : vérifiez que vous revenez
   bien sur `/connexion-enfant`. Remplacez temporairement `path(...)` par le nom
   de route dans `_failure_path`, réessayez, constatez la différence, puis
   remettez le code correct.
3. Dans `AccueilController`, affichez temporairement le nom de la classe de
   l'utilisateur injecté :

```php
dump(get_class($user), $user->getUserIdentifier(), $user->getRoles());
```

   Ouvrez la page et lisez la sortie dans la barre de debug.
4. Changez le mot de passe depuis « Mon profil », déconnectez-vous, reconnectez-
   vous avec le nouveau. Regardez la colonne `password` en base **avant** et
   **après** : elle doit avoir changé, et rester illisible.
5. Retirez le `dump()` avant de continuer.

---

## 13. Scénario de test manuel

1. Se déconnecter, puis ouvrir `/connexion-enfant`.
2. Saisir l'identifiant créé en phase 05 et son mot de passe.
3. Vérifier l'arrivée sur `/enfant` avec le prénom et l'avatar de l'enfant.
4. Ouvrir « Mon profil », changer le mot de passe, se déconnecter et se reconnecter avec le nouveau.
5. **Résultat attendu** : la connexion par identifiant fonctionne et le nouveau mot de passe est accepté.

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

➡️ [Phase suivante](./phase-07.md)

➡️ [Phase de développement](../README.md#phase-06--espace-enfant--connexion-et-accueil)

➡️ [Prompt Claude Code](../prompts/phase-06.md)
