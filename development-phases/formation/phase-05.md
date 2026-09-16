# Formation — Phase 05 : Espace parent, profils enfants et relations Doctrine

## 1. Objectifs pédagogiques

À la fin de cette leçon, vous devez être capable de :

- déclarer les trois **relations Doctrine** utilisées ici : `ManyToOne`,
  `OneToOne`, `OneToMany` ;
- expliquer ce qu'est le **côté propriétaire** d'une relation ;
- distinguer `cascade: ['remove']` (Doctrine) de `onDelete: 'CASCADE'` (base) ;
- écrire un **voter** et expliquer en quoi il complète `access_control` ;
- créer une **extension Twig** pour ajouter un filtre d'affichage ;
- comprendre pourquoi un `RangeType` a besoin d'un **transformer** ;
- protéger une suppression par un **jeton CSRF** hors formulaire.

## 2. Prérequis

- Phases 01 à 04 terminées : un parent peut s'inscrire et se connecter.
- Savoir ce qu'est une entité, un repository, une migration (phase 03).
- Savoir ce qu'est un formulaire Symfony et un champ non mappé (phase 04).

---

## 3. Concepts à apprendre

### Concept 1 — Les relations entre entités

**Pourquoi ?** Les données du monde réel sont liées : un parent **a** des
enfants, un enfant **appartient** à un parent. En base, ce lien est une clé
étrangère ; côté PHP, on veut manipuler des objets.

**Comment ça fonctionne ?** Trois relations suffisent ici :

| Relation | Lecture | Dans le projet |
|---|---|---|
| `ManyToOne` | plusieurs X pour un Y | plusieurs enfants pour un parent |
| `OneToMany` | l'inverse du précédent | la collection d'enfants d'un parent |
| `OneToOne` | un pour un | un enfant ↔ un compte de connexion |

**Exemple commenté.**

```php
class Enfant
{
    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'enfants')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $parent = null;
    // Cette classe porte la clé étrangère parent_id : c'est le CÔTÉ PROPRIÉTAIRE.
}

class User
{
    /** @var Collection<int, Enfant> */
    #[ORM\OneToMany(targetEntity: Enfant::class, mappedBy: 'parent', cascade: ['remove'])]
    private Collection $enfants;
    // Côté INVERSE : aucune colonne en base, c'est du confort de lecture.
}
```

À retenir : `inversedBy` et `mappedBy` se répondent. Le côté qui porte
`JoinColumn` est celui qui a la colonne en base ; c'est lui que Doctrine regarde
pour enregistrer le lien.

**Dans ce projet.** L'enfant a **deux** relations vers `User` : son `parent`
(qui le gère) et son `compte` (avec lequel il se connecte). Deux rôles
différents, donc deux relations.

---

### Concept 2 — Les cascades

**Pourquoi ?** Supprimer un parent doit supprimer ses enfants, leurs comptes,
leurs journaux. Sinon la base se remplit de lignes orphelines — et de données
personnelles qui auraient dû disparaître.

**Comment ça fonctionne ?** Deux mécanismes, souvent confondus :

| | Où | Qui l'exécute | Quand |
|---|---|---|---|
| `cascade: ['remove']` | dans le mapping Doctrine | **PHP** | quand vous appelez `remove()` sur l'objet parent |
| `onDelete: 'CASCADE'` | sur la `JoinColumn` | **MySQL** | quand la ligne parente est supprimée, par n'importe quel moyen |

**Dans ce projet.** Les deux sont utilisés, volontairement :

```text
Parent ──► Enfants ──► Compte de connexion (cascade Doctrine : c'est un OneToOne)
                  └──► Journaux ──► Douleurs (onDelete en base : c'est massif)
```

`cascade: ['persist']` existe aussi : enregistrer l'enfant enregistre son compte
en même temps, sans `persist()` séparé.

⚠️ Règle du projet : **pas de classe « manager » de suppression**. On configure
les cascades, on ne les réimplémente pas en PHP.

---

### Concept 3 — Le voter

**Pourquoi ?** `access_control` protège une **URL**. Mais
`/parent/enfants/12/modifier` est autorisée à **tous** les parents : il faut
vérifier que l'enfant 12 appartient **à celui qui est connecté**. C'est une
décision sur un **objet**, pas sur une URL.

**Comment ça fonctionne ?** Un voter répond à la question « cet utilisateur
a-t-il le droit de faire CETTE action sur CET objet ? ».

```php
class EnfantVoter extends Voter
{
    public const GERER = 'ENFANT_GERER';

    protected function supports(string $attribute, mixed $subject): bool
    {
        // Ce voter ne se prononce que sur cette action et ce type d'objet
        return self::GERER === $attribute && $subject instanceof Enfant;
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
$this->denyAccessUnlessGranted(EnfantVoter::GERER, $enfant);   // sinon : 403
```

**À retenir.** `access_control` = grosse maille (par URL).
Voter = maille fine (par objet). Les deux se complètent.

---

### Concept 4 — Les constantes d'entité

**Pourquoi ?** Il faut une liste fermée d'avatars, avec pour chacun un emoji, un
nom et une couleur.

**Comment ça fonctionne ?** Une constante de classe, et des getters d'affichage.

```php
public const AVATARS = [
    'renard' => ['emoji' => '🦊', 'nom' => 'Renard malin', 'couleur' => '#F59E0B'],
    'panda'  => ['emoji' => '🐼', 'nom' => 'Panda calme',  'couleur' => '#64748B'],
];

public function getAvatarEmoji(): string
{
    return self::AVATARS[$this->avatar]['emoji'] ?? '🙂';
}
```

La **clé** (`renard`) est enregistrée en base ; l'emoji et le nom ne servent
qu'à l'affichage. Le `?? '🙂'` évite une erreur si une ancienne valeur traîne en
base.

**Dans ce projet.** Règle explicite : **pas d'enum PHP**. Les listes fixes
(avatars, zones du corps, types de contenu, règles de conseil) sont des
constantes dans l'entité concernée. C'est plus simple à lire pour un débutant et
cela évite les migrations de type.

---

### Concept 5 — L'extension Twig

**Pourquoi ?** « 150 minutes » n'a aucun sens pour un enfant ; « 2 h 30 » si. Ce
formatage est utilisé dans une dizaine de gabarits : il ne doit exister qu'une
fois.

**Comment ça fonctionne ?** Une classe déclare un filtre, utilisable partout dans
Twig.

```php
class DureeExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [new TwigFilter('duree', [self::class, 'formater'])];
    }

    public static function formater(?int $minutes): string
    {
        $minutes = max(0, (int) $minutes);
        $heures = intdiv($minutes, 60);
        $reste = $minutes % 60;

        if (0 === $heures) { return $reste.' min'; }
        if (0 === $reste)  { return $heures.' h'; }

        return sprintf('%d h %02d', $heures, $reste);
    }
}
```

```twig
{{ enfant.maxMinutesJour|duree }}   {# 90 → « 1 h 30 » #}
```

La méthode est `static` : le service `ConseilService` (phase 09) la réutilisera
directement, sans passer par Twig.

---

### Concept 6 — Le transformer de données

**Pourquoi ?** Un `<input type="range">` renvoie **toujours** une chaîne
(`"120"`). La propriété `maxMinutesJour` est un `int`. Sans conversion, le
formulaire échoue avec un message incompréhensible.

**Comment ça fonctionne ?** Un transformer convertit dans les deux sens :

```php
$builder->get('maxMinutesJour')->addModelTransformer(new CallbackTransformer(
    fn (?int $minutes) => (string) $minutes,                        // objet → formulaire
    fn (?string $valeur) => is_numeric($valeur) ? (int) $valeur : null, // formulaire → objet
));
```

Le `null` en cas de valeur non numérique est volontaire : la **validation**
affichera alors un message propre, au lieu d'une erreur technique.

---

### Concept 7 — Le CSRF hors formulaire Symfony

**Pourquoi ?** Le bouton « Supprimer » n'est pas un formulaire de saisie, mais
c'est une action destructrice : elle doit être protégée comme les autres.

**Comment ça fonctionne ?** On génère le jeton dans le gabarit et on le vérifie
dans le contrôleur.

```twig
<form method="post" action="{{ path('parent_enfant_supprimer', {id: enfant.id}) }}"
      onsubmit="return confirm('Supprimer définitivement le profil ?');">
    <input type="hidden" name="_token" value="{{ csrf_token('supprimer-enfant-' ~ enfant.id) }}">
    <button type="submit">🗑️ Supprimer</button>
</form>
```

```php
if (!$this->isCsrfTokenValid('supprimer-enfant-'.$enfant->getId(), $request->getPayload()->getString('_token'))) {
    throw $this->createAccessDeniedException('Jeton CSRF invalide.');
}
```

Le jeton inclut l'identifiant : celui de l'enfant 3 ne vaut pas pour l'enfant 4.
Le `confirm()` protège de la fausse manœuvre, **pas** de l'attaque : les deux
sont nécessaires.

---

## 4. Explications avec exemples

### Créer un enfant **et** son compte, en une seule opération

```php
$compte = new User();
$compte->setUsername($userRepository->genererUsername($enfant->getPrenom())); // « lea », « lea2 »…
$compte->setRoles([User::ROLE_CHILD]);
$compte->setPassword($passwordHasher->hashPassword($compte, $form->get('motDePasse')->getData()));

$enfant->setCompte($compte);

$entityManager->persist($enfant);   // le compte suit, grâce à cascade: ['persist']
$entityManager->flush();
```

Un seul `persist()` pour deux objets : c'est la cascade qui s'en charge. C'est
aussi pour cela que `Enfant::compte` est **non nullable** : un profil sans compte
n'existe pas dans ce projet, donc aucun cas particulier à gérer ensuite.

### Générer un identifiant libre

```php
public function genererUsername(string $prenom): string
{
    $base = (new AsciiSlugger())->slug($prenom, '')->lower()->toString(); // « Léa » → « lea »
    $base = substr($base, 0, 40) ?: 'enfant';

    $username = $base;
    $numero = 1;

    while (null !== $this->findOneBy(['username' => $username])) {
        ++$numero;
        $username = $base.$numero;      // lea2, lea3…
    }

    return $username;
}
```

Une boucle simple, lisible de haut en bas. Pas de génération aléatoire : un
enfant de 8 ans doit pouvoir **retenir** son identifiant.

### Un formulaire, deux usages

```php
$resolver->setDefaults([
    'data_class' => Enfant::class,
    'creation' => false,        // option personnalisée
]);
```

```php
if ($options['creation']) {
    $builder->add('motDePasse', TextType::class, ['mapped' => false, /* … */]);
}
```

À la création, le formulaire demande un mot de passe ; à la modification, non. Un
seul `*Type`, un seul partiel Twig, deux comportements.

---

## 5. Commandes

### `docker compose exec app php bin/console make:entity Enfant`

- **Ce qu'elle fait** : crée l'entité et son repository, et propose d'ajouter les
  relations (`relation` comme type de champ).
- **À observer** : le générateur écrit **les deux côtés** de la relation et les
  méthodes `addEnfant()` / `removeEnfant()`. Relisez-les.

### `docker compose exec app php bin/console make:voter EnfantVoter`

- **Ce qu'elle fait** : génère le squelette d'un voter avec `supports()` et
  `voteOnAttribute()`.
- **À observer** : le voter est automatiquement enregistré comme service, sans
  configuration.

### `docker compose exec app php bin/console make:migration` puis `doctrine:migrations:migrate`

- **À observer** : le SQL doit contenir la création de `enfant` **et** les deux
  clés étrangères, dont une avec `ON DELETE CASCADE`.

### `docker compose exec app php bin/console doctrine:schema:validate`

- **Quand** : après toute modification de relation. Un mapping bancal se voit
  immédiatement ici.

### `docker compose exec app php bin/console debug:twig --filter=duree`

- **Ce qu'elle fait** : vérifie que votre filtre est bien enregistré.
- **Quand** : « Unknown "duree" filter ».

---

## 6. Concepts Symfony de la phase

| Composant | Rôle ici |
|---|---|
| **Doctrine ORM** | relations, cascades, collections |
| **Security (Voter)** | autorisation sur un objet précis |
| **Form** | option personnalisée, champ non mappé, transformer |
| **Validator** | âge 8-14 ans, limite 15-480 par pas de 15 |
| **Twig (extension)** | le filtre `duree` |
| **String (Slugger)** | génération de l'identifiant |

---

## 7. Architecture et organisation du code

```text
src/
├── Controller/Parent/
│   └── EnfantController.php      liste, création, modification, suppression
├── Entity/
│   ├── Enfant.php                profil + constantes AVATARS et LIMITE_*
│   └── User.php                  modifié : relations enfants / profilEnfant
├── Form/
│   ├── EnfantType.php            option « creation »
│   └── MotDePasseType.php        réutilisé par le parent ET l'enfant
├── Repository/
│   └── EnfantRepository.php      findByParent()
├── Security/
│   └── EnfantVoter.php           ENFANT_GERER
└── Twig/
    └── DureeExtension.php        filtre « duree »

templates/
├── parent/
│   ├── layout.html.twig          menu de l'espace parent
│   └── enfants/                  index, nouveau, modifier, _formulaire
├── form/avatars.html.twig        galerie d'avatars
└── _partials/bouton_supprimer.html.twig
```

Pourquoi un dossier `Controller/Parent/` : chaque espace a ses contrôleurs, ce
qui rend la structure lisible dès le premier coup d'œil.

---

## 8. Flux de fonctionnement

### Création d'un enfant

```text
Navigateur : POST /parent/enfants/nouveau
    ↓
access_control : ROLE_PARENT requis
    ↓
EnfantController::nouveau()
    ↓
EnfantType : remplit l'objet Enfant + le champ non mappé « motDePasse »
    ↓
Validator : âge 8-14 ans, limite valide, mot de passe ≥ 6 caractères
    ↓
Contrôleur : crée le User enfant, génère l'identifiant, hache le mot de passe
    ↓
persist(enfant) + flush()  →  cascade persist : le compte est créé aussi
    ↓
message flash avec l'identifiant  →  redirection vers la liste
```

### Modification d'un enfant qui n'est pas le vôtre

```text
GET /parent/enfants/42/modifier
    ↓
access_control : ROLE_PARENT → OK (c'est bien un parent)
    ↓
Doctrine : charge l'enfant 42
    ↓
EnfantVoter : ce parent est-il celui de l'enfant 42 ?  → NON
    ↓
403 Accès refusé
```

---

## 9. Application au projet

**Pourquoi cette phase ?** C'est le parent qui ouvre l'accès à son enfant : sans
profil enfant, pas de journal, pas de conseils, pas de suivi. C'est aussi ici que
se joue le **cloisonnement entre familles**, une exigence forte du projet.

**Composants utilisés** : Doctrine (relations), Security (voter), Form,
Validator, Twig (extension).

**Fichiers créés** : voir l'arborescence ci-dessus.

**Pourquoi cette architecture ?**

- **Compte enfant obligatoire** (`compte` non nullable) : un seul cas à gérer,
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
→ Solution : `$enfant->setParent($parent)` avant `persist()`.

**Le compte de l'enfant n'est pas enregistré**
→ Il manque `cascade: ['persist']` sur la relation `compte`.
→ Signe : erreur « A new entity was found through the relationship… ».

**Supprimer un enfant laisse son compte en base**
→ Il manque `cascade: ['remove']` sur la même relation.
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

**La suppression renvoie 403 alors que le bouton vient du site**
→ Le nom du jeton CSRF du gabarit ne correspond pas à celui du contrôleur.
→ Solution : la **même chaîne** des deux côtés, identifiant inclus.

---

## 11. Bonnes pratiques

- **Une requête = une méthode de repository**, nommée en français
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
2. Dans phpMyAdmin, ouvrez la table `enfant` : repérez les colonnes `parent_id`
   et `compte_id`, puis retrouvez les deux comptes correspondants dans `users`.
3. Supprimez un enfant depuis l'interface, puis vérifiez dans `users` que son
   compte a bien disparu. Quelle option de mapping l'a provoqué ?
4. Connectez-vous avec un **second** compte parent, puis tentez d'ouvrir
   `/parent/enfants/1/modifier` (l'enfant du premier parent) : vous devez obtenir
   un 403. Retirez temporairement l'appel au voter, rechargez : la page s'affiche.
   **Remettez l'appel** et expliquez ce que vous venez de démontrer.
5. Affichez `{{ 150|duree }}` dans un gabarit : vous devez lire « 2 h 30 ».

---

## 13. Scénario de test manuel

1. Connecté en parent, ouvrir `/parent/enfants` puis « Ajouter un enfant ».
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

➡️ [Phase suivante](./phase-06.md)

➡️ [Phase de développement](../README.md#phase-05--espace-parent--profils-enfants)

➡️ [Prompt Claude Code](../prompts/phase-05.md)
