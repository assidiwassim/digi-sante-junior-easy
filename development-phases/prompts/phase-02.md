# Prompt Claude Code — Phase 02 : Base de données et entités

> Copiez tout ce qui suit dans Claude Code, à la racine du projet.

---

## Contexte du projet

**Digi-Santé Junior** : application Symfony de suivi du bien-être numérique des
enfants de 8 à 14 ans. L'enfant déclare chaque jour son temps d'écran et ses
douleurs, reçoit des conseils ; ses parents suivent l'évolution. Trois rôles
**sans hiérarchie** (un administrateur n'est ni parent ni enfant) :

| Rôle | Espace | Connexion |
|---|---|---|
| `ROLE_ADMIN` | `/admin` | **email** |
| `ROLE_PARENT` | `/parent` | **email** |
| `ROLE_CHILD` | `/enfant` | **identifiant** (pas d'email) |

Déjà en place (phase 01) : Docker (`app`, `database` MySQL 8, `phpmyadmin` sur
`http://localhost:8082`), squelette Symfony 7.4, et **tous les paquets du
projet** (Doctrine ORM, Maker, Security, Validator…). Aucune entité n'existe
encore.

Stack : PHP 8.4, Symfony 7.4, **Doctrine ORM 3**, MySQL 8, Twig, Docker.
Je débute avec Symfony : code simple, en français, sans sur-ingénierie.

## Objectif de la phase

Connecter l'application à MySQL et créer **les cinq entités** du projet, avec
leurs relations, leurs cascades de suppression, leurs listes fixes et leurs
règles de validation : `User`, `Enfant`, `JournalEntree`, `DouleurZone`,
`ContenuBienEtre`. Aucune page dans cette phase : on construit le modèle de
données, que les phases suivantes utiliseront sans le recréer.

## Avant de coder

1. Lis `compose.yaml`, `.env`, `composer.json` et `config/packages/doctrine.yaml`
   pour voir la configuration existante.
2. Vérifie que le service `database` tourne et que `DATABASE_URL` pointe bien
   sur lui (hôte `database`, port 3306 **dans** le réseau Docker).
3. Explique-moi la différence entre `.env` et `.env.local` avant de toucher à
   ces fichiers, et rappelle-moi qu'une vraie variable d'environnement (ici la
   `DATABASE_URL` fournie par `compose.yaml`) l'emporte sur les deux : `.env`
   ne sert que de valeur de repli.
4. Tous les paquets sont installés depuis la phase 01 : n'en installe aucun ;
   s'il en manque un, signale-le moi.
5. Annonce-moi l'ordre dans lequel tu vas créer les entités avant de commencer.

## À implémenter

### 1. Doctrine

- Vérifier la configuration générée dans `config/packages/doctrine.yaml` et la
  commenter en français là où c'est utile.
- Ajoute au `Makefile` la cible `migrate`
  (`php bin/console doctrine:migrations:migrate --no-interaction`) : les
  scénarios de test l'utilisent à partir d'ici.

### 2. Règles communes aux entités

- **Suppressions** : le projet s'appuie sur **les deux** mécanismes —
  `cascade: ['remove']` côté Doctrine (sur la relation inverse), et
  `onDelete: 'CASCADE'` sur la clé étrangère, filet de sécurité si une ligne est
  supprimée hors Doctrine. Chaîne attendue : parent → enfants → compte +
  journaux → douleurs.
- **Listes fixes en constantes de classe**, pas d'enum PHP, avec des getters
  d'affichage (`getAvatarEmoji()`, `getZoneLabel()`…).
- **Contraintes `#[Assert\…]` sur l'entité** pour les règles permanentes, avec
  des messages en français écrits pour l'utilisateur. Elles seront mises en
  œuvre par les formulaires à partir de la phase 04.
- ⚠️ Les setters liés à un formulaire acceptent `null` (`?string`, `?int`…) :
  sinon un champ vide provoque une erreur 500 **avant** la validation.
- Collections initialisées dans le constructeur (`new ArrayCollection()`).

### 3. Entité `User` (table `users`)

Un seul type de compte pour les trois rôles :

| Propriété | Type | Règles |
|---|---|---|
| `id` | `int` | clé primaire auto |
| `email` | `?string(180)` | **unique**, nullable (les enfants n'en ont pas), `#[Assert\Email(message: "Cette adresse email n'est pas valide.")]` |
| `username` | `?string(60)` | **unique**, nullable (réservé aux enfants) |
| `roles` | `json` | liste de rôles |
| `password` | `string` | mot de passe **haché**, jamais en clair |
| `pays` | `?string(80)` | facultatif |
| `ville` | `?string(80)` | facultatif |
| `createdAt` | `datetime_immutable` | rempli dans le constructeur |
| `enfants` | `OneToMany` vers `Enfant` | côté parent, `mappedBy: 'parent'`, `cascade: ['remove']`, **sans tri** (les listes triées passent par le repository) |
| `profilEnfant` | `OneToOne` inverse vers `Enfant` | côté enfant, `mappedBy: 'compte'` |

Exigences :

- la table s'appelle `users` (`user` est un mot réservé en SQL) ;
- la classe implémente `UserInterface` et `PasswordAuthenticatedUserInterface` ;
- `getUserIdentifier()` renvoie l'email **sinon** le username ;
- `getRoles()` ajoute toujours `ROLE_USER` ;
- les rôles sont des **constantes de classe** (`ROLE_ADMIN`, `ROLE_PARENT`,
  `ROLE_CHILD`) : pas d'enum PHP ;
- `setEmail()` et `setUsername()` enregistrent en **minuscules**, sans espaces
  autour ;
- `#[UniqueEntity]` sur `email` (« Cette adresse email est déjà utilisée. ») et
  sur `username` (« Cet identifiant est déjà pris. ») ;
- une méthode `isParent()` pratique pour la suite ;
- `eraseCredentials()` reste vide et porte l'attribut `#[\Deprecated]` : depuis
  Symfony 7.3 cette méthode est dépréciée, et sans cet attribut Symfony
  signale une dépréciation (visible dans la barre de debug à partir de la
  phase 03).

### 4. Entité `Enfant`

| Propriété | Type | Règles |
|---|---|---|
| `parent` | `ManyToOne` vers `User` | non nullable, `onDelete: 'CASCADE'` |
| `compte` | `OneToOne` vers `User` | **non nullable**, `cascade: ['persist', 'remove']` (le compte part avec le profil) |
| `prenom`, `nom` | `string(80)` | obligatoires : « Le prénom est obligatoire. », « Le nom est obligatoire. » |
| `dateNaissance` | `date_immutable` | obligatoire (« La date de naissance est obligatoire. »), l'enfant doit avoir **entre 8 et 14 ans** |
| `avatar` | `string(20)` | clé de `AVATARS`, défaut `renard`, obligatoire (« Choisissez un avatar. ») |
| `maxMinutesJour` | `int` | limite quotidienne, **15 à 480 min par pas de 15**, défaut **120** |
| `journalEntrees` | `OneToMany` vers `JournalEntree` | `mappedBy: 'enfant'`, `cascade: ['remove']` |

- Constante `AVATARS` — **exactement** ces 12 avatars (clé → emoji, nom,
  couleur), dans cet ordre. Les clés sont enregistrées en base : elles doivent
  être identiques d'un projet à l'autre.

  | Clé | Emoji | Nom | Couleur |
  |---|---|---|---|
  | `renard` | 🦊 | Renard malin | `#F59E0B` |
  | `panda` | 🐼 | Panda calme | `#64748B` |
  | `chat` | 🐱 | Chat curieux | `#F472B6` |
  | `chien` | 🐶 | Chien fidèle | `#C99A2E` |
  | `lapin` | 🐰 | Lapin rapide | `#A78BFA` |
  | `lion` | 🦁 | Lion courageux | `#EA580C` |
  | `grenouille` | 🐸 | Grenouille sportive | `#16A34A` |
  | `poulpe` | 🐙 | Poulpe créatif | `#0E7C7B` |
  | `licorne` | 🦄 | Licorne magique | `#DB2777` |
  | `dragon` | 🐲 | Dragon rigolo | `#059669` |
  | `pingouin` | 🐧 | Pingouin cool | `#1F3864` |
  | `astronaute` | 🧑‍🚀 | Astronaute | `#3B82F6` |

- Constantes `LIMITE_MIN = 15`, `LIMITE_MAX = 480`, `LIMITE_PAS = 15`.
- Getters d'affichage : `getNomComplet()` (« prénom nom »), `getAge()` (années
  révolues), `getAvatarEmoji()` (🙂 si la clé est inconnue), `getAvatarNom()`
  (« Avatar » si la clé est inconnue).
- Validation :
  - date de naissance : `Assert\Range` entre `'today -15 years +1 day'` et
    `'today -8 years'`, message « L'application est réservée aux enfants de 8 à
    14 ans. » ;
  - limite : `Assert\NotNull` (« Choisissez une limite. »), `Assert\Range`
    (15 à 480, « La limite doit être comprise entre {{ min }} et {{ max }}
    minutes. ») et `Assert\DivisibleBy(15)` (« La limite se règle par tranches
    de 15 minutes. »).

### 5. Entité `JournalEntree`

| Propriété | Type | Règles |
|---|---|---|
| `enfant` | `ManyToOne` vers `Enfant` | non nullable, `onDelete: 'CASCADE'` |
| `date` | `date_immutable` | jour seul (minuit), initialisé à « today » dans le constructeur |
| `ecranTv`, `ecranOrdinateur`, `ecranSmartphone`, `ecranTablette`, `ecranConsole`, `ecranAutre` | `int` | minutes, défaut 0 |
| `douleurs` | `OneToMany` vers `DouleurZone` | `mappedBy: 'journalEntree'`, `cascade: ['persist', 'remove']` |

- **Index unique `un_journal_par_jour` sur `(enfant_id, date)`**
  (`#[ORM\UniqueConstraint]`) : un seul journal par enfant et par jour, garanti
  **en base**.
- Constante `ECRANS` (nom de propriété → libellé affiché), **exactement** :
  `ecranTv` → « 📺 Télévision », `ecranOrdinateur` → « 💻 Ordinateur »,
  `ecranSmartphone` → « 📱 Téléphone », `ecranTablette` → « 📲 Tablette »,
  `ecranConsole` → « 🎮 Console de jeux », `ecranAutre` → « 🖥️ Autre écran ».
  Elle servira au formulaire du journal.
- `addDouleur()`, qui rattache aussi la douleur au journal.
- `getTotalEcran()` : somme des six durées.
- `niveauPourMinutes(int $minutes): string` (statique) : `vert` sous 120 min,
  `orange` jusqu'à 240 min inclus, `rouge` au-delà — plus `getNiveauEcran()`.

### 6. Entité `DouleurZone`

- `journalEntree` (`ManyToOne`, non nullable, `onDelete: 'CASCADE'`), `zone`
  (`string(20)`), `intensite` (`int`, 1 à 5).
- Constructeur `__construct(string $zone, int $intensite)`.
- Constante `ZONES` (clé → libellé et emoji), **exactement** : `yeux` → Yeux 👀,
  `cou` → Cou / nuque 🦴, `epaule` → Épaules 💪, `dos` → Dos 🔙,
  `poignet` → Poignets 🤚, `main` → Doigts / main ✋. Les clés correspondront à
  l'attribut `data-zone` du schéma du corps (phase 07).
- Getters d'affichage `getZoneLabel()` (la clé si elle est inconnue) et
  `getZoneEmoji()` (📍 si elle est inconnue).

### 7. Entité `ContenuBienEtre`

| Propriété | Type | Règles |
|---|---|---|
| `type` | `string(20)` | obligatoire (« Choisissez un type de contenu. »), défaut `fiche` |
| `titre` | `string(160)` | obligatoire (« Le titre est obligatoire. ») |
| `contenu` | `text` | obligatoire (« Le contenu est obligatoire. ») |
| `url` | `?string(500)` | facultatif, URL valide |
| `declencheur` | `?string(40)` | facultatif |
| `createdAt` | `datetime_immutable` | rempli dans le constructeur |

- Constantes :
  - `TYPES` (clé → libellé et emoji), dans cet ordre, qui sera celui des groupes
    de la page « Découvrir » : `fiche` → Fiche 📄, `video` → Vidéo 🎬,
    `quiz` → Quiz ❓, `glossaire` → Glossaire 📚, `exercice` → Exercice 🤸 ;
  - `DECLENCHEURS` : **uniquement** les trois règles qu'appliquera le moteur de
    conseils (phase 09) — `20-20-20` (« Règle du 20-20-20 »),
    `etirement_cervical` (« Étirements du cou »), `yoga_yeux` (« Yoga des
    yeux ») —, déclarées aussi en constantes nommées `DECLENCHEUR_20_20_20`,
    `DECLENCHEUR_ETIREMENT` et `DECLENCHEUR_YOGA_YEUX`. ⚠️ N'ajoute **aucune**
    autre règle : une règle proposée mais jamais déclenchée rendrait invisibles
    ses contenus.
- Getters d'affichage : `getTypeLabel()`, `getTypeEmoji()` (📄 par défaut),
  `getDeclencheurLabel()` (`null` sans règle).
- Validation sur `url` :
  `#[Assert\Url(message: 'Merci de saisir une URL valide.', requireTld: true, tldMessage: 'Merci de saisir une URL valide, par exemple https://exemple.fr.')]`.
  `requireTld: true` est obligatoire : l'omettre est déprécié depuis
  Symfony 7.1, et sans lui `tldMessage` n'est jamais utilisé.

### 8. Repositories

`make:entity` crée un repository par entité : **laisse-les vides**. Chaque
méthode de requête (`findByParent()`, `genererUsername()`, `findAujourdhui()`…)
sera écrite dans la phase qui l'utilise.

### 9. Migrations

- Générer la ou les migrations, **me les faire relire** avant de les appliquer,
  puis les appliquer (`make migrate`).
- Vérifier ensuite avec `doctrine:schema:validate`.

## Contraintes techniques et architecturales

- **Toute** modification du schéma passe par une migration : jamais de SQL à la
  main dans la base.
- Les requêtes Doctrine vivront dans les **repositories**, jamais dans un
  contrôleur.
- Dates : `datetime_immutable` pour un instant, `date_immutable` pour un jour.
- Pas d'enum PHP : des constantes de classe.
- Propriétés `private`, getters/setters classiques, pas de magie.
- Pas de classe « manager » de suppression : les cascades suffisent.

## Commandes attendues

```bash
docker compose exec app php bin/console make:entity User
docker compose exec app php bin/console make:entity Enfant
docker compose exec app php bin/console make:entity JournalEntree
docker compose exec app php bin/console make:entity DouleurZone
docker compose exec app php bin/console make:entity ContenuBienEtre
docker compose exec app php bin/console make:migration
make migrate
docker compose exec app php bin/console doctrine:schema:validate
docker compose exec app php bin/console doctrine:mapping:info
```

## Ce qui n'est PAS dans cette phase

- Aucun contrôleur, aucune page, aucun gabarit (la charte arrive en phase 03).
- Pas de formulaire ni de configuration de `security.yaml` (le fichier par
  défaut de la recette reste tel quel, phase 04).
- Aucune méthode dans les repositories (chaque phase ajoute les siennes).
- Aucune donnée en base : ni utilisateur, ni contenu (phases 04 et 12).
- Aucune installation de paquet (tout est installé depuis la phase 01).

## Scénario de test manuel

1. Appliquer les migrations : `make migrate`.
2. Ouvrir phpMyAdmin sur `http://localhost:8082`, puis la base `digisante_junior`.
3. Vérifier la présence des 5 tables : `users`, `enfant`, `journal_entree`, `douleur_zone`, `contenu_bien_etre`.
4. Dans l'onglet « Structure » de chaque table, vérifier les colonnes, les index uniques (`email`, `username`, `compte_id`, `(enfant_id, date)`) et, via « Vue relationnelle », les clés étrangères : `parent_id`, `enfant_id` et `journal_entree_id` en `ON DELETE CASCADE` ; `compte_id` sans (le compte est supprimé par la cascade Doctrine `Enfant::compte`).
5. **Résultat attendu** : les tables existent avec les bonnes colonnes et contraintes, `doctrine:schema:validate` affiche `[OK]` pour le mapping **et** pour la base, et `doctrine:mapping:info` liste 5 entités.

## Critères de validation

- [ ] `doctrine:schema:validate` : deux `[OK]`.
- [ ] `doctrine:mapping:info` : 5 entités `[OK]`.
- [ ] Les 5 tables sont visibles dans phpMyAdmin avec leurs index uniques et
      leurs clés étrangères (`ON DELETE CASCADE` sur `parent_id`, `enfant_id`
      et `journal_entree_id`, pas sur `compte_id`).
- [ ] `User` implémente les deux interfaces de sécurité et expose ses rôles en
      constantes.
- [ ] Les listes fixes (`AVATARS`, `ECRANS`, `ZONES`, `TYPES`, `DECLENCHEURS`)
      sont des constantes, sans enum PHP, avec **exactement** les valeurs
      demandées.
- [ ] Les repositories sont vides.
- [ ] Les fichiers de migration sont lisibles et ne contiennent que ce schéma.
- [ ] `.env` ne contient aucun mot de passe réel autre que celui du Docker local.

## Enfin

- N'écris **aucun test automatisé** : je valide dans phpMyAdmin.
- Termine en m'expliquant en quelques lignes ce que fait exactement une
  migration, pourquoi on ne modifie jamais une migration déjà appliquée, et la
  différence entre `cascade: ['remove']` et `onDelete: 'CASCADE'`.
