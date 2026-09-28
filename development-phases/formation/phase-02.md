# Formation — Phase 02 : Base de données et entités

## 1. Objectifs pédagogiques

À la fin de cette leçon, vous devez être capable de :

- expliquer ce qu'est un **ORM** et ce qu'il vous évite d'écrire ;
- déclarer une **entité** Doctrine et comprendre chaque attribut de mapping ;
- déclarer les trois **relations** du projet (`ManyToOne`, `OneToMany`,
  `OneToOne`) et expliquer ce qu'est le **côté propriétaire** ;
- distinguer `cascade: ['remove']` (Doctrine) de `onDelete: 'CASCADE'` (base) ;
- remplacer un enum par des **constantes d'entité** et des **getters
  d'affichage** ;
- poser des **contraintes de validation** sur une entité, en sachant qu'elles ne
  serviront qu'avec les formulaires (phase 04) ;
- garantir une règle métier **en base** avec un index unique ;
- choisir le bon **type de date** (`date_immutable`, `datetime_immutable`) ;
- expliquer le rôle d'un **repository** et pourquoi les requêtes y vivent ;
- comprendre ce qu'est une **migration** et pourquoi on ne modifie jamais la base
  à la main ;
- lire une `DATABASE_URL` et savoir où la configurer.

## 2. Prérequis

- Phase 01 terminée : les conteneurs `app`, `database` et `phpmyadmin`
  tournent, et **tous les paquets** sont installés (Doctrine, MakerBundle,
  Validator, SecurityBundle…).
- Savoir ce qu'est une table, une colonne, une clé primaire, une clé étrangère,
  un index.
- Savoir lire une classe PHP avec des propriétés privées et des getters/setters.

---

## 3. Concepts à apprendre

### Concept 1 — L'ORM (Object-Relational Mapping)

**Pourquoi ?** Écrire du SQL à la main partout dans l'application, c'est du code
répétitif, difficile à relire, et une porte ouverte aux injections SQL si l'on
concatène des variables.

**Comment ça fonctionne ?** Un ORM fait correspondre :

```text
une classe PHP   ←→   une table
un objet         ←→   une ligne
une propriété    ←→   une colonne
une relation     ←→   une clé étrangère
```

Vous manipulez des objets ; Doctrine génère le SQL.

**Exemple.**

```php
$user = new User();
$user->setEmail('parent@digisante.local');
$entityManager->persist($user);   // « je veux enregistrer cet objet »
$entityManager->flush();          // exécute réellement le INSERT
```

`persist()` **prépare**, `flush()` **exécute**. Oublier `flush()` est l'erreur
classique du débutant : rien ne part en base, et aucune erreur ne s'affiche.
(Vous écrirez ce code en phase 04 ; ici, on ne fait que **décrire** les données.)

**Dans ce projet.** Doctrine ORM 3 gère **cinq entités**, toutes créées dans
cette phase :

| Entité | Table | Ce qu'elle représente | Utilisée à partir de |
|---|---|---|---|
| `User` | `users` | un compte de connexion (admin, parent ou enfant) | phase 04 |
| `Enfant` | `enfant` | le profil d'un enfant, géré par son parent | phase 05 |
| `JournalEntree` | `journal_entree` | le journal d'une journée : minutes par écran | phase 07 |
| `DouleurZone` | `douleur_zone` | une douleur signalée sur le schéma du corps | phase 07 |
| `ContenuBienEtre` | `contenu_bien_etre` | une fiche, vidéo, quiz… de la bibliothèque | phase 08 |

Pourquoi tout d'un coup ? Parce que le **modèle de données** se pense en entier :
les relations entre ces cinq classes forment un tout. Les phases suivantes
n'auront plus qu'à **utiliser** ces entités, sans toucher au schéma.

---

### Concept 2 — L'entité et son mapping

**Pourquoi ?** Doctrine doit savoir quelle classe correspond à quelle table, et
quel type SQL donner à chaque propriété.

**Comment ça fonctionne ?** On le déclare avec des **attributs PHP** posés sur la
classe et sur les propriétés.

**Exemple commenté.**

```php
#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users')]          // « user » est un mot réservé en SQL
class User
{
    #[ORM\Id]                        // clé primaire
    #[ORM\GeneratedValue]            // auto-incrémentée par MySQL
    #[ORM\Column]
    private ?int $id = null;         // null tant que l'objet n'est pas enregistré

    #[ORM\Column(length: 180, unique: true, nullable: true)]
    private ?string $email = null;
    //    ^^^^^^^ 180 caractères, index unique, peut être NULL
}
```

Pourquoi `email` est-il **nullable** alors qu'il semble obligatoire ? Parce que
les **enfants** se connecteront avec un identifiant (`username`), sans email
(phase 06). La colonne autorise donc `NULL`, et c'est le **formulaire
d'inscription** du parent qui exigera l'email (phase 04).

Règle du projet à retenir : les setters liés à un formulaire acceptent `null`
(`?string`). Sinon, un champ laissé vide provoque une erreur 500 **avant** que
la validation ait pu afficher un message propre.

---

### Concept 3 — Les relations entre entités

**Pourquoi ?** Les données du monde réel sont liées : un parent **a** des
enfants, un enfant **appartient** à un parent, un journal **appartient** à un
enfant. En base, ce lien est une clé étrangère ; côté PHP, on veut manipuler des
objets (`$enfant->getParent()`).

**Comment ça fonctionne ?** Trois relations suffisent dans tout le projet :

| Relation | Lecture | Dans le projet |
|---|---|---|
| `ManyToOne` | plusieurs X pour un Y | plusieurs enfants pour un parent, plusieurs journaux pour un enfant, plusieurs douleurs pour un journal |
| `OneToMany` | l'inverse du précédent | `User::enfants`, `Enfant::journalEntrees`, `JournalEntree::douleurs` |
| `OneToOne` | un pour un | un enfant ↔ son compte de connexion (`Enfant::compte` / `User::profilEnfant`) |

**Exemple commenté.**

```php
class Enfant
{
    #[ORM\ManyToOne(inversedBy: 'enfants')]
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

À retenir :

- `inversedBy` (côté propriétaire) et `mappedBy` (côté inverse) se répondent ;
- le côté qui porte la `JoinColumn` est celui qui a la **colonne en base** ;
  c'est lui que Doctrine regarde pour enregistrer le lien ;
- une collection (`OneToMany`) s'initialise dans le constructeur :
  `$this->enfants = new ArrayCollection();`.

**Dans ce projet.** L'enfant a **deux** relations vers `User` : son `parent`
(qui le gère, `ManyToOne`) et son `compte` (avec lequel il se connecte,
`OneToOne`). Deux rôles différents, donc deux relations. Le compte est **non
nullable** : un profil sans compte n'existe pas dans ce projet, ce qui supprime
tout cas particulier à gérer plus tard.

---

### Concept 4 — Les cascades

**Pourquoi ?** Supprimer un parent doit supprimer ses enfants, leurs comptes,
leurs journaux et leurs douleurs. Sinon la base se remplit de lignes orphelines —
et de données personnelles d'enfants qui auraient dû disparaître.

**Comment ça fonctionne ?** Deux mécanismes, souvent confondus :

| | Où | Qui l'exécute | Quand |
|---|---|---|---|
| `cascade: ['remove']` | dans le mapping Doctrine | **PHP** (Doctrine) | quand vous appelez `remove()` sur l'objet parent |
| `onDelete: 'CASCADE'` | sur la `JoinColumn` | **MySQL** | quand la ligne parente est supprimée, par n'importe quel moyen |

**Dans ce projet.** Les **deux** sont utilisés, volontairement, sur toute la
chaîne :

```text
Parent ──► Enfants ──► Compte de connexion
                  └──► Journaux ──► Douleurs
```

- `cascade: ['remove']` côté Doctrine sur chaque maillon (`User::enfants`,
  `Enfant::compte`, `Enfant::journalEntrees`, `JournalEntree::douleurs`) :
  c'est ce qui agit quand un contrôleur appelle `remove()` (phases 05 et 11) ;
- `onDelete: 'CASCADE'` sur les clés étrangères (`parent_id`, `enfant_id`,
  `journal_entree_id`) : un **filet de sécurité** si une ligne est supprimée
  hors de Doctrine (SQL direct, phpMyAdmin).

`cascade: ['persist']` existe aussi : enregistrer l'enfant enregistre son compte
en même temps (`Enfant::compte`), et enregistrer un journal enregistre ses
douleurs (`JournalEntree::douleurs`), sans `persist()` séparé.

⚠️ Règle du projet : **pas de classe « manager » de suppression**. On configure
les cascades, on ne les réimplémente pas en PHP.

---

### Concept 5 — Des constantes plutôt que des enums

**Pourquoi ?** Plusieurs données prennent leurs valeurs dans une **liste
fermée** : les avatars, les écrans, les zones du corps, les types de contenu, les
règles de conseil. Chaque valeur a en plus un libellé ou un emoji à afficher.

**Comment ça fonctionne ?** Une constante de classe, et des getters d'affichage.

```php
public const AVATARS = [
    'renard' => ['emoji' => '🦊', 'nom' => 'Renard', 'couleur' => '#fde2c8'],
    'panda'  => ['emoji' => '🐼', 'nom' => 'Panda',  'couleur' => '#e8ecef'],
    // …
];

public function getAvatarEmoji(): string
{
    return self::AVATARS[$this->avatar]['emoji'] ?? '🙂';
}
```

La **clé** (`renard`) est enregistrée en base, dans une simple colonne texte ;
l'emoji et le nom ne servent qu'à l'affichage. Le `?? '🙂'` évite une erreur si
une ancienne valeur traîne en base. Dans un gabarit (Twig, vu en phase 03),
`{{ enfant.avatarEmoji }}` appellera `getAvatarEmoji()`.

**Dans ce projet.** Règle explicite : **pas d'enum PHP**. Les listes fixes sont
des constantes dans l'entité concernée :

| Constante | Getters d'affichage |
|---|---|
| `User::ROLE_ADMIN`, `ROLE_PARENT`, `ROLE_CHILD` | `isParent()` |
| `Enfant::AVATARS`, `LIMITE_MIN/MAX/PAS/DEFAUT`, `AGE_MIN/MAX` | `getAvatarEmoji()`, `getAvatarNom()`, `getNomComplet()`, `getAge()` |
| `JournalEntree::ECRANS`, `SEUIL_ORANGE` (120), `SEUIL_ROUGE` (240) | `getTotalEcran()`, `niveauPourMinutes()`, `getNiveauEcran()` |
| `DouleurZone::ZONES` (yeux, cou, epaule, dos, poignet, main) | `getZoneLabel()`, `getZoneEmoji()` |
| `ContenuBienEtre::TYPES`, `DECLENCHEURS` | `getTypeLabel()`, `getTypeEmoji()`, `getDeclencheurLabel()` |

C'est plus simple à lire pour un débutant, et cela évite un type de colonne
spécial. Ces getters sont de **l'affichage calculé**, pas de la logique métier :
ils ont leur place dans l'entité, pas dans Twig.

⚠️ **Leçon apprise sur ce projet** : `ContenuBienEtre::DECLENCHEURS` ne contient
que les **trois** règles réellement appliquées par le moteur de conseils
(phase 09) : `20-20-20`, `etirement_cervical`, `yoga_yeux`, chacune avec sa
constante nommée (`DECLENCHEUR_20_20_20`…). Ne proposez jamais une option qui ne
produit aucun effet.

---

### Concept 6 — Les contraintes de validation sur l'entité

**Pourquoi ?** La base garantit la cohérence **technique** (unicité, type,
`NOT NULL`), mais pas les règles **métier** : « cet email doit ressembler à un
email », « l'enfant a entre 8 et 14 ans », « la limite d'écran se règle par
tranches de 15 minutes ».

**Comment ça fonctionne ?** Des attributs `#[Assert\…]` sur les propriétés, et
`#[UniqueEntity]` sur la classe.

```php
#[UniqueEntity(fields: ['email'], message: 'Cette adresse email est déjà utilisée.')]
class User
{
    #[Assert\Email(message: "Cette adresse email n'est pas valide.")]
    private ?string $email = null;
}
```

```php
class Enfant
{
    #[ORM\Column]
    #[Assert\Range(
        notInRangeMessage: 'La limite doit être comprise entre 15 minutes et 8 heures.',
        min: self::LIMITE_MIN,
        max: self::LIMITE_MAX,
    )]
    #[Assert\DivisibleBy(self::LIMITE_PAS, message: 'La limite se règle par tranches de 15 minutes.')]
    private ?int $maxMinutesJour = self::LIMITE_DEFAUT;
}
```

⚠️ **Une contrainte ne fait rien toute seule.** Poser `#[Assert\Email]` ne
bloque aucun `INSERT` : c'est le **validateur** qui lit ces attributs, et il est
appelé par `$form->isValid()`. Les contraintes posées aujourd'hui **prendront
vie en phase 04**, avec les formulaires. On les déclare maintenant parce que ces
règles appartiennent à la **donnée**, pas à un formulaire particulier : le même
`Enfant` sera validé à la création et à la modification.

`UniqueEntity` et `unique: true` sont complémentaires : le premier affiche un
message propre dans le formulaire, le second garantit l'unicité en base.

Message toujours **en français et écrit pour l'utilisateur** : « Cette adresse
email est déjà utilisée. », pas « UNIQUE constraint violation ».

---

### Concept 7 — Une règle garantie par la base : l'index unique

**Pourquoi ?** « Un seul journal par enfant et par jour » doit rester vrai même
en cas de double clic, d'onglet dupliqué ou de bug futur. Une vérification PHP
seule laisse une fenêtre : deux requêtes simultanées peuvent passer toutes les
deux.

**Comment ça fonctionne ?** Un **index unique sur deux colonnes**, déclaré sur la
classe :

```php
#[ORM\Entity(repositoryClass: JournalEntreeRepository::class)]
#[ORM\UniqueConstraint(name: 'journal_unique_par_jour', columns: ['enfant_id', 'date'])]
class JournalEntree
```

MySQL refusera physiquement le doublon. En phase 07, le contrôleur vérifiera
**en plus** et redirigera proprement : le confort côté PHP, la garantie côté base.

**Dans ce projet.** Quatre index uniques au total : `users.email`,
`users.username`, `enfant.compte_id` (créé par la relation `OneToOne`) et
`journal_entree (enfant_id, date)`.

---

### Concept 8 — Les types de dates

**Pourquoi ?** Un anniversaire est un **jour** ; une date de création est un
**instant**. Les confondre donne des comparaisons fausses (« le journal
d'aujourd'hui » à 14 h 32 n'est pas égal à « aujourd'hui »).

**Comment ça fonctionne ?**

| Type Doctrine | Colonne MySQL | Objet PHP | Dans le projet |
|---|---|---|---|
| `date_immutable` (`Types::DATE_IMMUTABLE`) | `DATE` | `\DateTimeImmutable` à minuit | `Enfant::dateNaissance`, `JournalEntree::date` |
| `datetime_immutable` (`Types::DATETIME_IMMUTABLE`) | `DATETIME` | `\DateTimeImmutable` | `User::createdAt`, `ContenuBienEtre::createdAt` |

Les objets **immuables** ne peuvent pas être modifiés par accident :
`$date->modify('+1 day')` renvoie une **nouvelle** date au lieu de changer
l'ancienne.

```php
public function __construct()
{
    $this->date = new \DateTimeImmutable('today');   // aujourd'hui, à minuit
    $this->douleurs = new ArrayCollection();
}
```

Rappel de la phase 01 : PHP et MySQL sont tous deux réglés sur `Europe/Paris`.
Sans cela, `new \DateTimeImmutable('today')` et `CURDATE()` ne désigneraient pas
le même jour autour de minuit.

---

### Concept 9 — Le repository

**Pourquoi ?** Il faut un endroit unique où vivent les requêtes d'une entité :
sinon les mêmes requêtes se dupliquent dans plusieurs contrôleurs, avec des
variantes subtiles.

**Comment ça fonctionne ?** Chaque entité a un repository, créé par
`make:entity`. Il hérite de méthodes toutes faites, et vous y ajoutez les vôtres.

```php
$userRepository->find(12);                               // par identifiant
$userRepository->findOneBy(['email' => 'a@b.fr']);        // un seul résultat
$userRepository->findBy(['ville' => 'Lyon']);            // une liste
$userRepository->findAll();                               // tout
```

Pour **trier**, on écrit une méthode du repository avec le QueryBuilder (détaillé
en phase 10) :
`->orderBy('u.email')` (croissant par défaut) ou
`->orderBy('u.email', \SortDirection::Descending)`. Règle du projet : jamais
`'ASC'` / `'DESC'` en chaîne — c'est déprécié dans `QueryBuilder::orderBy()` et
`#[ORM\OrderBy]`.

**Dans ce projet.** Règle stricte : **aucune requête dans un contrôleur**. Les
cinq repositories restent **vides** dans cette phase : chaque méthode
(`findByParent()`, `findAujourdhui()`, `findParents()`…) sera écrite **dans la
phase qui l'utilise**, avec un nom explicite en français. On n'écrit pas une
requête dont personne n'a encore besoin.

---

### Concept 10 — Les migrations

**Pourquoi ?** La base de votre collègue et celle de production doivent recevoir
**les mêmes changements, dans le même ordre**. Les appliquer à la main est
impossible à tenir.

**Comment ça fonctionne ?**

```text
1. vous modifiez une entité
2. make:migration compare vos entités à la base et génère un fichier SQL horodaté
3. vous RELISEZ ce fichier
4. doctrine:migrations:migrate l'exécute et note qu'il est appliqué
```

Doctrine garde la trace des migrations déjà passées dans une table dédiée
(`doctrine_migration_versions`) : la même migration ne s'exécute jamais deux fois.

**Exemple.**

```php
public function up(Schema $schema): void
{
    $this->addSql('CREATE TABLE enfant (id INT AUTO_INCREMENT NOT NULL, parent_id INT NOT NULL, …)');
    $this->addSql('ALTER TABLE enfant ADD CONSTRAINT FK_… FOREIGN KEY (parent_id) REFERENCES users (id) ON DELETE CASCADE');
}
```

**Dans ce projet.** Trois règles :

1. toute modification de schéma passe par une migration ;
2. on ne modifie **jamais** la base à la main (ni via phpMyAdmin) ;
3. on ne modifie **jamais** une migration déjà partagée — on en crée une
   nouvelle.

Vous pouvez générer **une** migration après avoir écrit les cinq entités, ou une
par entité au fil de l'eau : les deux sont corrects, l'important est de relire
chaque fichier.

---

### Concept 11 — `DATABASE_URL` et la connexion

**Pourquoi ?** L'application doit savoir où est la base, avec quel utilisateur.

**Comment ça fonctionne ?** Une seule variable d'environnement contient tout :

```dotenv
DATABASE_URL="mysql://digisante:digisante@database:3306/digisante_junior?serverVersion=8.0.36&charset=utf8mb4"
#              ^^^^^  ^^^^^^^^^ ^^^^^^^^^ ^^^^^^^^ ^^^^ ^^^^^^^^^^^^^^^^
#              type   user      password  hôte     port  base
```

⚠️ L'hôte est `database` — le **nom du service Docker** — et le port `3306`,
car l'application parle à MySQL **depuis l'intérieur** du réseau Docker. Depuis
votre machine (DBeaver par exemple), c'est `127.0.0.1:3308`. phpMyAdmin, lui,
est dans le réseau Docker : il utilise `database:3306` (`PMA_HOST`, phase 01).

| Fichier | Rôle |
|---|---|
| `.env` | valeur par défaut, versionnée, écrite par la recette Doctrine en phase 01 |
| `.env.local` | vos réglages personnels, jamais versionnés |
| `compose.yaml` | fournit directement la bonne `DATABASE_URL` au conteneur `app` |

⚠️ Une **vraie** variable d'environnement (fournie par `compose.yaml`) l'emporte
sur `.env` **et** sur `.env.local` : dans ce projet, `.env` ne sert que de valeur
de repli, et modifier `DATABASE_URL` dans `.env.local` n'aurait aucun effet dans
le conteneur.

---

## 4. Explications avec exemples

### L'entité `User`, et pourquoi elle est particulière

```php
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    public const ROLE_ADMIN = 'ROLE_ADMIN';
    public const ROLE_PARENT = 'ROLE_PARENT';
    public const ROLE_CHILD = 'ROLE_CHILD';

    #[ORM\Column(type: Types::JSON)]
    private array $roles = [];

    // Côté parent : ses enfants, supprimés avec lui.
    #[ORM\OneToMany(targetEntity: Enfant::class, mappedBy: 'parent', cascade: ['remove'])]
    private Collection $enfants;

    // Côté enfant : le profil rattaché à ce compte (côté inverse du OneToOne).
    #[ORM\OneToOne(mappedBy: 'compte')]
    private ?Enfant $profilEnfant = null;

    /** Identifiant utilisé par Symfony Security : l'email, sinon le username. */
    public function getUserIdentifier(): string
    {
        return (string) ($this->email ?? $this->username);
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';                    // tout le monde a au moins ce rôle
        return array_values(array_unique($roles));
    }

    /** Méthode imposée par UserInterface : rien à effacer ici. */
    #[\Deprecated]
    public function eraseCredentials(): void
    {
    }
}
```

- `implements UserInterface` : le **contrat** que Symfony Security attend d'une
  classe d'utilisateur (phase 04). On l'écrit dès maintenant pour que l'entité
  soit complète du premier coup — une interface ne change pas le schéma, elle
  n'a aucun effet sur les migrations. `symfony/security-bundle` est installé
  depuis la phase 01 ; son `security.yaml` reste tel quel jusqu'en phase 04.
- `eraseCredentials()` est dépréciée depuis Symfony 7.3 mais encore imposée par
  l'interface : on la garde **vide**, avec l'attribut `#[\Deprecated]`. Sans lui,
  Symfony signale une dépréciation à chaque requête (visible dans la barre de
  debug à partir de la phase 03).
- `roles` est de type `json` : MySQL stocke `["ROLE_PARENT"]`. Pratique, mais
  cela complique les recherches — on le verra en phase 11.
- Un seul `User` pour trois rôles : un parent et un admin se connectent par
  **email**, un enfant par **identifiant** ; d'où deux colonnes nullables et
  uniques.

### Normaliser une donnée dans le setter

```php
public function setEmail(?string $email): static
{
    // On enregistre toujours l'email en minuscules, sans espaces autour.
    $this->email = $email ? mb_strtolower(trim($email)) : null;

    return $this;
}
```

Pourquoi ? Sans cela, `Parent@Digisante.local` et `parent@digisante.local`
seraient deux comptes différents, et l'index unique ne servirait à rien. Même
traitement pour `setUsername()`.

### L'entité `Enfant` : deux relations vers `User`, des contraintes métier

```php
#[ORM\Entity(repositoryClass: EnfantRepository::class)]
class Enfant
{
    #[ORM\ManyToOne(inversedBy: 'enfants')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $parent = null;

    // Le compte part avec le profil : supprimer l'enfant supprime son compte.
    #[ORM\OneToOne(inversedBy: 'profilEnfant', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $compte = null;

    // Entre 8 ans révolus (aujourd'hui) et 14 ans révolus (veille des 15 ans).
    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    #[Assert\NotNull(message: 'Merci de saisir la date de naissance.')]
    #[Assert\Range(
        notInRangeMessage: "L'application est réservée aux enfants de 8 à 14 ans.",
        min: 'today -15 years +1 day',
        max: 'today -8 years',
    )]
    private ?\DateTimeImmutable $dateNaissance = null;

    /** @var Collection<int, JournalEntree> */
    #[ORM\OneToMany(targetEntity: JournalEntree::class, mappedBy: 'enfant', cascade: ['remove'])]
    private Collection $journalEntrees;

    /** Âge en années révolues. */
    public function getAge(): ?int
    {
        return $this->dateNaissance?->diff(new \DateTimeImmutable('today'))->y;
    }
}
```

Autres propriétés : `prenom` et `nom` (80 caractères, `NotBlank`), `avatar`
(clé de `AVATARS`, `Assert\Choice`), `maxMinutesJour` (15 à 480, par pas de 15,
120 par défaut).

### Les entités du journal et de la bibliothèque

```php
class JournalEntree
{
    /** Propriété => libellé : sert au formulaire et à l'affichage. */
    public const ECRANS = [
        'ecranTv' => '📺 Télévision',
        'ecranOrdinateur' => '💻 Ordinateur',
        // … smartphone, tablette, console, autre
    ];

    #[ORM\Column]
    private int $ecranTv = 0;          // six colonnes int, 0 par défaut

    public function getTotalEcran(): int { /* somme des six écrans */ }

    public static function niveauPourMinutes(int $minutes): string
    {
        if ($minutes < self::SEUIL_ORANGE) { return 'vert'; }
        if ($minutes <= self::SEUIL_ROUGE) { return 'orange'; }
        return 'rouge';
    }
}
```

- `niveauPourMinutes()` est **statique** : elle classe n'importe quel nombre de
  minutes (vert, orange, rouge), même sans objet `JournalEntree` sous la main ;
  `getNiveauEcran()` l'appelle avec le total du journal.
- `DouleurZone` : relation `ManyToOne` vers son journal (`onDelete: 'CASCADE'`),
  une `zone` (clé de `ZONES`) et une `intensite` de 1 à 5, passées au
  **constructeur** `new DouleurZone('cou', 3)` : une douleur n'existe jamais sans
  ces deux valeurs.
- `ContenuBienEtre` : `type` (clé de `TYPES`, `fiche` par défaut), `titre`
  (160 caractères, obligatoire), `contenu` (type `text`), `url` facultative,
  `declencheur` facultatif (clé de `DECLENCHEURS`), `createdAt`. L'URL est
  validée ainsi :

```php
#[ORM\Column(length: 500, nullable: true)]
#[Assert\Url(
    message: 'Merci de saisir une URL valide.',
    requireTld: true,
    tldMessage: 'Merci de saisir une URL complète, avec son domaine (par exemple https://exemple.fr).',
)]
private ?string $url = null;
```

`requireTld: true` est **obligatoire** : l'omettre est déprécié depuis
Symfony 7.1, et sans lui le `tldMessage` n'est jamais utilisé.

---

## 5. Commandes

> Aucun `composer require` dans cette phase : Doctrine, MakerBundle, Validator et
> SecurityBundle sont installés depuis la phase 01, et phpMyAdmin tourne déjà.

### `docker compose exec app php bin/console make:entity`

- **Ce qu'elle fait** : crée ou modifie une entité **en dialoguant** avec vous
  (nom de propriété, type, longueur, nullable). Le type `relation` lance un
  assistant qui demande `ManyToOne`, `OneToMany` ou `OneToOne`, et s'il faut
  créer le côté inverse.
- **À observer** : elle crée aussi le repository, et écrit **les deux côtés**
  d'une relation avec des méthodes `addEnfant()` / `removeEnfant()`. Le code
  généré est à relire et à compléter (constantes, getters d'affichage,
  contraintes, cascades, normalisation, commentaires).
- **Ordre conseillé** : `User`, puis `Enfant`, `JournalEntree`, `DouleurZone`
  (une relation ne peut viser qu'une entité qui existe déjà), et enfin
  `ContenuBienEtre`, qui ne dépend d'aucune autre.

### `docker compose exec app php bin/console make:migration`

- **Ce qu'elle fait** : compare vos entités à la base et génère le SQL de l'écart.
- **À observer** : **ouvrez le fichier généré**. Vous devez y lire les cinq
  `CREATE TABLE`, les `FOREIGN KEY … ON DELETE CASCADE` sur `parent_id`,
  `enfant_id` et `journal_entree_id`, et les index uniques. Une table inattendue
  signale un problème de mapping.

### `docker compose exec app php bin/console doctrine:migrations:migrate`

- **Ce qu'elle fait** : exécute les migrations non encore appliquées.
- **Quand** : après chaque `make:migration`, et à chaque installation du projet.
- **Raccourci** : ajoutez au `Makefile` la cible `migrate`
  (`php bin/console doctrine:migrations:migrate --no-interaction`) ; les
  scénarios de test l'utilisent à partir d'ici.

### `docker compose exec app php bin/console doctrine:schema:validate`

- **Ce qu'elle fait** : vérifie deux choses — le mapping est-il cohérent (par
  exemple `inversedBy` et `mappedBy` qui se répondent), et la base
  correspond-elle aux entités ?
- **À observer** : **deux** `[OK]`. Un message « The database schema is not in
  sync » signifie qu'il manque une migration ; une erreur de mapping cite la
  relation fautive.

### `docker compose exec app php bin/console doctrine:mapping:info`

- **Ce qu'elle fait** : liste les entités connues de Doctrine.
- **À observer** : **cinq** entités, marquées `[OK]`.

---

## 6. Concepts Symfony de la phase

| Composant | Rôle ici |
|---|---|
| **Doctrine ORM** | mapping objet ↔ table, relations, cascades, collections |
| **Doctrine DBAL** | la couche basse qui parle à MySQL |
| **DoctrineMigrationsBundle** | génère et applique les migrations |
| **MakerBundle** | génère entités, relations et repositories |
| **Validator** | les contraintes `#[Assert\…]` et `#[UniqueEntity]` (appliquées en phase 04) |
| **SecurityBundle** | les interfaces implémentées par `User` (configuré en phase 04) |

---

## 7. Architecture et organisation du code

```text
src/
├── Entity/
│   ├── User.php                  comptes (3 rôles), enfants, profilEnfant
│   ├── Enfant.php                profil, AVATARS, LIMITE_*, contraintes d'âge et de limite
│   ├── JournalEntree.php         6 écrans, ECRANS, seuils, index unique (enfant, date)
│   ├── DouleurZone.php           ZONES, intensité 1-5
│   └── ContenuBienEtre.php       TYPES, DECLENCHEURS, URL validée
└── Repository/
    └── *Repository.php           un par entité, VIDES pour l'instant

migrations/
└── VersionYYYYMMDDHHMMSS.php     une ou plusieurs migrations, relues

config/packages/
└── doctrine.yaml                 connexion, mapping (créé par Flex en phase 01)
```

Pourquoi cette séparation :

- l'**entité** décrit une donnée, ses liens et ses règles ;
- le **repository** décrit comment la retrouver ;
- la **migration** décrit comment la base doit évoluer.

Trois responsabilités, trois fichiers.

### Le modèle de données complet

```text
users ◄──────── parent_id (N:1, ON DELETE CASCADE) ──── enfant
  ▲                                                       │
  └──────────── compte_id (1:1, unique) ──────────────────┘
                                                          ▲
journal_entree ── enfant_id (N:1, ON DELETE CASCADE) ─────┘
  ▲   index unique (enfant_id, date)
  │
douleur_zone ── journal_entree_id (N:1, ON DELETE CASCADE)

contenu_bien_etre                 (aucune relation)
```

---

## 8. Flux de fonctionnement

### De l'entité à la table

```text
src/Entity/*.php  (attributs #[ORM\…])
    ↓ make:migration : compare au schéma actuel
migrations/VersionXXXX.php  (SQL généré — à relire)
    ↓ doctrine:migrations:migrate
MySQL : tables, clés étrangères, index
    ↓ doctrine:schema:validate
[OK] mapping   [OK] base synchronisée
```

### D'une recherche aux objets (phases suivantes)

```text
Votre code
    ↓ $repository->findOneBy(['email' => 'a@b.fr'])
Repository
    ↓
Doctrine ORM  (construit le SQL, avec des paramètres liés)
    ↓
Doctrine DBAL → PDO / pdo_mysql → MySQL (conteneur database)
    ↓
lignes → objets User hydratés ; $user->getEnfants() chargera les enfants à la demande
```

Les valeurs passent toujours en **paramètres liés**, jamais concaténées : c'est
ce qui protège des injections SQL.

---

## 9. Application au projet

**Pourquoi cette phase ?** Toutes les fonctionnalités du projet lisent ou
écrivent ces cinq tables. Poser le modèle de données complet **maintenant**, en
une fois, permet de réfléchir aux relations et aux suppressions d'un seul
regard, et d'avancer ensuite sans migration à chaque phase.

**Composants utilisés** : Doctrine ORM, Migrations, MakerBundle, Validator,
SecurityBundle (pour ses interfaces).

**Fichiers créés** : les cinq entités de `src/Entity/`, leurs cinq repositories
(vides), une ou plusieurs migrations et la cible `migrate` du `Makefile`.

**Pourquoi ces choix ?**

- **Cascades Doctrine + `ON DELETE CASCADE`** : supprimer un parent efface toute
  sa famille, par Doctrine comme par la base.
- **Compte enfant obligatoire** (`compte` non nullable) : jamais de « profil sans
  compte ».
- **Constantes plutôt qu'enums** : une seule convention, lisible, pour toutes les
  listes fixes.
- **Contraintes sur l'entité** : les règles permanentes (âge, limite, URL,
  unicité) sont écrites une fois et s'appliqueront à tous les formulaires.
- **Index unique (enfant, date)** : la règle « un journal par jour » tient même
  face à un double clic.

**Pourquoi phpMyAdmin ?** Pour **voir** ce que Doctrine fabrique : colonnes,
clés étrangères, index. C'est le meilleur moyen de comprendre le mapping.

**Ce qui n'est pas encore là** : aucune page nouvelle, aucun contrôleur, aucun
formulaire, aucune donnée. Les tables existent, vides — c'est tout.

---

## 10. Erreurs fréquentes

**`Unknown database 'digisante_junior'`**
→ La base n'a pas encore été créée.
→ Solution : `php bin/console doctrine:database:create --if-not-exists`.

**`Connection refused` au premier démarrage**
→ MySQL met quelques secondes à être prêt.
→ Signe : le `healthcheck` du conteneur n'est pas encore *healthy*.
→ Solution : attendre, puis relancer la commande.

**`The database schema is not in sync with the current mapping file`**
→ Vous avez modifié une entité sans générer ou appliquer la migration.
→ Solution : `make:migration` puis `doctrine:migrations:migrate`.

**`The association App\Entity\Enfant#parent refers to the inverse side field App\Entity\User#enfant which does not exist`**
→ `inversedBy` et `mappedBy` ne se répondent pas (faute de frappe, singulier au
lieu du pluriel).
→ Solution : `inversedBy: 'enfants'` d'un côté, propriété `$enfants` avec
`mappedBy: 'parent'` de l'autre, puis `doctrine:schema:validate`.

**`Target entity "App\Entity\JournalEntree" not found`**
→ Une relation vise une entité qui n'existe pas encore.
→ Solution : créer les entités dans l'ordre (`User`, `Enfant`, `JournalEntree`,
`DouleurZone`).

**`Typed property … $enfants must not be accessed before initialization`**
→ La collection n'est pas initialisée.
→ Solution : `$this->enfants = new ArrayCollection();` dans le constructeur.

**La migration ne contient pas `ON DELETE CASCADE`**
→ `onDelete: 'CASCADE'` a été oublié sur la `JoinColumn` (`make:entity` ne
l'ajoute pas).
→ Solution : l'ajouter, supprimer la migration **non encore appliquée**, la
regénérer.

**`Table 'user' doesn't exist` alors que la migration est passée**
→ `user` est un mot réservé : la table s'appelle `users` via
`#[ORM\Table(name: 'users')]`.

**Une migration a été générée alors que vous n'avez rien changé**
→ Souvent un type de colonne légèrement différent (ex. `datetime` vs
`datetime_immutable`), ou une base pas à jour.
→ Solution : lire le SQL généré **avant** de l'appliquer, et corriger le mapping
si le changement n'est pas voulu.

**`#[Assert\…]` posé, mais rien n'est bloqué**
→ C'est normal : aucune validation n'est déclenchée tant qu'aucun formulaire ne
l'appelle (phase 04).

---

## 11. Bonnes pratiques

- **Relisez toujours la migration générée** avant de l'appliquer. C'est du SQL
  qui va s'exécuter sur des données réelles.
- **Ne modifiez jamais la base via phpMyAdmin** : votre changement serait absent
  chez les autres et écrasé à la prochaine migration. phpMyAdmin sert à
  **regarder**.
- **Types de date explicites** : `date_immutable` pour un jour,
  `datetime_immutable` pour un instant.
- **Propriétés privées, getters/setters classiques**, pas de setters dynamiques
  ni de magie.
- **Normalisez dans le setter** (minuscules, `trim`) : la donnée est propre
  **avant** d'atteindre la base.
- **Constantes plutôt qu'enums**, avec des getters d'affichage.
- **Laissez les cascades faire le travail** : pas de boucle de suppression.
- **Les règles permanentes sur l'entité**, les règles propres à un formulaire
  dans le `*Type` (phase 04).
- **Repositories vides tant que personne n'en a besoin** : une requête s'écrit
  dans la phase qui l'utilise.

---

## 12. Exercice pratique

1. Ouvrez phpMyAdmin (`http://localhost:8082`), table `enfant`, onglet
   « Structure » puis « Vue relationnelle » : repérez les colonnes `parent_id`
   (en `ON DELETE CASCADE`) et `compte_id` (sans cascade en base : le compte est
   supprimé par la cascade Doctrine `Enfant::compte`). Expliquez pourquoi
   `compte_id` porte un index **unique** alors que `parent_id` n'en a pas.
2. Ajoutez temporairement une propriété à `ContenuBienEtre` :

```php
#[ORM\Column(length: 20, nullable: true)]
private ?string $auteur = null;
```

3. Lancez `make:migration` et **ouvrez le fichier généré** : vous devez y lire un
   `ALTER TABLE contenu_bien_etre ADD auteur …`.
4. Appliquez la migration, vérifiez la colonne dans phpMyAdmin.
5. Supprimez la propriété, regénérez une migration (elle contiendra un `DROP`)
   et appliquez-la.
6. Pour retirer les deux migrations d'exercice, **annulez-les d'abord** :
   `doctrine:migrations:migrate prev` deux fois (ou
   `doctrine:migrations:version --delete <version>` pour chacune). Supprimez
   ensuite les deux fichiers et vérifiez que `doctrine:schema:validate` est de
   nouveau au vert. Sans cette étape, Doctrine signale des migrations
   « exécutées mais introuvables ».
7. Sur papier : si l'on supprime un parent avec `remove()`, dans quel ordre
   Doctrine supprime-t-il les lignes des cinq tables ? Laquelle n'est jamais
   touchée ?

Vous devez savoir expliquer la différence entre `cascade: ['remove']` et
`onDelete: 'CASCADE'`, et pourquoi on ne supprime pas une migration **déjà
partagée** avec d'autres développeurs.

---

## 13. Scénario de test manuel

1. Lancer `make migrate` (ou `doctrine:migrations:migrate`).
2. Ouvrir phpMyAdmin sur `http://localhost:8082` et sélectionner la base `digisante_junior`.
3. Vérifier la présence des cinq tables `users`, `enfant`, `journal_entree`, `douleur_zone`, `contenu_bien_etre` (plus `doctrine_migration_versions`).
4. Dans la vue relationnelle de `enfant`, `journal_entree` et `douleur_zone`, vérifier que `parent_id`, `enfant_id` et `journal_entree_id` sont en `ON DELETE CASCADE` (pas `compte_id`, supprimé par la cascade Doctrine) ; dans les index, vérifier les index uniques sur `users.email`, `users.username`, `enfant.compte_id` et `journal_entree (enfant_id, date)`.
5. Lancer `doctrine:schema:validate` puis `doctrine:mapping:info`.
6. **Résultat attendu** : les cinq tables existent avec leurs clés et index, `doctrine:schema:validate` affiche deux `[OK]` et `doctrine:mapping:info` liste cinq entités.

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

⬅️ [Phase précédente](./phase-01.md)

➡️ [Phase suivante](./phase-03.md)

➡️ [Phase de développement](../README.md#phase-02--base-de-données-et-entités)

➡️ [Prompt Claude Code](../prompts/phase-02.md)
