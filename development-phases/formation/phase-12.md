# Formation — Phase 12 : Données de démonstration et qualité

## 1. Objectifs pédagogiques

À la fin de cette leçon, vous devez être capable de :

- créer des **fixtures** : un jeu de données de démonstration reproductible ;
- hacher les mots de passe des comptes de démonstration comme ceux de vrais
  utilisateurs ;
- rendre des données **déterministes** (même résultat à chaque chargement) ;
- expliquer à quoi servent les **linters** de Symfony et ce que chacun vérifie ;
- regrouper les commandes utiles dans un `Makefile` ;
- vérifier la qualité d'une application avec une **recette manuelle** au
  navigateur ;
- documenter l'installation et l'utilisation du projet dans un `README`.

> ⚠️ C'est **la dernière phase du parcours**. Elle ne crée aucune
> fonctionnalité : elle rend le projet **facile à installer, à démontrer et à
> vérifier**, par vous comme par la personne qui le reprendra.

## 2. Prérequis

- Phases 01 à 11 terminées : toutes les fonctionnalités existent.
- Savoir lancer une commande dans le conteneur.
- Connaître les parcours des trois rôles (admin, parent, enfant) : c'est eux que
  l'on va rejouer.

---

## 3. Concepts à apprendre

### Concept 1 — Les fixtures

**Pourquoi ?** Un projet qui démarre sur une base vide est impossible à
démontrer : pas de compte pour se connecter, pas de journal pour afficher un
graphique. Et chacun finirait par créer ses propres données à la main,
différentes de celles du voisin.

**Comment ça fonctionne ?** Une classe décrit les données de démonstration, une
commande les charge.

```php
class AppFixtures extends Fixture
{
    public function __construct(private UserPasswordHasherInterface $passwordHasher)
    {
    }

    public function load(ObjectManager $manager): void
    {
        $admin = new User();
        $admin->setEmail('admin@digisante.local');
        $admin->setRoles([User::ROLE_ADMIN]);
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'admin123'));
        $manager->persist($admin);

        // … parents, enfants, journaux, contenus

        $manager->flush();
    }
}
```

⚠️ `doctrine:fixtures:load` **purge la base** avant de charger : toutes les
données existantes sont perdues. À rappeler dans l'aide du `Makefile`, et à ne
jamais lancer en production.

**Dans ce projet.** Les fixtures créent :

| Donnée | Contenu |
|---|---|
| 1 administrateur | `admin@digisante.local` / `admin123` |
| 2 parents | `parent@digisante.local` et `sofia@digisante.local` / `parent123` |
| 4 enfants | `lea`, `tom`, `noah`, `ines` / `enfant123` |
| Journaux | plusieurs semaines par enfant, **dont celui du jour** |
| Contenus | une quinzaine, **dont un par règle déclencheuse** : `20-20-20`, `etirement_cervical`, `yoga_yeux` |

Sans un contenu par règle, les conseils de la phase 09 n'auraient rien à
proposer ; sans journaux sur plusieurs semaines, la courbe de 30 jours de la
phase 10 serait vide.

---

### Concept 2 — Des mots de passe hachés, même pour la démo

**Pourquoi ?** Le pare-feu compare le mot de passe saisi à un **hash**. Un mot
de passe écrit en clair dans la base ne correspondrait jamais : la connexion
échouerait.

**Comment ça fonctionne ?** Les fixtures reçoivent `UserPasswordHasherInterface`
par leur constructeur (une fixture est un service), exactement comme le
contrôleur d'inscription.

```php
$compte->setPassword($this->passwordHasher->hashPassword($compte, 'enfant123'));
```

Les mots de passe de démonstration sont connus et documentés ; ils ne sont
**jamais générés au hasard**, sinon personne ne pourrait se connecter.

---

### Concept 3 — Des données déterministes

**Pourquoi ?** Des journaux inventés avec `mt_rand()` changeraient à chaque
chargement : le graphique d'hier ne ressemblerait pas à celui d'aujourd'hui, et
une capture d'écran de la documentation deviendrait fausse.

**Comment ça fonctionne ?** Deux précautions suffisent.

```php
// 1. Une graine fixe : mt_rand() renvoie toujours la même suite de nombres
mt_srand(20240912);

// 2. Des dates relatives à aujourd'hui, jamais des dates figées
$enfant->setDateNaissance(new \DateTimeImmutable('today -10 years'));
$journal->setDate(new \DateTimeImmutable('today -' . $jour . ' days'));
```

- La graine rend les **valeurs** identiques d'un chargement à l'autre.
- Les dates relatives gardent les **âges** entre 8 et 14 ans et placent les
  journaux sur les dernières semaines, quelle que soit la date du chargement.
  Une date de naissance figée (`2015-03-01`) finirait par sortir de la plage
  autorisée par la validation.

---

### Concept 4 — Les linters

**Pourquoi ?** Certaines erreurs ne se voient qu'en ouvrant la page qui les
contient. Un linter les trouve sans navigateur.

**Comment ça fonctionne ?** Quatre commandes, réunies dans `make lint` :

| Commande | Ce qu'elle vérifie |
|---|---|
| `lint:twig templates` | syntaxe de tous les gabarits |
| `lint:yaml config` | syntaxe YAML (indentation, deux-points) |
| `lint:container` | tous les services peuvent être construits |
| `doctrine:schema:validate` | mapping cohérent **et** base à jour |

`lint:container` est le plus sous-estimé : il détecte une dépendance mal typée
sans exécuter la moindre page. `doctrine:schema:validate` signale une entité
modifiée sans migration.

⚠️ Un linter vérifie la **forme**, pas le **comportement** : un gabarit
syntaxiquement correct peut afficher la mauvaise donnée. D'où le concept suivant.

---

### Concept 5 — La recette manuelle

**Pourquoi ?** Chaque phase s'est terminée par un scénario de test manuel. Une
fois le projet fini, la moindre modification peut casser une règle écrite il y a
dix phases. Il faut donc pouvoir **tout rejouer**, rapidement et dans le même
ordre.

**Comment ça fonctionne ?** On regroupe les scénarios des phases précédentes en
une **checklist** que l'on déroule au navigateur, sur une base fraîchement
rechargée (`make reset-db`).

```text
Public   : accueil, inscription parent, connexion email, connexion enfant
Parent   : tableau de bord (jauge, courbe 30 jours), créer / modifier / supprimer un enfant
Enfant   : journal en 2 étapes, conseils, bibliothèque, profil
Admin    : CRUD des contenus, liste et fiche des parents, suppression en cascade
Sécurité : un parent ne voit pas l'enfant d'un autre (403), un enfant n'ouvre pas /parent
```

Le **résultat attendu** de chaque ligne est celui écrit dans la phase
correspondante.

---

### Concept 6 — Le README

**Pourquoi ?** Un projet que seul son auteur sait lancer n'est pas terminé.

**Comment ça fonctionne ?** Le `README.md` à la racine répond aux questions
qu'une nouvelle personne se pose, dans l'ordre :

1. **Installation** : `make install` (qui démarre aussi les conteneurs).
2. **Adresses** : application, phpMyAdmin.
3. **Comptes de démonstration** : les identifiants du concept 1.
4. **Commandes utiles** : `make fixtures`, `make reset-db`, `make lint`.
5. **Rejouer le journal** : un enfant ne remplit qu'un journal par jour, et les
   fixtures ont déjà rempli celui du jour. Pour refaire le parcours :

```bash
docker compose exec app php bin/console dbal:run-sql "DELETE FROM journal_entree WHERE date = CURDATE()"
```

6. **Problèmes fréquents** : port déjà pris, base inaccessible, journal déjà
   rempli…

---

## 4. Explications avec exemples

### Créer un enfant et son compte dans les fixtures

```php
private function creerEnfant(ObjectManager $manager, User $parent, string $prenom, string $nom, string $identifiant, int $age, string $avatar, int $limite): Enfant
{
    $compte = new User();
    $compte->setUsername($identifiant);
    $compte->setRoles([User::ROLE_CHILD]);
    $compte->setPassword($this->passwordHasher->hashPassword($compte, 'enfant123'));

    $enfant = new Enfant();
    $enfant->setPrenom($prenom);
    $enfant->setNom($nom);                 // colonne NOT NULL : obligatoire
    $enfant->setDateNaissance(new \DateTimeImmutable('today -' . $age . ' years'));
    $enfant->setAvatar($avatar);           // une clé de Enfant::AVATARS
    $enfant->setMaxMinutesJour($limite);   // multiple de 15, entre 15 et 480
    $enfant->setParent($parent);
    $enfant->setCompte($compte);

    $manager->persist($compte);
    $manager->persist($enfant);

    return $enfant;
}
```

Une petite méthode privée évite de répéter quatre fois le même bloc. Le compte
est créé **avec** le profil : c'est la règle du projet (compte enfant
obligatoire).

### Créer des journaux sur plusieurs semaines

```php
mt_srand(20240912);

for ($jour = 0; $jour < 35; $jour++) {
    $journal = new JournalEntree();
    $journal->setEnfant($enfant);
    $journal->setDate(new \DateTimeImmutable('today -' . $jour . ' days'));
    $journal->setEcranTv(mt_rand(0, 8) * 15);
    // … autres écrans
    $manager->persist($journal);
}
```

- `$jour = 0` correspond à **aujourd'hui** : le tableau de bord affiche tout de
  suite une journée remplie.
- 35 jours couvrent largement la courbe de 30 jours.
- Des multiples de 15 minutes donnent des valeurs réalistes, comme celles des
  curseurs.

### Un contenu par règle déclencheuse

```php
$contenu = new ContenuBienEtre();
$contenu->setTitre('Repose tes yeux avec le 20-20-20');
$contenu->setType('exercice');
$contenu->setContenu('Toutes les 20 minutes, regarde à 20 pieds (6 m) pendant 20 secondes.');
$contenu->setDeclencheur(ContenuBienEtre::DECLENCHEUR_20_20_20);
$manager->persist($contenu);
```

Les clés (`20-20-20`, `etirement_cervical`, `yoga_yeux`) sont les constantes de
`ContenuBienEtre::DECLENCHEURS` : les textes des conseils vivent **en base**,
pas dans le code.

---

## 5. Commandes

### Le bundle des fixtures : déjà là

- **Rien à installer** : `doctrine/doctrine-fixtures-bundle` fait partie des
  paquets installés en phase 01 (avec `--dev`), et sa recette a déjà créé un
  `src/DataFixtures/AppFixtures.php` vide. Cette phase le remplit. S'il manque
  un paquet, signalez-le plutôt que de l'ajouter en douce.
- **Pourquoi `--dev`** : les fixtures ne doivent **jamais** être installées en
  production — elles purgeraient la base.
- **À observer** : `docker compose exec app php bin/console list doctrine:fixtures`
  affiche la commande `doctrine:fixtures:load`.

### Les cibles du `Makefile`

- `migrate` existe déjà depuis la phase 02. On ajoute `fixtures`, `reset-db` et
  `lint`, et on complète `install` (migrations + fixtures).

```makefile
install: ## Démarre les conteneurs, installe les dépendances, applique les migrations, charge les fixtures
	docker compose up -d --build
	docker compose exec app composer install
	$(MAKE) migrate
	$(MAKE) fixtures

fixtures: ## ⚠️ Purge la base et recharge les données de démonstration
	docker compose exec app php bin/console doctrine:fixtures:load --no-interaction

reset-db: ## Supprime et recrée la base, migrations + fixtures
	docker compose exec app php bin/console doctrine:database:drop --force --if-exists
	docker compose exec app php bin/console doctrine:database:create
	$(MAKE) migrate
	$(MAKE) fixtures

lint: ## Vérifie gabarits, YAML, services et schéma
	docker compose exec app php bin/console lint:twig templates
	docker compose exec app php bin/console lint:yaml config
	docker compose exec app php bin/console lint:container
	docker compose exec app php bin/console doctrine:schema:validate
```

⚠️ Dans un `Makefile`, les lignes de commande commencent par une
**tabulation**, pas par des espaces.

### `make install`

- **Ce qu'elle fait** : démarre les conteneurs, installe les dépendances,
  applique les migrations et charge les fixtures.
- **Quand** : la première fois.

### `make fixtures`

- **Ce qu'elle fait** : purge la base et recharge les données de démonstration.
- **Quand** : après avoir cassé ses données pendant des essais.
- **À observer** : « purging database », puis « loading App\DataFixtures\AppFixtures ».

### `make reset-db`

- **Ce qu'elle fait** : supprime la base, la recrée, applique les migrations,
  recharge les fixtures.
- **Quand** : repartir d'un état parfaitement propre, par exemple avant une
  démonstration ou une recette manuelle.

### `make lint`

- **Ce qu'elle fait** : enchaîne les quatre vérifications.
- **À observer** : un `[OK]` pour chacune des quatre commandes
  (`schema:validate` en affiche un pour le mapping et un pour la base).

### `docker compose exec app php bin/console dbal:run-sql "DELETE FROM journal_entree WHERE date = CURDATE()"`

- **Ce qu'elle fait** : supprime les journaux du jour (et leurs douleurs, par la
  clé étrangère).
- **Quand** : pour rejouer le parcours du journal avec un enfant des fixtures.

---

## 6. Concepts Symfony de la phase

| Composant | Rôle ici |
|---|---|
| **DoctrineFixturesBundle** | données de démonstration |
| **PasswordHasher** | hachage des mots de passe des comptes de démonstration |
| **Console** | `lint:twig`, `lint:yaml`, `lint:container`, `schema:validate`, `dbal:run-sql` |
| **Makefile** (hors Symfony) | raccourcis des commandes du projet |

---

## 7. Architecture et organisation du code

```text
src/DataFixtures/
└── AppFixtures.php            toutes les données de démonstration

Makefile                       install, migrate, fixtures, reset-db, lint
README.md                      installation, comptes, commandes, problèmes fréquents
```

Une seule classe de fixtures suffit ici : elle se lit de haut en bas, dans
l'ordre des dépendances (utilisateurs, puis enfants, puis journaux, puis
contenus).

---

## 8. Flux de fonctionnement

```text
make reset-db
    ↓
doctrine:database:drop  →  doctrine:database:create
    ↓
doctrine:migrations:migrate     (toutes les tables)
    ↓
doctrine:fixtures:load
    ↓
purge  →  AppFixtures::load()  →  persist()  →  flush()
    ↓
base prête : comptes, enfants, journaux (dont aujourd'hui), contenus
    ↓
make lint  →  recette manuelle au navigateur
```

---

## 9. Application au projet

**Pourquoi cette phase ?** Jusqu'ici, chaque phase était validée à la main, sur
des données créées au fil de l'eau. Pour terminer le projet, il faut une base
**identique pour tout le monde**, des commandes **simples à retenir** et une
documentation qui permette à quelqu'un d'autre de tout relancer.

**Composants utilisés** : Fixtures, PasswordHasher, Console, Makefile.

**Fichiers créés ou modifiés** : `src/DataFixtures/AppFixtures.php`, `Makefile`,
`README.md`.

**Pourquoi ces choix ?**

- **Des fixtures riches** : des journaux sur plusieurs semaines, sinon le
  graphique de la phase 10 serait vide et invérifiable.
- **Un contenu par règle déclencheuse** : sinon les conseils de la phase 09
  s'afficheraient sans contenu associé.
- **Des données déterministes** : la démonstration et les captures de la
  documentation restent vraies d'un chargement à l'autre.
- **Des cibles `make`** : personne n'a à retenir de longues commandes Docker.
- **Linters + recette manuelle** : les linters attrapent les erreurs de forme en
  quelques secondes ; la recette vérifie le comportement, l'ergonomie et le ton
  adapté aux enfants, que rien d'autre ne peut juger.

---

## 10. Erreurs fréquentes

**`There are no commands defined in the "doctrine:fixtures" namespace`**
→ Le bundle n'est pas installé (étape oubliée en phase 01), ou l'environnement
n'est pas `dev`.
→ Solution : vérifier `APP_ENV=dev`, puis `composer show doctrine/doctrine-fixtures-bundle` ;
s'il est absent, reprendre l'installation des paquets de la phase 01.

**Impossible de se connecter avec un compte de démonstration**
→ Le mot de passe a été enregistré en clair.
→ Solution : passer par `$this->passwordHasher->hashPassword()`.

**`Cannot delete or update a parent row: a foreign key constraint fails`**
→ Les fixtures ont été chargées avec `--append`, ou une donnée est créée dans
le mauvais ordre.
→ Solution : `make reset-db` ; persister les objets dans l'ordre des
dépendances (parent avant enfant, journal avant douleurs).

**Un enfant des fixtures est refusé par la validation (âge)**
→ Sa date de naissance est figée et il a « vieilli ».
→ Solution : une date relative, `new \DateTimeImmutable('today -10 years')`.

**Le graphique change à chaque rechargement**
→ La graine aléatoire n'est pas fixée.
→ Solution : `mt_srand(20240912)` au début de `load()`.

**Le tableau de bord n'affiche rien pour aujourd'hui**
→ La boucle des journaux commence à 1 au lieu de 0, ou MySQL et PHP ne sont pas
sur le même fuseau (`TZ` dans `compose.yaml`).
→ Solution : démarrer à `$jour = 0` ; vérifier `Europe/Paris` des deux côtés.

**`make: *** missing separator. Stop.`**
→ Une ligne de commande du `Makefile` est indentée avec des espaces.
→ Solution : la remplacer par une **tabulation**.

**`doctrine:schema:validate` : « The database schema is not in sync »**
→ Une entité a été modifiée sans migration.
→ Solution : `make:migration`, relire le fichier, puis `make migrate`.

---

## 11. Bonnes pratiques

- **Les fixtures ne vont jamais en production** : elles purgent la base.
- **Des données réalistes et cohérentes** : des prénoms, des durées en multiples
  de 15 minutes, des âges dans la plage autorisée.
- **Des données déterministes** : graine fixe et dates relatives.
- **Documentez chaque compte de démonstration** dans le `README`.
- **Faites de `make lint` un réflexe** avant de considérer une tâche terminée,
  puis exercez la fonctionnalité au navigateur.
- **Rejouez la recette manuelle sur une base propre** (`make reset-db`) : une
  donnée laissée par un essai précédent peut masquer un problème.

---

## 12. Exercice pratique

1. Lancez `make reset-db`, puis connectez-vous avec les trois types de comptes
   de démonstration. Notez l'identifiant et le mot de passe de chacun.
2. Relancez `make fixtures` deux fois de suite et comparez la courbe du tableau
   de bord parent : elle doit être **identique**. Retirez `mt_srand(20240912)`,
   rechargez : que constatez-vous ? Remettez la graine.
3. Ajoutez dans `AppFixtures` un nouveau contenu de type « exercice », puis
   rechargez et vérifiez qu'il apparaît dans la bibliothèque de l'enfant.
4. Introduisez volontairement une erreur de syntaxe dans un gabarit (une balise
   `{% endif %}` en trop), lancez `make lint` et lisez le message. Corrigez.
5. Rédigez votre checklist de recette manuelle à partir des scénarios des phases
   01 à 11, puis déroulez-la entièrement. Pour le journal, utilisez la commande
   `dbal:run-sql` du README.

---

## 13. Scénario de test manuel

1. Lancer `make reset-db` pour repartir d'une base propre remplie par les fixtures.
2. Se connecter avec l'admin (`admin@digisante.local`), un parent
   (`parent@digisante.local`) et un enfant (`lea`).
3. Ouvrir le tableau de bord parent : les journaux des dernières semaines doivent
   être visibles dans la courbe de 30 jours.
4. Supprimer le journal du jour (commande du README), puis rejouer le journal en
   2 étapes en tant qu'enfant.
5. Lancer `make lint`.
6. **Résultat attendu** : les trois connexions fonctionnent, le graphique est
   rempli, le journal se rejoue sans erreur, et les quatre vérifications de
   `make lint` affichent `[OK]`.

---

## Checklist

- [ ] J'ai compris les concepts principaux
- [ ] Je comprends le rôle des fichiers créés
- [ ] Je comprends les commandes utilisées
- [ ] Je peux expliquer le fonctionnement de cette phase
- [ ] J'ai réalisé l'exercice pratique
- [ ] J'ai exécuté le scénario de test manuel
- [ ] Le résultat attendu est obtenu

## Conclusion

Bravo : c'est la fin du parcours. Digi-Santé Junior est complet — trois espaces,
un journal, des conseils, un tableau de bord, une administration — et il
s'installe, se démontre et se vérifie en quelques commandes. Pour toute
évolution future, gardez les mêmes réflexes : une migration pour le schéma,
`make lint`, puis la recette manuelle au navigateur.

### Aller plus loin

⬅️ [Phase précédente](./phase-11.md)

➡️ [Retour au sommaire de la formation](./README.md)

➡️ [Phase de développement](../README.md#phase-12--données-de-démonstration-et-qualité)

➡️ [Prompt Claude Code](../prompts/phase-12.md)
