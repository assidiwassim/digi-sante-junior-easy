# Formation Symfony — Digi-Santé Junior

Une formation Symfony **progressive**, construite autour d'un vrai projet : la
plateforme Digi-Santé Junior. Une leçon par phase de développement, 13 au total.

Ici, on n'apprend pas Symfony « dans le vide » : chaque notion arrive au moment
où vous en avez besoin pour construire la fonctionnalité suivante.

---

## Objectif de la formation

À la fin des 13 leçons, vous devez être capable de :

- expliquer **ce que fait Symfony** à votre place et ce qui reste à votre charge ;
- lire et écrire un contrôleur, un gabarit Twig, une entité, un repository, un
  formulaire, un service et un voter ;
- comprendre **pourquoi** le projet est organisé ainsi, et pas seulement
  **comment** le recopier ;
- diagnostiquer seul les erreurs les plus courantes (404, 403, 422, 500, erreur
  de migration, formulaire qui ne se soumet pas) ;
- relire du code écrit par quelqu'un d'autre — ou par Claude Code — et dire si
  c'est correct.

## Public cible

Un développeur qui **débute avec Symfony**. On suppose que vous :

- connaissez les bases de la programmation ;
- savez lire du PHP, y compris les classes, les objets et les types ;
- savez ouvrir un terminal et lancer une commande ;
- connaissez HTML et CSS.

On ne suppose **jamais** que vous connaissez déjà l'injection de dépendances,
le conteneur de services, Doctrine, la sécurité Symfony, les formulaires, les
validateurs, les voters, les repositories, le QueryBuilder ou Twig. Chaque
notion est expliquée la première fois qu'elle apparaît.

## Prérequis

- [Docker Desktop](https://www.docker.com/products/docker-desktop/) installé et
  lancé (il fournit `docker compose`).
- Un éditeur de code et un terminal.
- **Rien d'autre** : PHP, Composer et MySQL tournent dans les conteneurs.

## Stack utilisée

| Rôle | Outil |
|---|---|
| Langage | PHP 8.4 |
| Framework | Symfony 7.4 (LTS) |
| Base de données | MySQL 8 |
| ORM | Doctrine ORM 3 |
| Gabarits | Twig |
| Interface | Bootstrap 5.3 par CDN + `public/css/app.css` |
| Graphiques | Chart.js par CDN |
| Tests | PHPUnit 12 + `dama/doctrine-test-bundle` |
| Environnement | Docker : FrankenPHP, MySQL, phpMyAdmin |

**Ni Node.js, ni npm, ni build front** : c'est un choix du projet pour rester
simple. Il n'y a donc aucune commande NPM dans cette formation.

---

## Comment utiliser la formation

Pour chaque phase, dans cet ordre :

1. **Lisez la leçon** (`formation/phase-XX.md`) : les concepts, les exemples,
   les commandes, les pièges.
2. **Faites l'exercice pratique** de la leçon. Il est court et tient dans les
   notions de la phase.
3. **Lisez la phase de développement** (`../README.md`) : ce qu'il faut
   construire, les fichiers concernés, le résultat attendu.
4. **Lancez l'implémentation** avec le prompt Claude Code
   (`../prompts/phase-XX.md`).
5. **Relisez le code produit** : la leçon vous a donné de quoi le comprendre.
   C'est le moment le plus formateur.
6. **Jouez le scénario de test manuel**, puis cochez la checklist.
7. Passez à la phase suivante.

> ⚠️ Ne sautez pas l'étape 5. Faire écrire du code que l'on ne relit pas, c'est
> apprendre à cliquer, pas à développer.

## Comment la formation est liée aux phases de développement

Trois documents, trois questions différentes :

```text
../README.md              →  QUOI développer dans cette phase ?
formation/phase-XX.md     →  QUELS concepts comprendre ? COMMENT ça marche ?
../prompts/phase-XX.md    →  COMMENT demander l'implémentation à Claude Code ?
                             puis : tester manuellement → valider → phase suivante
```

La formation **complète** le parcours de développement, elle ne le remplace pas.
Les deux documents partagent le même scénario de test manuel : c'est lui qui
décide si la phase est terminée.

## Comment utiliser les prompts Claude Code

- Un prompt est **autonome** : il rappelle le contexte, décrit ce qu'il faut
  implémenter, les contraintes, le test manuel et les critères de validation.
- Copiez **tout le fichier** dans Claude Code, à la racine de votre projet.
- Chaque prompt demande explicitement d'**analyser l'existant avant de
  modifier**, de respecter les conventions du projet et de **ne pas anticiper**
  les phases suivantes.
- Aucun prompt ne demande de tests automatisés, **sauf celui de la phase 12**
  qui leur est consacrée. Partout ailleurs, la validation est manuelle.
- Si le code produit vous semble étrange, demandez une explication avant
  d'accepter : c'est un excellent exercice.

---

## Parcours complet

| Phase | Formation | Développement | Prompt Claude Code |
|---|---|---|---|
| 01 — Environnement Docker et squelette Symfony | [Formation](./phase-01.md) | [Phase 01](../README.md#phase-01--environnement-docker-et-squelette-symfony) | [Prompt](../prompts/phase-01.md) |
| 02 — Gabarit de base, charte graphique et accueil | [Formation](./phase-02.md) | [Phase 02](../README.md#phase-02--gabarit-de-base-charte-graphique-et-accueil) | [Prompt](../prompts/phase-02.md) |
| 03 — Base de données, Doctrine et entité `User` | [Formation](./phase-03.md) | [Phase 03](../README.md#phase-03--base-de-données-doctrine-et-entité-user) | [Prompt](../prompts/phase-03.md) |
| 04 — Inscription, connexion et rôles | [Formation](./phase-04.md) | [Phase 04](../README.md#phase-04--inscription-connexion-et-rôles) | [Prompt](../prompts/phase-04.md) |
| 05 — Espace parent : profils enfants | [Formation](./phase-05.md) | [Phase 05](../README.md#phase-05--espace-parent--profils-enfants) | [Prompt](../prompts/phase-05.md) |
| 06 — Espace enfant : connexion et accueil | [Formation](./phase-06.md) | [Phase 06](../README.md#phase-06--espace-enfant--connexion-et-accueil) | [Prompt](../prompts/phase-06.md) |
| 07 — Journal quotidien en 2 étapes | [Formation](./phase-07.md) | [Phase 07](../README.md#phase-07--journal-quotidien-en-2-étapes) | [Prompt](../prompts/phase-07.md) |
| 08 — Bibliothèque de contenus (admin + enfant) | [Formation](./phase-08.md) | [Phase 08](../README.md#phase-08--bibliothèque-de-contenus-admin--enfant) | [Prompt](../prompts/phase-08.md) |
| 09 — Moteur de conseils | [Formation](./phase-09.md) | [Phase 09](../README.md#phase-09--moteur-de-conseils) | [Prompt](../prompts/phase-09.md) |
| 10 — Tableau de bord parent et graphiques | [Formation](./phase-10.md) | [Phase 10](../README.md#phase-10--tableau-de-bord-parent-et-graphiques) | [Prompt](../prompts/phase-10.md) |
| 11 — Administration des comptes parents | [Formation](./phase-11.md) | [Phase 11](../README.md#phase-11--administration-des-comptes-parents) | [Prompt](../prompts/phase-11.md) |
| 12 — Données de démonstration, qualité et tests | [Formation](./phase-12.md) | [Phase 12](../README.md#phase-12--données-de-démonstration-qualité-et-tests) | [Prompt](../prompts/phase-12.md) |
| 13 — Performance, robustesse et mise en production | [Formation](./phase-13.md) | [Phase 13](../README.md#phase-13--performance-robustesse-et-mise-en-production) | [Prompt](../prompts/phase-13.md) |

---

## Progression des notions

Chaque notion apparaît **une seule fois**, au moment où elle sert. Voici où
chacune est enseignée :

```text
Phase 01  Docker, Composer, autoload, front controller, Kernel, requête/réponse,
          routes et contrôleurs
Phase 02  Twig : héritage de gabarits, blocs, variables, échappement, assets
Phase 03  Doctrine : entités, mapping, repositories, migrations, DATABASE_URL
Phase 04  Security : firewall, provider, hachage, access_control
          Forms + Validator : *Type, handleRequest, contraintes, CSRF, flash
Phase 05  Relations Doctrine, cascades, Voters, extension Twig, transformers
Phase 06  Chargement d'utilisateur sur mesure, #[CurrentUser], layouts par espace
Phase 07  Session, formulaire multi-étapes, contrainte multi-champs,
          index unique, JavaScript vanilla, revalidation serveur
Phase 08  CRUD complet, résolution d'entité par l'URL, données métier en base
Phase 09  Services, injection de dépendances, conteneur de services, autowiring
Phase 10  QueryBuilder, paramètres d'URL, PHP → JavaScript, code partagé
Phase 11  Requête sur une colonne JSON, 404 volontaire, cascades vérifiées
Phase 12  Fixtures, linters, PHPUnit, transactions de test
Phase 13  Environnement prod, requêtes N+1, profiler, sécurité, exploitation
```

---

## Conseils pour bien apprendre

- **Tapez les commandes vous-même** au moins une fois, même si `make` les
  enchaîne pour vous.
- **Ouvrez le profiler** (la barre noire en bas des pages en développement) :
  c'est le meilleur outil de compréhension de Symfony.
- **Cassez des choses volontairement** : renommez une route, retirez une
  contrainte, videz un champ. Lire un message d'erreur, c'est apprendre.
- **Ne copiez jamais un fichier sans le comprendre.** Si un bout de code vous
  échappe, demandez une explication avant d'avancer.
- Gardez le fichier `CLAUDE.md` du projet sous la main : il résume les
  conventions et les pièges connus.

➡️ Commencer : [Formation Phase 01](./phase-01.md)
