# Prompt Claude Code — Phase 12 : Données de démonstration et qualité

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
configuration (linters) et une documentation qui permet d'installer le projet
sans aide. C'est la **dernière phase** du parcours.

> Comme dans toutes les phases, **aucun test automatisé** : la validation se
> fait au navigateur, en rejouant les scénarios des phases précédentes sur les
> données de démonstration.

## Avant de coder

1. Parcours `src/Entity/` pour connaître les champs et les règles de validation
   à respecter dans les données.
2. Tous les paquets sont installés depuis la phase 01, dont
   `doctrine/doctrine-fixtures-bundle` ; s'il en manque un, signale-le.
3. Propose-moi la liste des fixtures **avant** de les écrire.

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

Contraintes :

- mots de passe hachés avec `UserPasswordHasherInterface` ;
- textes en français, adaptés aux enfants ; aucune donnée personnelle réelle ;
- données **reproductibles** : graine aléatoire fixe (`mt_srand()`), et dates de
  naissance **relatives** à aujourd'hui pour que les âges restent entre 8 et
  14 ans quel que soit le jour du chargement ;
- chaque durée d'écran est un multiple de 15 minutes, et le total d'une journée
  reste sous le plafond de 16 h.

### 2. Confort et vérifications (`Makefile`)

Ajoute ou complète les cibles (`migrate` existe déjà depuis la phase 02) :

| Cible | Rôle |
|---|---|
| `install` | conteneurs, dépendances, base, migrations, fixtures |
| `fixtures` | recharge les données de démonstration |
| `reset-db` | supprime, recrée, migre et recharge |
| `lint` | `lint:twig`, `lint:yaml`, `lint:container`, `doctrine:schema:validate` |

Chaque cible affiche la commande qu'elle exécute. Les fixtures **purgent** la
base : rappelle-le dans l'aide du `Makefile`.

### 3. Documentation

Mets à jour le `README.md` : installation, comptes de démonstration, commandes
utiles, commande pour supprimer le journal du jour (afin de rejouer le parcours
en 2 étapes), problèmes fréquents (port déjà utilisé, journal déjà rempli,
erreur après un `git pull` → `make migrate`).

## Contraintes techniques et architecturales

- Les données de démonstration vont dans `AppFixtures`, **jamais** en dur dans
  le code applicatif ou les gabarits.
- Pas de nouvelle fonctionnalité, pas de refonte de l'existant « au passage ».
- Si une vérification échoue (linter, parcours au navigateur), dis-le-moi et
  propose la correction **séparément**.

## Commandes attendues

```bash
make fixtures
make reset-db
make lint
```

## Ce qui n'est PAS dans cette phase

- Pas de tests automatisés (PHPUnit) : **hors périmètre du parcours**.
- Pas de nouvelle fonctionnalité métier.
- Pas de mise en production.

## Scénario de test manuel

1. Lancer `make reset-db` pour repartir d'une base propre remplie par les fixtures.
2. Se connecter successivement avec les trois comptes de démonstration : administrateur, parent, enfant.
3. Ouvrir le tableau de bord parent et vérifier que la courbe des 30 derniers jours est remplie.
4. Supprimer le journal du jour (commande du README), puis remplir un journal complet en tant qu'enfant et lire les conseils.
5. Lancer `make lint`.
6. **Résultat attendu** : les trois connexions fonctionnent, le graphique contient l'historique des fixtures, le journal se rejoue sans erreur et les quatre linters affichent `[OK]`.

## Critères de validation

- [ ] `make install` remonte un environnement complet sur une machine vierge.
- [ ] Les fixtures créent au moins un contenu par règle déclencheuse.
- [ ] Les enfants de démonstration ont un journal **du jour** ; la commande pour
      le supprimer est documentée.
- [ ] Recharger deux fois les fixtures donne les mêmes données.
- [ ] `make lint` affiche quatre `[OK]`.
- [ ] Le README permet à quelqu'un d'autre d'installer le projet sans aide.

## Enfin

- N'écris **aucun test automatisé** : je valide au navigateur.
- Termine en me donnant une **checklist de vérification manuelle** (10 points
  maximum) à rejouer au navigateur avant chaque livraison, en reprenant les
  scénarios les plus importants des phases précédentes.
