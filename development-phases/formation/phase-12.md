# Formation — Phase 12 : Fixtures, qualité et tests automatisés

## 1. Objectifs pédagogiques

À la fin de cette leçon, vous devez être capable de :

- créer des **fixtures** : un jeu de données de démonstration reproductible ;
- expliquer à quoi servent les **linters** de Symfony et ce que chacun vérifie ;
- choisir le bon type de test : `TestCase`, `KernelTestCase` ou `WebTestCase` ;
- écrire un test de parcours qui simule un vrai utilisateur ;
- comprendre pourquoi une **transaction annulée** rend les tests indépendants ;
- savoir ce qu'un test **prouve** — et ce qu'il ne prouve pas.

> ⚠️ C'est **la seule phase du parcours où l'on écrit des tests automatisés**.
> Les onze précédentes se valident au navigateur. Ici, les tests deviennent le
> filet de sécurité qui protège les évolutions futures : c'est une exigence du
> projet, inscrite dans `CLAUDE.md`.

## 2. Prérequis

- Phases 01 à 11 terminées : toutes les fonctionnalités existent.
- Savoir lancer une commande dans le conteneur.
- Connaître les règles métier du projet (elles sont ce que l'on va tester).

---

## 3. Concepts à apprendre

### Concept 1 — Les fixtures

**Pourquoi ?** Un projet qui démarre sur une base vide est intestable : pas de
compte pour se connecter, pas de journal pour afficher un graphique. Et chacun
finirait par créer ses propres données à la main, différentes de celles du
voisin.

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

**Dans ce projet.** Les fixtures créent 1 administrateur, 2 parents, 4 enfants,
des journaux sur plusieurs semaines (dont celui du jour) et une quinzaine de
contenus — **dont un par règle déclencheuse**, sans quoi les conseils de la
phase 09 n'auraient rien à proposer.

---

### Concept 2 — Les linters

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
sans exécuter la moindre page.

---

### Concept 3 — Les trois niveaux de test

**Pourquoi ?** Tester une règle de calcul et tester un parcours de connexion
n'ont ni le même coût ni le même intérêt.

| Classe de base | Ce qu'elle démarre | Pour quoi | Vitesse |
|---|---|---|---|
| `TestCase` | rien | une classe pure (le filtre `duree`) | très rapide |
| `KernelTestCase` | le conteneur de services | un service (`ConseilService`) | rapide |
| `WebTestCase` | un client HTTP simulé | un parcours complet | plus lent |

**Exemple, du plus simple au plus complet.**

```php
// TestCase : aucune dépendance
public function testFormateLesMinutes(): void
{
    $this->assertSame('2 h 30', DureeExtension::formater(150));
}
```

```php
// KernelTestCase : on récupère un service dans le conteneur
$conseils = static::getContainer()->get(ConseilService::class)->getConseils($journal);
```

```php
// WebTestCase : on simule un navigateur
$client = static::createClient();
$client->request('GET', '/enfant/journal');
$this->assertResponseRedirects('/enfant/journal/etape/1');
```

**Règle simple** : prenez le niveau le plus bas qui répond à la question.

---

### Concept 4 — Des tests indépendants

**Pourquoi ?** Un test qui crée un enfant laisserait des données derrière lui. Le
test suivant compterait alors un enfant de trop, et échouerait « sans raison ».

**Comment ça fonctionne ?** `dama/doctrine-test-bundle` ouvre une **transaction**
au début de chaque test et l'**annule** à la fin. Tout ce que le test a écrit
disparaît.

```text
début du test  →  BEGIN TRANSACTION
    le test crée, modifie, supprime librement
fin du test    →  ROLLBACK        (la base retrouve son état initial)
```

Conséquences pratiques :

- l'ordre des tests n'a aucune importance ;
- un test peut supprimer le journal du jour d'un enfant des fixtures sans gêner
  les autres ;
- la base de test reste toujours identique à ce que les fixtures ont chargé.

---

### Concept 5 — Simuler un navigateur

**Pourquoi ?** Un parcours (connexion, formulaire, redirection) ne se teste pas
en appelant une méthode : il faut suivre les pages.

**Comment ça fonctionne ?** Le client `WebTestCase` envoie des requêtes **sans
réseau ni serveur**, directement dans le noyau.

```php
$client = static::createClient();
$crawler = $client->request('GET', '/enfant/journal/etape/1');

$client->submitForm('Suivant : mon corps →', [
    'journal_ecrans[ecranTv]' => '60',
    'journal_ecrans[ecranOrdinateur]' => '45',
]);

$this->assertResponseRedirects('/enfant/journal/etape/2');
$client->followRedirect();
$this->assertSelectorTextContains('body', 'Mon corps');
```

Le `crawler` permet aussi d'inspecter le HTML : `$crawler->filter('input[type="range"]')`
compte les curseurs, par exemple.

**Se connecter dans un test** :

```php
$tom = static::getContainer()->get(UserRepository::class)->findOneBy(['username' => 'tom']);
$client->loginUser($tom);
```

Convention du projet : `loginUser()` partout, **sauf** quand c'est la connexion
elle-même que l'on teste.

---

### Concept 6 — Le code 422

**Pourquoi ?** Beaucoup de débutants écrivent `assertResponseIsSuccessful()`
après avoir soumis un formulaire **invalide** — et le test échoue sans qu'ils
comprennent pourquoi.

**Comment ça fonctionne ?** Depuis Symfony 6.2, un formulaire invalide renvoie
**422 Unprocessable Content**, pas 200. C'est plus juste : la requête a bien été
comprise, mais elle n'a pas pu être traitée.

```php
$client->submitForm('Suivant', [ /* valeurs invalides */ ]);

$this->assertResponseStatusCodeSame(422);                       // ✅
$this->assertSelectorTextContains('body', 'c\'est impossible'); // le message
```

**Dans ce projet.** C'est une erreur réellement commise pendant le développement :
le test du plafond de 16 h attendait un 200. Le code était bon, le test était
faux.

---

### Concept 7 — Ce qu'un test prouve

**Pourquoi ?** Un test vert donne confiance. Parfois à tort.

**Comment ça fonctionne ?** Un test ne prouve qu'une chose : **ce cas précis se
comporte comme écrit**. Il ne prouve pas que la fonctionnalité est correcte, ni
qu'elle plaira à l'utilisateur.

Le meilleur réflexe : **cassez volontairement la règle** que le test protège, et
vérifiez que le test **échoue**. Un test qui ne rougit jamais ne sert à rien.

```php
// Exemple : ce test protège-t-il vraiment le cloisonnement entre familles ?
// → retirez l'appel au voter dans le contrôleur : le test DOIT échouer.
public function testUnParentNePeutPasToucherAuxEnfantsDUnAutre(): void
```

---

## 4. Explications avec exemples

### Un test de règle métier

```php
public function testDeuxHeuresPileDonnentDejaLe202020(): void
{
    $conseils = $this->getConseils(ecranTv: 120, limite: 180);

    $this->assertSame(['Repose tes yeux avec le 20-20-20'], array_column($conseils, 'titre'));
}
```

Court, lisible, et il protège exactement le bug corrigé en phase 09 (le seuil
`>=` au lieu de `>`). Un bon test raconte une règle métier.

Les arguments nommés (`ecranTv:`, `limite:`) rendent l'intention évidente sans
commentaire.

### Un test de parcours complet

```php
public function testParcoursCompletDuJournal(): void
{
    $client = static::createClient();
    $tom = static::getContainer()->get(UserRepository::class)->findOneBy(['username' => 'tom']);
    $client->loginUser($tom);

    // Les fixtures ont déjà rempli la journée de Tom : on la supprime.
    // (La base est remise en état automatiquement après le test.)
    $journalRepository = static::getContainer()->get(JournalEntreeRepository::class);
    $entityManager = static::getContainer()->get(EntityManagerInterface::class);
    $entityManager->remove($journalRepository->findAujourdhui($tom->getProfilEnfant()));
    $entityManager->flush();

    // … étape 1, étape 2

    // On vérifie ce qui est réellement en base, pas seulement l'affichage
    $entityManager->clear();
    $journal = $journalRepository->findAujourdhui($tom->getProfilEnfant());
    $this->assertSame(150, $journal->getTotalEcran());
    $this->assertCount(2, $journal->getDouleurs());
}
```

Trois enseignements :

- le test **peut supprimer** des données des fixtures : la transaction annulera
  tout ;
- `$entityManager->clear()` vide le cache d'objets pour relire depuis la base —
  sans lui, on vérifierait l'objet gardé en mémoire ;
- le test vérifie **l'état final en base**, pas seulement le HTML : c'est ce qui
  permet d'affirmer que les données invalides ont bien été ignorées.

### Ce que couvre la suite du projet

| Fichier | Règles protégées |
|---|---|
| `SecuriteTest` | pages publiques, connexion, inscription, cloisonnement des espaces |
| `ParentEnfantTest` | création d'un enfant et de son compte, validations, 403 entre familles, suppression en cascade, CSRF |
| `JournalTest` | parcours en 2 étapes, curseurs à zéro, plafond de 16 h, étape 2 impossible sans l'étape 1, données invalides ignorées |
| `ConseilServiceTest` | les cinq règles et leurs seuils |
| `DureeExtensionTest` | le formatage des durées |

Quelques dizaines de tests suffisent : ils couvrent les règles **qu'on ne peut
pas se permettre de casser**.

---

## 5. Commandes

### `docker compose exec app composer require --dev doctrine/doctrine-fixtures-bundle`

- **Pourquoi `--dev`** : les fixtures ne doivent **jamais** être installées en
  production — elles purgeraient la base.

### `make fixtures`

- **Ce qu'elle fait** : purge la base et recharge les données de démonstration.
- **Quand** : après avoir cassé ses données pendant des essais.
- **À observer** : « purging database », puis « loading App\DataFixtures\AppFixtures ».

### `make reset-db`

- **Ce qu'elle fait** : supprime la base, la recrée, applique les migrations,
  recharge les fixtures.
- **Quand** : repartir d'un état parfaitement propre.

### `make lint`

- **Ce qu'elle fait** : enchaîne les quatre vérifications.
- **À observer** : **cinq** `[OK]` (schema:validate en affiche deux).

### `make tests`

- **Ce qu'elle fait** : prépare la base de test (création, migrations, fixtures)
  **puis** lance PHPUnit.
- **À observer** : la ligne finale `OK (N tests, M assertions)`. La suite échoue
  aussi sur les **dépréciations** : c'est voulu, cela garde le projet à jour.

### `docker compose exec app php bin/phpunit --testdox`

- **Ce qu'elle fait** : affiche les tests sous forme de phrases lisibles.
- **Quand** : pour relire ce que la suite couvre réellement.

### `docker compose exec app php bin/phpunit --filter testLeTotalDeLaJourneeEstPlafonne`

- **Ce qu'elle fait** : ne lance qu'un test.
- **Quand** : pendant la correction d'un test qui échoue.

---

## 6. Concepts Symfony de la phase

| Composant | Rôle ici |
|---|---|
| **DoctrineFixturesBundle** | données de démonstration |
| **PHPUnit + symfony/test-pack** | exécution des tests |
| **BrowserKit / CssSelector** | client HTTP simulé, `assertSelectorTextContains()` |
| **dama/doctrine-test-bundle** | une transaction annulée par test |
| **Console** | `lint:twig`, `lint:yaml`, `lint:container`, `schema:validate` |

---

## 7. Architecture et organisation du code

```text
src/DataFixtures/
└── AppFixtures.php            toutes les données de démonstration

tests/
├── bootstrap.php              initialisation de l'environnement de test
├── Controller/
│   ├── SecuriteTest.php
│   ├── ParentEnfantTest.php
│   └── JournalTest.php
├── Service/
│   └── ConseilServiceTest.php
└── Twig/
    └── DureeExtensionTest.php

phpunit.dist.xml               configuration de PHPUnit
.env.test                      environnement de test (base séparée)
```

L'arborescence de `tests/` **reflète** celle de `src/` : on trouve le test d'une
classe sans réfléchir.

⚠️ Piège du projet : les droits MySQL de la base de test viennent de
`docker/mysql/init.sql`, qui ne s'exécute **qu'à la création du volume**. Si la
base de test refuse l'accès, il faut recréer le volume
(`docker compose down -v`, puis réinstaller) — c'est documenté dans le README.

---

## 8. Flux de fonctionnement

```text
make tests
    ↓
création de la base digisante_junior_test (si absente)
    ↓
migrations appliquées
    ↓
fixtures chargées
    ↓
PHPUnit démarre
    ↓
pour chaque test :
    BEGIN TRANSACTION
        le test s'exécute (client HTTP simulé, service, ou classe pure)
    ROLLBACK
    ↓
résultat : OK (N tests) — ou la liste des échecs
```

---

## 9. Application au projet

**Pourquoi cette phase ?** Jusqu'ici, chaque phase était validée à la main. À
partir de maintenant, la moindre modification pourrait casser silencieusement une
règle écrite il y a dix phases. Les tests figent ce qui a été construit.

**Composants utilisés** : Fixtures, PHPUnit, BrowserKit, DAMA, Console.

**Fichiers créés** : voir l'arborescence, plus les cibles du `Makefile`.

**Pourquoi ces choix ?**

- **Des fixtures riches** : des journaux sur plusieurs semaines, sinon le
  graphique de la phase 10 serait vide et invérifiable.
- **Un contenu par règle déclencheuse** : sinon les conseils de la phase 09
  s'afficheraient sans contenu associé.
- **DAMA** plutôt que recharger les fixtures entre chaque test : c'est beaucoup
  plus rapide, et parfaitement fiable.
- **Peu de tests, bien choisis** : sécurité, cloisonnement, parcours du journal,
  règles de conseil, formatage. Le projet ne cherche pas 100 % de couverture,
  mais 100 % des **règles critiques**.

**Ce qui reste manuel** : l'ergonomie, le rendu visuel, l'adaptation du ton aux
enfants. Aucun test ne dit si une page est agréable à utiliser.

---

## 10. Erreurs fréquentes

**`Unknown database 'digisante_junior_test'`**
→ La base de test n'a pas été créée.
→ Solution : `make tests` (qui la prépare), ou les trois commandes `--env=test`.

**`Access denied for user 'digisante'@'%' to database 'digisante_junior_test'`**
→ Le volume MySQL a été créé avant `docker/mysql/init.sql`.
→ Solution : `docker compose down -v` puis réinstallation complète.

**Un test échoue avec 422 alors qu'on attendait 200**
→ Le formulaire est invalide : c'est **normal** si c'est ce qu'on testait.
→ Solution : `assertResponseStatusCodeSame(422)`.

**Les tests passent un par un mais échouent tous ensemble**
→ Les données ne sont pas isolées : DAMA n'est pas activé.
→ Vérification : la configuration du bundle dans `config/packages/`.

**`The current node list is empty` lors d'un `submitForm()`**
→ Le libellé du bouton ne correspond pas exactement (accents, emoji, espace).
→ Solution : copier le libellé **tel qu'il apparaît** dans le gabarit.

**Un test vérifie la base et ne voit pas les changements**
→ L'objet est encore en cache mémoire.
→ Solution : `$entityManager->clear()` avant de relire.

**La suite échoue sur une dépréciation, pas sur une assertion**
→ C'est voulu : le projet traite les dépréciations comme des erreurs.
→ Solution : corriger le code déprécié (souvent un `'ASC'`/`'DESC'` en chaîne).

---

## 11. Bonnes pratiques

- **Un test = une règle métier.** Pas de test qui vérifie dix choses à la fois.
- **Nommez les tests en français**, comme des phrases :
  `testUnParentNePeutPasToucherAuxEnfantsDUnAutre`.
- **Vérifiez l'état final en base**, pas seulement l'affichage.
- **Cassez la règle pour vérifier que le test rougit.** C'est le seul moyen de
  savoir qu'il sert à quelque chose.
- **N'adaptez jamais le code pour faire passer un test** : si le test a raison,
  corrigez le code ; s'il a tort, corrigez le test — et dites lequel des deux.
- **Les fixtures ne vont jamais en production** : elles purgent la base.
- **Faites de `make lint` et `make tests` un réflexe** avant de considérer une
  tâche terminée.

---

## 12. Exercice pratique

1. Lancez `make reset-db`, puis connectez-vous avec les trois comptes de
   démonstration. Notez l'identifiant et le mot de passe de chacun.
2. Lancez `docker compose exec app php bin/phpunit --testdox` et **lisez la
   liste** : chaque ligne décrit une règle du projet. Retrouvez celle qui protège
   le plafond de 16 h.
3. Cassez volontairement une règle : dans `ConseilService`, remettez `>` au lieu
   de `>=` pour le seuil de 2 h. Relancez les tests : **un** test doit échouer,
   et le message doit vous dire lequel. Remettez `>=`.
4. Écrivez un test supplémentaire, court, pour une règle qui vous semble non
   couverte — par exemple : « un enfant de 15 ans est refusé à la création ».
   Lancez-le, vérifiez qu'il passe, puis assurez-vous qu'il échoue si vous
   retirez la contrainte d'âge de l'entité.
5. Lancez `make lint` et lisez chacune des quatre sorties : sauriez-vous dire ce
   que chaque commande vient de vérifier ?

---

## 13. Scénario de test manuel

1. Lancer `make reset-db` pour repartir d'une base propre remplie par les fixtures.
2. Se connecter avec chacun des trois comptes de démonstration (admin, parent, enfant).
3. Lancer `make lint`, puis `make tests`.
4. Ouvrir le tableau de bord parent : les journaux des dernières semaines doivent être visibles dans la courbe.
5. **Résultat attendu** : les trois connexions fonctionnent, les quatre linters affichent `[OK]`, la suite PHPUnit est verte, et le graphique est rempli.

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

➡️ [Phase suivante](./phase-13.md)

➡️ [Phase de développement](../README.md#phase-12--données-de-démonstration-qualité-et-tests)

➡️ [Prompt Claude Code](../prompts/phase-12.md)
