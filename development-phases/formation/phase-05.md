# Formation — Phase 05 : Espace parent et profils enfants

## 1. Objectifs pédagogiques

À la fin de cette leçon, vous devez être capable de :

- **utiliser** les relations et les cascades déclarées en phase 02 pour créer,
  modifier et supprimer un enfant et son compte ;
- écrire une requête de repository (`findByParent()`, `generateUsername()`) ;
- écrire un **voter** et expliquer en quoi il complète `access_control` ;
- créer une **extension Twig** pour ajouter un filtre d'affichage ;
- comprendre pourquoi un `RangeType` a besoin d'un **transformer** ;
- protéger une suppression par un **jeton CSRF** hors formulaire.

## 2. Prérequis

- Phases 01 à 04 terminées : un parent peut s'inscrire et se connecter.
- Savoir ce qu'est une entité, une relation, une cascade et une contrainte de
  validation (phase 02) : l'entité `Child` existe déjà, relisez
  `src/Entity/Child.php` avant de commencer.
- Savoir ce qu'est un formulaire Symfony et un champ non mappé (phase 04).

---

## 3. Concepts à apprendre

### Concept 1 — Se servir des entités de la phase 02

**Pourquoi ?** L'entité `Child`, ses relations avec `User`, ses cascades, ses
constantes et ses contraintes existent **depuis la phase 02**
([leçon 02](./phase-02.md)). Cette phase ne touche pas au schéma : elle
**utilise** ce qui a été déclaré.

**Rappel rapide.**

| Déclaré en phase 02 | Ce qu'on en fait ici |
|---|---|
| `Child::parent` (`ManyToOne`, `onDelete: 'CASCADE'`) | `$child->setParent($parent)` avant `persist()` |
| `User::children` (`OneToMany`, `cascade: ['remove']`) | lister les enfants, supprimer sans boucle |
| `Child::compte` (`OneToOne` non nullable, `cascade: ['persist', 'remove']`) | un seul `persist()` crée l'enfant **et** son compte ; `remove()` supprime les deux |
| `Child::AVATARS`, `getAvatarEmoji()` | la galerie d'avatars et l'affichage des cartes |
| `#[Assert\…]` (âge 8-14 ans, limite 15-480 par pas de 15) | les messages d'erreur du formulaire `ChildType` |

**Dans ce projet.** L'enfant a **deux** relations vers `User` : son `parent`
(qui le gère) et son `account` (avec lequel il se connecte). Si un détail vous
échappe (côté propriétaire, différence `cascade` / `onDelete`, pourquoi des
constantes plutôt que des enums), relisez la leçon 02 avant de continuer.

⚠️ Règle du projet : **pas de classe « manager » de suppression**. Les cascades
sont configurées, on ne les réimplémente pas en PHP.

---

### Concept 2 — Le voter

**Pourquoi ?** `access_control` protège une **URL**. Mais
`/parent/children/12/edit` est autorisée à **tous** les parents : il faut
vérifier que l'enfant 12 appartient **à celui qui est connecté**. C'est une
décision sur un **objet**, pas sur une URL.

**Comment ça fonctionne ?** Un voter répond à la question « cet utilisateur
a-t-il le droit de faire CETTE action sur CET objet ? ».

```php
class ChildVoter extends Voter
{
    public const MANAGE = 'CHILD_MANAGE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        // This voter only decides on this action and this type of object
        return self::MANAGE === $attribute && $subject instanceof Child;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        return $user instanceof User && $subject->getParent()?->getId() === $user->getId();
    }
}
```

Utilisation dans le contrôleur :

```php
$this->denyAccessUnlessGranted(ChildVoter::MANAGE, $child);   // sinon : 403
```

**À retenir.** `access_control` = grosse maille (par URL).
Voter = maille fine (par objet). Les deux se complètent.

---

### Concept 3 — L'extension Twig

**Pourquoi ?** « 150 minutes » n'a aucun sens pour un enfant ; « 2 h 30 » si. Ce
formatage est utilisé dans une dizaine de gabarits : il ne doit exister qu'une
fois.

**Comment ça fonctionne ?** Une classe déclare un filtre, utilisable partout dans
Twig.

```php
class DurationExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [new TwigFilter('duree', [self::class, 'formater'])];
    }

    public static function formater(?int $minutes): string
    {
        $minutes = max(0, (int) $minutes);
        $hours = intdiv($minutes, 60);
        $rest = $minutes % 60;

        if (0 === $hours) { return $rest.' min'; }
        if (0 === $rest)  { return $hours.' h'; }

        return sprintf('%d h %02d', $hours, $rest);
    }
}
```

```twig
{{ child.dailyLimit|duration }}   {# 90 → « 1 h 30 » #}
```

La méthode est `static` : le service `AdviceService` (phase 09) la réutilisera
directement, sans passer par Twig.

---

### Concept 4 — Le transformer de données

**Pourquoi ?** Un `<input type="range">` renvoie **toujours** une chaîne
(`"120"`). La propriété `dailyLimit` est un `int`. Sans conversion, le
formulaire échoue avec un message incompréhensible.

**Comment ça fonctionne ?** Un transformer convertit dans les deux sens :

```php
$builder->get('dailyLimit')->addModelTransformer(new CallbackTransformer(
    fn (?int $minutes) => (string) $minutes,                        // objet → formulaire
    fn (?string $value) => is_numeric($value) ? (int) $value : null, // formulaire → objet
));
```

Le `null` en cas de valeur non numérique est volontaire : la **validation**
affichera alors un message propre, au lieu d'une erreur technique.

---

### Concept 5 — Le CSRF hors formulaire Symfony

**Pourquoi ?** Le bouton « Supprimer » n'est pas un formulaire de saisie, mais
c'est une action destructrice : elle doit être protégée comme les autres.

**Comment ça fonctionne ?** On génère le jeton dans le gabarit et on le vérifie
dans le contrôleur.

```twig
<form method="post" action="{{ path('parent_child_delete', {id: child.id}) }}"
      onsubmit="return confirm('Supprimer définitivement le profil ?');">
    <input type="hidden" name="_token" value="{{ csrf_token('supprimer-enfant-' ~ child.id) }}">
    <button type="submit">🗑️ Supprimer</button>
</form>
```

```php
if (!$this->isCsrfTokenValid('supprimer-enfant-'.$child->getId(), $request->getPayload()->getString('_token'))) {
    throw $this->createAccessDeniedException('Jeton CSRF invalide.');
}
```

Un jeton absent ou faux donne donc un **403** — même règle pour toutes les
suppressions du projet (enfants, contenus, parents).
Le jeton inclut l'identifiant : celui de l'enfant 3 ne vaut pas pour l'enfant 4.
Le `confirm()` protège de la fausse manœuvre, **pas** de l'attaque : les deux
sont nécessaires.

---

## 4. Explications avec exemples

### Créer un enfant **et** son compte, en une seule opération

```php
$account = new User();
$account->setUsername($userRepository->generateUsername($child->getFirstName())); // « lea », « lea2 »…
$account->setRoles([User::ROLE_CHILD]);
$account->setPassword($passwordHasher->hashPassword($account, $form->get('password')->getData()));

$child->setAccount($account);

$entityManager->persist($child);   // the account follows, thanks to cascade: ['persist']
$entityManager->flush();
```

Un seul `persist()` pour deux objets : c'est la cascade qui s'en charge. C'est
aussi pour cela que `Child::compte` est **non nullable** : un profil sans compte
n'existe pas dans ce projet, donc aucun cas particulier à gérer ensuite.

### Générer un identifiant libre

```php
public function generateUsername(string $firstName): string
{
    $base = (new AsciiSlugger())->slug($firstName, '')->lower()->toString(); // "Léa" → "lea"
    $base = substr($base, 0, 40) ?: 'child';

    $username = $base;
    $number = 1;

    while (null !== $this->findOneBy(['username' => $username])) {
        ++$number;
        $username = $base.$number;      // lea2, lea3…
    }

    return $username;
}
```

Une boucle simple, lisible de haut en bas. Pas de génération aléatoire : un
enfant de 8 ans doit pouvoir **retenir** son identifiant.

### Un formulaire, deux usages

```php
$resolver->setDefaults([
    'data_class' => Child::class,
    'creation' => false,        // custom option
]);
```

```php
if ($options['creation']) {
    // TextType (visible): the parent reads the password again before writing it down.
    $builder->add('password', TextType::class, ['mapped' => false, /* … */]);
}
```

À la création, le formulaire demande un mot de passe ; à la modification, non. Un
seul `*Type`, un seul partiel Twig, deux comportements.

### Le profil du parent, et le piège de l'utilisateur connecté

Le menu de l'espace parent mène à `/parent/profile` (`parent_profile`,
`Parent\ProfileController`) : deux formulaires sur la même page,
`ParentProfileType` (email obligatoire, pays, ville) et `PasswordChangeType` (un
champ `plainPassword`, `RepeatedType` non mappé, comme dans `RegistrationType`).

```php
$formProfil = $this->createForm(ParentProfileType::class, $parent);
$formProfil->handleRequest($request);

if ($formProfil->isSubmitted() && !$formProfil->isValid()) {
    // The form has already changed the User object in memory. It is
    // the logged-in user: an empty or wrong email would log them out
    // on the next request. So their real values are reloaded.
    $entityManager->refresh($parent);
}
```

---

## 5. Commandes

### `docker compose exec app php bin/console make:voter ChildVoter`

- **Ce qu'elle fait** : génère le squelette d'un voter avec `supports()` et
  `voteOnAttribute()`, dans `src/Security/Voter/ChildVoter.php` (espace de noms
  `App\Security\Voter`). Le squelette propose des attributs d'exemple
  (`POST_EDIT`, `POST_VIEW`) : remplacez-les par la seule constante
  `MANAGE = 'CHILD_MANAGE'`.
- **À observer** : le voter est automatiquement enregistré comme service, sans
  configuration.

### `docker compose exec app php bin/console doctrine:schema:validate`

- **Quand** : pour confirmer que le schéma n'a pas bougé. Aucune entité n'est
  modifiée dans cette phase : **aucune migration** n'est attendue ; si
  `make:migration` propose quelque chose, c'est qu'une entité a été touchée par
  erreur.

### `docker compose exec app php bin/console debug:twig --filter=duree`

- **Ce qu'elle fait** : vérifie que votre filtre est bien enregistré.
- **Quand** : « Unknown "duree" filter ».

---

## 6. Concepts Symfony de la phase

| Composant | Rôle ici |
|---|---|
| **Doctrine ORM** | utilisation des relations et cascades (déclarées en phase 02), requêtes de repository |
| **Security (Voter)** | autorisation sur un objet précis |
| **Form** | option personnalisée, champ non mappé, transformer |
| **Validator** | contraintes de la phase 02 (âge 8-14 ans, limite 15-480 par pas de 15) + mot de passe non mappé |
| **Twig (extension)** | le filtre `duration` |
| **String (Slugger)** | génération de l'identifiant |

---

## 7. Architecture et organisation du code

```text
src/
├── Controller/Parent/
│   ├── ChildController.php      liste, création, modification, suppression
│   └── ProfileController.php      /parent/profile : informations + mot de passe
├── Form/
│   ├── ChildType.php            option « creation »
│   ├── ParentProfileType.php      email, pays, ville
│   └── PasswordChangeType.php        réutilisé par le parent ET l'enfant
├── Repository/                   (fichiers existants depuis la phase 02)
│   ├── ChildRepository.php      + findByParent()
│   └── UserRepository.php        + generateUsername()
├── Security/Voter/
│   └── ChildVoter.php           CHILD_MANAGE (créé par make:voter)
└── Twig/
    └── DurationExtension.php        filtre « duree »

templates/
├── parent/
│   ├── layout.html.twig          menu de l'espace parent
│   ├── profile.html.twig         les deux formulaires du profil
│   └── children/                 index, new, edit, _form
├── form/avatars.html.twig        galerie d'avatars
└── _partials/delete_button.html.twig
```

Les entités `Child` et `User` ne sont **pas modifiées** : elles datent de la
phase 02.

Pourquoi un dossier `Controller/Parent/` : chaque espace a ses contrôleurs, ce
qui rend la structure lisible dès le premier coup d'œil.

---

## 8. Flux de fonctionnement

### Création d'un enfant

```text
Navigateur : POST /parent/children/new
    ↓
access_control : ROLE_PARENT requis
    ↓
ChildController::nouveau()
    ↓
ChildType : remplit l'objet Child + le champ non mappé « password »
    ↓
Validator : âge 8-14 ans, limite valide, mot de passe ≥ 6 caractères
    ↓
Contrôleur : crée le User enfant, génère l'identifiant, hache le mot de passe
    ↓
persist(child) + flush()  →  cascade persist : le compte est créé aussi
    ↓
message flash avec l'identifiant  →  redirection vers la liste
```

### Modification d'un enfant qui n'est pas le vôtre

```text
GET /parent/children/42/edit
    ↓
access_control : ROLE_PARENT → OK (c'est bien un parent)
    ↓
Doctrine : charge l'enfant 42
    ↓
ChildVoter : ce parent est-il celui de l'enfant 42 ?  → NON
    ↓
403 Accès refusé
```

---

## 9. Application au projet

**Pourquoi cette phase ?** C'est le parent qui ouvre l'accès à son enfant : sans
profil enfant, pas de journal, pas de conseils, pas de suivi. C'est aussi ici que
se joue le **cloisonnement entre familles**, une exigence forte du projet.

**Composants utilisés** : Doctrine (relations de la phase 02, repositories), Security (voter), Form,
Validator, Twig (extension).

**Fichiers créés** : voir l'arborescence ci-dessus.

**Pourquoi cette architecture ?**

- **Compte enfant obligatoire** (`account` non nullable) : un seul cas à gérer,
  jamais de « profil sans compte ».
- **Le parent choisit le mot de passe** : jamais de génération automatique. Il
  doit pouvoir le transmettre à son enfant et le redonner en cas d'oubli — il
  n'y a pas de récupération par email dans ce projet.
- **La limite d'écran vit sur le profil**, dans le même formulaire : un seul
  écran pour le parent, un seul partiel Twig.
- **Le voter plutôt qu'un `if` dans le contrôleur** : la règle est écrite une
  fois et réutilisée par les trois actions sensibles.

---

## 10. Erreurs fréquentes

**`Column 'parent_id' cannot be null`**
→ Le parent n'a pas été affecté avant l'enregistrement.
→ Solution : `$child->setParent($parent)` avant `persist()`.

**Erreur « A new entity was found through the relationship… »**
→ Le compte est créé mais `$child->setAccount($account)` a été oublié, ou la
cascade `persist` de la phase 02 a été retirée de `Child::compte`.
→ Solution : relier le compte à l'enfant avant `persist()` ; vérifier le
mapping (voir la [leçon 02](./phase-02.md)).

**Supprimer un enfant laisse son compte en base**
→ La suppression a été faite en SQL, ou `cascade: ['remove']` manque sur
`Child::compte` (phase 02).
→ Vérification : après suppression, cherchez le `username` dans la table `users`.

**Le curseur de limite provoque « Cette valeur n'est pas valide »**
→ Le transformer manque : le formulaire envoie une chaîne, l'entité attend un
entier.

**La galerie d'avatars s'affiche mal, chaque radio entourée d'un bloc**
→ Le thème Bootstrap entoure chaque radio d'un `div.form-check`.
→ Solution : écrire les `<input>` dans le partiel, puis appeler `setRendered`.

**403 sur son propre enfant**
→ Comparaison d'objets au lieu d'identifiants, ou utilisateur rechargé.
→ Solution : comparer les `getId()`, comme dans l'exemple du voter.

**Après une saisie invalide sur le profil, le parent est déconnecté**
→ L'objet `User` connecté a gardé l'email invalide en mémoire.
→ Solution : `$entityManager->refresh($user)` quand le formulaire est soumis
mais invalide.

**La suppression renvoie 403 alors que le bouton vient du site**
→ Le nom du jeton CSRF du gabarit ne correspond pas à celui du contrôleur.
→ Solution : la **même chaîne** des deux côtés, identifiant inclus.

---

## 11. Bonnes pratiques

- **Une requête = une méthode de repository**, nommée en anglais
  (`findByParent()`), jamais de DQL dans un contrôleur.
- **Laissez les cascades faire le travail** ; n'écrivez pas de boucle de
  suppression.
- **Un voter par règle d'appartenance**, appelé dans **chaque** action concernée.
  Une action oubliée, c'est une faille.
- **Les listes fixes sont des constantes d'entité**, avec des getters
  d'affichage.
- **Pas de code « astucieux »** : une boucle `while` lisible vaut mieux qu'une
  génération aléatoire élégante.
- **Avant d'ajouter une classe**, demandez-vous si vingt lignes dans le
  contrôleur ou l'entité ne suffisent pas. Ici, seuls le voter et l'extension
  Twig méritaient leur fichier.

---

## 12. Exercice pratique

1. Créez deux enfants prénommés « Léa » : vérifiez que les identifiants générés
   sont `lea` puis `lea2`, et expliquez quelle ligne de code produit ce
   comportement.
2. Dans phpMyAdmin, ouvrez la table `child` : repérez les colonnes `parent_id`
   et `account_id`, puis retrouvez les deux comptes correspondants dans `users`.
3. Supprimez un enfant depuis l'interface, puis vérifiez dans `users` que son
   compte a bien disparu. Quelle option de mapping, déclarée en phase 02, l'a
   provoqué ?
4. Connectez-vous avec un **second** compte parent, puis tentez d'ouvrir
   `/parent/children/1/edit` (l'enfant du premier parent) : vous devez obtenir
   un 403. Retirez temporairement l'appel au voter, rechargez : la page s'affiche.
   **Remettez l'appel** et expliquez ce que vous venez de démontrer.
5. Affichez `{{ 150|duration }}` dans un gabarit : vous devez lire « 2 h 30 ».

---

## 13. Scénario de test manuel

1. Connecté en parent, ouvrir `/parent/children` puis « Ajouter un enfant ».
2. Saisir un prénom, un nom, une date de naissance d'un enfant de 10 ans, un avatar, une limite de 1 h 30 et un mot de passe.
3. Valider et lire le message : il annonce l'identifiant généré (ex. « lea »).
4. Essayer de créer un deuxième enfant avec une date de naissance d'un enfant de 4 ans.
5. **Résultat attendu** : le premier enfant apparaît dans la liste avec son identifiant ; le second est refusé avec le message « L'application est réservée aux enfants de 8 à 14 ans. »

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

⬅️ [Phase précédente](./phase-04.md)

➡️ [Phase suivante](./phase-06.md)

➡️ [Phase de développement](../README.md#phase-05--espace-parent--profils-enfants)

➡️ [Prompt Claude Code](../prompts/phase-05.md)
