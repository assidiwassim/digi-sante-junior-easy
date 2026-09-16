# Prompt Claude Code — Phase 11 : Administration des comptes parents

> Copiez tout ce qui suit dans Claude Code, à la racine du projet.

---

## Contexte du projet

**Digi-Santé Junior** : application Symfony de suivi du bien-être numérique des
enfants de 8 à 14 ans. Trois rôles sans hiérarchie : `ROLE_ADMIN`,
`ROLE_PARENT`, `ROLE_CHILD`.

Déjà en place : comptes et connexion, espace parent complet (profils enfants,
tableau de bord, graphiques), espace enfant complet (journal, conseils,
bibliothèque), espace admin avec le CRUD des contenus.

Stack : PHP 8.4, Symfony 7.4, Doctrine ORM 3, Twig, Bootstrap 5.3 par CDN,
MySQL 8, Docker. Code simple, en français.

## Objectif de la phase

Terminer l'espace d'administration : lister les comptes parents, consulter la
fiche d'un parent, et supprimer un compte avec **toutes** ses données.

## Avant de coder

1. Lis `src/Controller/Admin/ContenuController.php`,
   `templates/admin/layout.html.twig`, `src/Entity/User.php`,
   `src/Entity/Enfant.php` (relations et cascades) et
   `templates/_partials/bouton_supprimer.html.twig`.
2. Vérifie **concrètement** quelles cascades sont déjà configurées :
   parent → enfants, enfant → compte, enfant → journaux, journal → douleurs.
   Dis-moi si une cascade manque **avant** d'écrire le code de suppression.
3. Suis les conventions de l'admin des contenus : même layout, même partiel de
   suppression, mêmes noms de routes (`admin_…`).

## À implémenter

### 1. Repository

`UserRepository::findParents(): array` — les comptes ayant `ROLE_PARENT`,
du plus récent au plus ancien.

⚠️ Les rôles sont stockés en **JSON** (`["ROLE_PARENT"]`) : filtre avec un
`LIKE` sur la chaîne du rôle, en le documentant par un commentaire. Le tri
utilise `\SortDirection::Descending` (passer `'DESC'` en chaîne est déprécié).

### 2. `Admin\ParentController`, préfixe `/admin/parents`

- `admin_parents` : tableau des comptes parents — email, localisation (ville,
  pays, ou `—`), nombre d'enfants, date d'inscription, actions (**Voir**,
  **Supprimer**) ; nombre total affiché au-dessus.
- `admin_parent_voir` : fiche d'un parent — email, ville, pays, date
  d'inscription, bouton de suppression, et la **liste de ses enfants en lecture
  seule** (avatar, nom complet, âge, identifiant, limite).
  ⚠️ L'administrateur **ne gère pas** les profils enfants : ils relèvent de leur
  parent. Ces lignes ne sont donc **pas cliquables** et n'offrent aucune action.
- `admin_parent_supprimer` : **POST uniquement**, jeton CSRF vérifié, puis
  suppression du compte. Le message flash indique **combien de profils enfants**
  ont été supprimés avec lui.

Dans les deux dernières actions, l'identifiant d'URL peut désigner n'importe
quel compte : si ce n'est **pas** un parent, lève une **404** explicite
(« Ce compte n'est pas un compte parent. »).

`requirements: ['id' => '\d+']` sur chaque `{id}`.

### 3. Menu

Ajoute l'entrée **👨‍👩‍👧 Parents** au menu de `admin/layout.html.twig`, à côté
de **📚 Contenus**, avec la mise en évidence du lien actif.

### 4. Suppression en cascade

La suppression d'un parent doit entraîner, **par les cascades Doctrine** et non
par du code impératif :

```text
Parent ──► Enfants ──► Compte de connexion de l'enfant
                  └──► Journaux ──► Douleurs
```

N'écris **aucune** classe « manager » de suppression, aucune boucle de
suppression manuelle : si une cascade manque, corrige la configuration de
l'entité et génère une migration.

## Contraintes techniques et architecturales

- Toutes les requêtes dans les repositories.
- Toute action qui modifie des données est en **POST** + jeton CSRF + une
  confirmation navigateur (partiel existant).
- Le message de confirmation doit être explicite sur le caractère **définitif**
  de l'opération.
- Contrôleurs simples, injection en argument de l'action.
- Aucune logique métier dans Twig.

## Commandes attendues

```bash
docker compose exec app php bin/console debug:router | grep admin
docker compose exec app php bin/console dbal:run-sql "SELECT id, email, roles FROM users"
docker compose exec app php bin/console doctrine:schema:validate
docker compose exec app php bin/console lint:twig templates
```

## Ce qui n'est PAS dans cette phase

- Pas de section « Enfants » dans l'administration : c'est un choix du projet.
- Pas de création ni de modification de compte parent par l'admin (le parent
  s'inscrit lui-même et gère son profil).
- Pas de création de compte administrateur par l'interface.
- Pas de fixtures (phase 12).

## Scénario de test manuel

1. Créer un compte parent de test via `/inscription`, s'y connecter et lui ajouter un enfant, puis remplir un journal pour cet enfant.
2. Se connecter en administrateur, ouvrir `/admin/parents` et vérifier que le compte de test apparaît avec « 1 » enfant.
3. Ouvrir sa fiche : l'enfant doit être listé, sans lien ni bouton d'action.
4. Supprimer le compte, confirmer, puis vérifier dans phpMyAdmin les tables `users`, `enfant`, `journal_entree` et `douleur_zone`.
5. **Résultat attendu** : le message indique le nombre de profils enfants supprimés, et plus aucune ligne liée à ce parent ne subsiste dans les quatre tables.

## Critères de validation

- [ ] `/admin/parents/{id}` avec l'identifiant d'un **enfant** renvoie 404.
- [ ] La suppression sans jeton CSRF est refusée (403).
- [ ] Aucune ligne orpheline après suppression (vérifié en base).
- [ ] La fiche parent n'offre **aucune** action sur les profils enfants.
- [ ] Un parent ou un enfant qui ouvre `/admin/parents` reçoit 403.
- [ ] `doctrine:schema:validate` et `lint:twig templates` sont au vert.

## Enfin

- N'écris **aucun test automatisé** : je valide au navigateur et dans phpMyAdmin.
- Termine en m'expliquant en quelques lignes la différence entre
  `cascade: ['remove']` (côté Doctrine) et `onDelete: 'CASCADE'` (côté base de
  données), et pourquoi ce projet utilise les deux.
