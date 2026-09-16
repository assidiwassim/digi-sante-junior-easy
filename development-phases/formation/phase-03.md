# Formation — Phase 03 : Base de données, Doctrine et entité `User`

## 1. Objectifs pédagogiques

À la fin de cette leçon, vous devez être capable de :

- expliquer ce qu'est un **ORM** et ce qu'il vous évite d'écrire ;
- déclarer une **entité** Doctrine et comprendre chaque attribut de mapping ;
- expliquer le rôle d'un **repository** et pourquoi les requêtes y vivent ;
- comprendre ce qu'est une **migration** et pourquoi on ne modifie jamais la base
  à la main ;
- lire une `DATABASE_URL` et savoir où la configurer ;
- choisir entre `null` autorisé ou non, et comprendre l'effet sur les
  formulaires à venir.

## 2. Prérequis

- Phases 01 et 02 terminées.
- Savoir ce qu'est une table, une colonne, une clé primaire, un index.
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

**Dans ce projet.** Doctrine ORM 3 gère cinq entités au total. Ici on ne crée que
la première : `User`.

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
les **enfants** se connecteront avec un identifiant, sans email (phase 06). La
colonne autorise donc `NULL`, et c'est le **formulaire d'inscription** du parent
qui exigera l'email (phase 04).

Règle du projet à retenir : les setters liés à un formulaire acceptent `null`
(`?string`). Sinon, un champ laissé vide provoque une erreur 500 **avant** que
la validation ait pu afficher un message propre.

---

### Concept 3 — Le repository

**Pourquoi ?** Il faut un endroit unique où vivent les requêtes d'une entité :
sinon les mêmes requêtes se dupliquent dans plusieurs contrôleurs, avec des
variantes subtiles.

**Comment ça fonctionne ?** Chaque entité a un repository. Il hérite de méthodes
toutes faites, et vous y ajoutez les vôtres.

```php
$userRepository->find(12);                               // par identifiant
$userRepository->findOneBy(['email' => 'a@b.fr']);        // un seul résultat
$userRepository->findBy(['ville' => 'Lyon'], ['email' => 'ASC']); // une liste
$userRepository->findAll();                               // tout
```

**Dans ce projet.** Règle stricte : **aucune requête dans un contrôleur**. Dès
qu'une recherche dépasse `find()` / `findBy()`, on écrit une méthode dans le
repository, avec un nom explicite en français (`findParents()`,
`findAujourdhui()`…).

---

### Concept 4 — Les migrations

**Pourquoi ?** La base de votre collègue, celle des tests et celle de production
doivent recevoir **les mêmes changements, dans le même ordre**. Les appliquer à
la main est impossible à tenir.

**Comment ça fonctionne ?**

```text
1. vous modifiez une entité
2. make:migration compare vos entités à la base et génère un fichier SQL horodaté
3. vous RELISEZ ce fichier
4. doctrine:migrations:migrate l'exécute et note qu'il est appliqué
```

Doctrine garde la trace des migrations déjà passées dans une table dédiée : la
même migration ne s'exécute jamais deux fois.

**Exemple.**

```php
public function up(Schema $schema): void
{
    $this->addSql('CREATE TABLE users (id INT AUTO_INCREMENT NOT NULL, …)');
}
```

**Dans ce projet.** Trois règles :

1. toute modification de schéma passe par une migration ;
2. on ne modifie **jamais** la base à la main (ni via phpMyAdmin) ;
3. on ne modifie **jamais** une migration déjà partagée — on en crée une
   nouvelle.

---

### Concept 5 — `DATABASE_URL` et la connexion

**Pourquoi ?** L'application doit savoir où est la base, avec quel utilisateur.

**Comment ça fonctionne ?** Une seule variable d'environnement contient tout :

```dotenv
DATABASE_URL="mysql://digisante:digisante@database:3306/digisante_junior?serverVersion=8.0.36&charset=utf8mb4"
#              ^^^^^  ^^^^^^^^^ ^^^^^^^^^ ^^^^^^^^ ^^^^ ^^^^^^^^^^^^^^^^
#              type   user      password  hôte     port  base
```

⚠️ L'hôte est `database` — le **nom du service Docker** — et le port `3306`,
car l'application parle à MySQL **depuis l'intérieur** du réseau Docker. Depuis
votre machine (DBeaver par exemple), c'est `127.0.0.1:3308`.

---

### Concept 6 — Les contraintes de validation, première rencontre

**Pourquoi ?** La base garantit la cohérence technique (unicité, type), mais pas
les règles métier (« cet email doit ressembler à un email »).

**Comment ça fonctionne ?** Des attributs `#[Assert\…]` sur les propriétés, et
`#[UniqueEntity]` sur la classe.

```php
#[UniqueEntity(fields: ['email'], message: 'Cette adresse email est déjà utilisée.')]
class User
{
    #[Assert\Email(message: 'Cette adresse email n\'est pas valide.')]
    private ?string $email = null;
}
```

La validation sera **déclenchée** par les formulaires en phase 04. On la déclare
maintenant parce que ces règles appartiennent à l'entité, pas au formulaire.

Message toujours **en français et écrit pour l'utilisateur** : « Cette adresse
email est déjà utilisée. », pas « UNIQUE constraint violation ».

---

## 4. Explications avec exemples

### L'entité `User`, et pourquoi elle est particulière

```php
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    public const ROLE_ADMIN = 'ROLE_ADMIN';
    public const ROLE_PARENT = 'ROLE_PARENT';
    public const ROLE_CHILD = 'ROLE_CHILD';

    #[ORM\Column]
    private array $roles = [];

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
}
```

- `implements UserInterface` : le **contrat** que Symfony Security attend d'une
  classe d'utilisateur (phase 04). On l'écrit dès maintenant pour ne pas avoir à
  créer une migration supplémentaire plus tard.
- `roles` est de type `json` : MySQL stocke `["ROLE_PARENT"]`. Pratique, mais
  cela complique les recherches — on le verra en phase 11.
- Les rôles sont des **constantes de classe**. Règle du projet : **pas d'enum
  PHP**, des constantes avec, si besoin, des getters d'affichage.

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
seraient deux comptes différents, et l'index unique ne servirait à rien.

---

## 5. Commandes

### `docker compose exec app composer require symfony/orm-pack`

- **Ce qu'elle fait** : installe Doctrine (ORM, DBAL, migrations) et crée
  `config/packages/doctrine.yaml`.
- **À observer** : Flex configure `DATABASE_URL` dans `.env`. Vérifiez qu'elle
  pointe bien sur le service `database`.

### `docker compose exec app composer require --dev symfony/maker-bundle`

- **Ce qu'elle fait** : installe le générateur de code (`make:entity`,
  `make:form`, `make:voter`…).
- **Pourquoi `--dev`** : c'est un outil de développement, inutile en production.

### `docker compose exec app php bin/console make:entity`

- **Ce qu'elle fait** : crée ou modifie une entité **en dialoguant** avec vous
  (nom de propriété, type, longueur, nullable).
- **À observer** : elle crée aussi le repository. Le code généré est à relire et
  à compléter (commentaires, constantes, normalisation).

### `docker compose exec app php bin/console make:migration`

- **Ce qu'elle fait** : compare vos entités à la base et génère le SQL de l'écart.
- **À observer** : **ouvrez le fichier généré**. Si vous y voyez une table que
  vous n'attendiez pas, il y a un problème de mapping.

### `docker compose exec app php bin/console doctrine:migrations:migrate`

- **Ce qu'elle fait** : exécute les migrations non encore appliquées.
- **Quand** : après chaque `make:migration`, et à chaque installation du projet.

### `docker compose exec app php bin/console doctrine:schema:validate`

- **Ce qu'elle fait** : vérifie deux choses — le mapping est-il cohérent, et la
  base correspond-elle aux entités ?
- **À observer** : **deux** `[OK]`. Un message « The database schema is not in
  sync » signifie qu'il manque une migration.

---

## 6. Concepts Symfony de la phase

| Composant | Rôle ici |
|---|---|
| **Doctrine ORM** | mapping objet ↔ table, `persist()`, `flush()` |
| **Doctrine DBAL** | la couche basse qui parle à MySQL |
| **DoctrineMigrationsBundle** | génère et applique les migrations |
| **MakerBundle** | génère entités, repositories et formulaires |
| **Validator** | les contraintes `#[Assert\…]` (utilisées en phase 04) |

---

## 7. Architecture et organisation du code

```text
src/
├── Entity/
│   └── User.php              une classe = une table
└── Repository/
    └── UserRepository.php    toutes les requêtes sur les utilisateurs

migrations/
└── VersionYYYYMMDDHHMMSS.php historique des changements de schéma

config/packages/
└── doctrine.yaml             connexion, mapping, réglages
```

Pourquoi cette séparation :

- l'**entité** décrit une donnée et ses règles ;
- le **repository** décrit comment la retrouver ;
- la **migration** décrit comment la base doit évoluer.

Trois responsabilités, trois fichiers.

---

## 8. Flux de fonctionnement

```text
Votre code
    ↓ $repository->findOneBy(['email' => 'a@b.fr'])
Repository
    ↓
Doctrine ORM  (construit le SQL, avec des paramètres liés)
    ↓
Doctrine DBAL
    ↓
PDO / pdo_mysql
    ↓
MySQL (conteneur database)
    ↓
lignes → objets User hydratés
```

Les valeurs passent toujours en **paramètres liés**, jamais concaténées : c'est
ce qui protège des injections SQL.

---

## 9. Application au projet

**Pourquoi cette phase ?** Tout le reste de l'application tourne autour des
comptes : parents, enfants, administrateur. `User` est donc la première table.

**Un seul `User` pour trois rôles**, c'est un choix structurant :

- un parent et un administrateur se connectent par **email** ;
- un enfant se connecte par **identifiant** ;
- d'où les deux colonnes nullables et uniques, et `getUserIdentifier()` qui
  renvoie l'une ou l'autre.

**Composants utilisés** : Doctrine ORM, Migrations, MakerBundle.

**Fichiers créés** : `src/Entity/User.php`, `src/Repository/UserRepository.php`,
une migration, et le service `phpmyadmin` dans `compose.yaml`.

**Pourquoi phpMyAdmin ?** Pour **voir** ce que Doctrine fabrique. Regarder la
table après une migration est le meilleur moyen de comprendre le mapping. C'est
un outil de développement : il n'ira pas en production (phase 13).

**Ce qui n'est pas encore là** : aucun utilisateur en base, aucune connexion,
aucun formulaire. La table existe, c'est tout.

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

**`Table 'user' doesn't exist` alors que la migration est passée**
→ `user` est un mot réservé : la table s'appelle `users` via
`#[ORM\Table(name: 'users')]`.
→ Solution : utiliser le nom réel, ou passer par l'entité plutôt que par du SQL.

**Rien n'est enregistré, sans message d'erreur**
→ `flush()` a été oublié après `persist()`.
→ Signe : aucune ligne en base, aucune exception.

**Une migration a été générée alors que vous n'avez rien changé**
→ Souvent un type de colonne légèrement différent (ex. `datetime` vs
`datetime_immutable`), ou une base pas à jour.
→ Solution : lire le SQL généré **avant** de l'appliquer, et corriger le mapping
si le changement n'est pas voulu.

---

## 11. Bonnes pratiques

- **Relisez toujours la migration générée** avant de l'appliquer. C'est du SQL
  qui va s'exécuter sur des données réelles.
- **Ne modifiez jamais la base via phpMyAdmin** : votre changement serait absent
  chez les autres et écrasé à la prochaine migration.
- **Types de date explicites** : `date_immutable` pour un jour,
  `datetime_immutable` pour un instant. Les objets immuables évitent les
  modifications accidentelles.
- **Propriétés privées, getters/setters classiques**, pas de setters dynamiques
  ni de magie.
- **Normalisez dans le setter** (minuscules, `trim`) : la donnée est propre
  **avant** d'atteindre la base.
- **Constantes plutôt qu'enums** : c'est la convention de ce projet, appliquée
  partout (rôles, avatars, zones du corps, types de contenu).

---

## 12. Exercice pratique

1. Ouvrez phpMyAdmin (`http://localhost:8082`) et regardez la structure de la
   table `users` : type de chaque colonne, index uniques.
2. Ajoutez temporairement une propriété à l'entité :

```php
#[ORM\Column(length: 20, nullable: true)]
private ?string $telephone = null;
```

3. Lancez `make:migration` et **ouvrez le fichier généré** : vous devez y lire un
   `ALTER TABLE users ADD telephone …`.
4. Appliquez la migration, vérifiez la colonne dans phpMyAdmin.
5. Supprimez la propriété, regénérez une migration (elle contiendra un `DROP`),
   appliquez-la, puis **supprimez les deux fichiers de migration d'exercice** et
   vérifiez que `doctrine:schema:validate` est de nouveau au vert.

Vous devez savoir expliquer pourquoi on ne supprime pas une migration **déjà
partagée** avec d'autres développeurs.

---

## 13. Scénario de test manuel

1. Lancer `make migrate` (ou `doctrine:migrations:migrate`).
2. Ouvrir phpMyAdmin sur `http://localhost:8082`.
3. Sélectionner la base `digisante_junior` et ouvrir la table `users`.
4. Vérifier la présence des colonnes `email`, `username`, `roles`, `password`, `created_at`.
5. **Résultat attendu** : la table existe avec les bonnes colonnes, et `doctrine:schema:validate` affiche deux `[OK]`.

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

➡️ [Phase suivante](./phase-04.md)

➡️ [Phase de développement](../README.md#phase-03--base-de-données-doctrine-et-entité-user)

➡️ [Prompt Claude Code](../prompts/phase-03.md)
