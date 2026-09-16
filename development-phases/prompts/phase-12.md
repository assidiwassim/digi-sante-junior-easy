# Prompt Claude Code — Phase 12 : Données de démonstration, qualité et tests

> Copiez tout ce qui suit dans Claude Code, à la racine du projet.

---

## Contexte du projet

**Digi-Santé Junior** : application Symfony de suivi du bien-être numérique des
enfants de 8 à 14 ans. Toutes les fonctionnalités sont développées : comptes et
rôles, espace parent (profils enfants, tableau de bord, graphiques), espace
enfant (journal en 2 étapes, conseils, bibliothèque), espace admin (contenus,
comptes parents).

Stack : PHP 8.4, Symfony 7.4, Doctrine ORM 3, Twig, MySQL 8, Docker.

## Objectif de la phase

Rendre le projet **reprenable par quelqu'un d'autre** : un jeu de données de
démonstration en une commande, des vérifications automatiques de la
configuration, et une suite de tests qui protège les règles métier.

> ⚠️ **C'est la seule phase où l'on écrit des tests automatisés.** Les phases
> précédentes se valident au navigateur ; ici, les tests deviennent le filet de
> sécurité pour les évolutions futures. C'est une exigence du projet.

## Avant de coder

1. Parcours `src/Entity/`, `src/Controller/`, `src/Service/` et `src/Twig/` pour
   lister les règles métier à couvrir.
2. Vérifie quels paquets de développement sont déjà installés.
3. Propose-moi la liste des fixtures et celle des tests **avant** de les écrire.

## À implémenter

### 1. Données de démonstration (`AppFixtures`)

Un jeu cohérent, réaliste, rechargeable à volonté :

- **1 administrateur** : `admin@digisante.local` / `admin123` ;
- **2 parents** : `parent@digisante.local` et `sofia@digisante.local` /
  `parent123`, avec ville et pays ;
- **4 enfants** répartis entre les deux parents : identifiants `lea`, `tom`,
  `noah`, `ines`, mot de passe `enfant123`, avatars variés, âges entre 8 et
  14 ans, limites différentes ;
- des **journaux sur plusieurs semaines** pour chaque enfant, avec des durées
  d'écran variées (certaines sous la limite, d'autres au-dessus) et des douleurs
  occasionnelles, **dont le journal du jour** ;
- une **quinzaine de contenus** de tous les types, dont **un par règle
  déclencheuse** (`20-20-20`, `etirement_cervical`, `yoga_yeux`) pour que le
  moteur de conseils ait toujours de quoi proposer.

Contraintes : mots de passe hachés avec `UserPasswordHasherInterface` ; textes
en français, adaptés aux enfants ; aucune donnée personnelle réelle.

### 2. Confort et vérifications (`Makefile`)

Ajoute ou complète les cibles :

| Cible | Rôle |
|---|---|
| `install` | conteneurs, dépendances, base, migrations, fixtures |
| `fixtures` | recharge les données de démonstration |
| `reset-db` | supprime, recrée, migre et recharge |
| `lint` | `lint:twig`, `lint:yaml`, `lint:container`, `doctrine:schema:validate` |
| `tests` | prépare la base de test **puis** lance PHPUnit |

Chaque cible affiche la commande qu'elle exécute.

### 3. Environnement de test

- Installer `phpunit/phpunit`, `symfony/browser-kit`, `symfony/css-selector`,
  `doctrine/doctrine-fixtures-bundle` et `dama/doctrine-test-bundle` (tous en
  `--dev`).
- Base de test **séparée** : `digisante_junior_test`, remplie avec les fixtures.
- Activer DAMA : chaque test s'exécute dans une transaction **annulée** à la
  fin. Un test peut donc créer ou supprimer des données librement.
- ⚠️ Droits MySQL : le script d'initialisation (`docker/mysql/init.sql`) ne
  s'exécute qu'à la **création du volume**. Assure-toi que l'utilisateur
  `digisante` a les droits sur `digisante_junior%`, et documente le cas dans le
  README (`docker compose down -v` puis réinstallation).
- PHPUnit doit **échouer aussi sur les dépréciations**.

### 4. Les tests

Écris des tests **courts et lisibles**, un fichier par sujet :

| Fichier | Ce qui est couvert |
|---|---|
| `tests/Controller/SecuriteTest.php` | pages publiques, connexion parent, échec de connexion enfant, inscription, chaque rôle reste dans son espace |
| `tests/Controller/ParentEnfantTest.php` | création d'un enfant et de son compte, erreurs de saisie, cloisonnement entre parents (403), suppression avec cascade, suppression refusée sans CSRF |
| `tests/Controller/JournalTest.php` | parcours complet du journal, curseurs à zéro à l'ouverture, plafond du total, étape 2 impossible sans l'étape 1, données invalides ignorées |
| `tests/Service/ConseilServiceTest.php` | les cinq règles, seuils inclus (2 h pile, douleur au cou à 2 puis à 3) |
| `tests/Twig/DureeExtensionTest.php` | le filtre `duree` (« 45 min », « 2 h », « 2 h 30 ») |

Règles d'écriture :

- `WebTestCase` pour un parcours, `KernelTestCase` pour un service, `TestCase`
  pour une classe pure ;
- `$client->loginUser()` pour se connecter, **sauf** quand c'est la connexion
  elle-même qu'on teste ;
- noms de méthodes en français, explicites
  (`testUnParentNePeutPasToucherAuxEnfantsDUnAutre`) ;
- ⚠️ un formulaire invalide renvoie **422**, pas 200 : utilise
  `assertResponseStatusCodeSame(422)` ;
- pas de tests redondants : une règle métier, un test.

### 5. Documentation

Mets à jour le `README.md` : installation, comptes de démonstration, commandes
utiles, préparation de la base de test, problèmes fréquents.

## Contraintes techniques et architecturales

- Les données de démonstration vont dans `AppFixtures`, **jamais** en dur dans
  le code applicatif ou les gabarits.
- Ne modifie pas le comportement de l'application pour faire passer un test :
  si un test échoue, dis-le-moi et propose la correction **séparément**.
- Les fixtures purgent la base : rappelle-le dans l'aide du `Makefile`.

## Commandes attendues

```bash
docker compose exec app composer require --dev doctrine/doctrine-fixtures-bundle
docker compose exec app composer require --dev phpunit/phpunit symfony/browser-kit symfony/css-selector
docker compose exec app composer require --dev dama/doctrine-test-bundle
make fixtures
make lint
make tests
```

## Ce qui n'est PAS dans cette phase

- Pas de nouvelle fonctionnalité métier.
- Pas de refonte de l'existant « au passage ».
- Pas de mise en production (phase 13).

## Scénario de test manuel

1. Lancer `make reset-db` pour repartir d'une base propre remplie par les fixtures.
2. Se connecter successivement avec les trois comptes de démonstration : administrateur, parent, enfant.
3. Ouvrir le tableau de bord parent et vérifier que la courbe des 30 derniers jours est remplie.
4. Lancer `make lint`, puis `make tests`.
5. **Résultat attendu** : les trois connexions fonctionnent, le graphique contient l'historique des fixtures, les quatre linters affichent `[OK]` et la suite PHPUnit est verte, sans dépréciation.

## Critères de validation

- [ ] `make install` remonte un environnement complet sur une machine vierge.
- [ ] Les fixtures créent au moins un contenu par règle déclencheuse.
- [ ] Les enfants de démonstration ont un journal **du jour** (le parcours en
      2 étapes se teste après suppression de ce journal — documente la commande).
- [ ] `make tests` est vert et chaque test échoue si l'on casse volontairement
      la règle qu'il protège (vérifie-le au moins pour un test).
- [ ] Le README permet à quelqu'un d'autre d'installer le projet sans aide.

## Enfin

- Termine en me listant les règles métier couvertes par les tests **et** celles
  qui ne le sont pas, pour que je sache où je marche sans filet.
