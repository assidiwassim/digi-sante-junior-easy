# Prompt Claude Code — Phase 03 : Base de données, Doctrine et entité `User`

> Copiez tout ce qui suit dans Claude Code, à la racine du projet.

---

## Contexte du projet

**Digi-Santé Junior** : application Symfony de suivi du bien-être numérique des
enfants de 8 à 14 ans. Trois rôles **sans hiérarchie** (un administrateur n'est
ni parent ni enfant) :

| Rôle | Espace | Connexion |
|---|---|---|
| `ROLE_ADMIN` | `/admin` | **email** |
| `ROLE_PARENT` | `/parent` | **email** |
| `ROLE_CHILD` | `/enfant` | **identifiant** (pas d'email) |

Stack : PHP 8.4, Symfony 7.4, **Doctrine ORM 3**, MySQL 8, Twig, Docker.
Je débute avec Symfony : code simple, en français, sans sur-ingénierie.

## Objectif de la phase

Connecter l'application à MySQL et créer la première table : `users`, le compte
de connexion commun aux trois rôles. Ajouter phpMyAdmin pour regarder la base
sans écrire de SQL.

## Avant de coder

1. Lis `compose.yaml`, `.env` et `composer.json` pour voir la configuration
   existante.
2. Vérifie que le service `database` tourne et que `DATABASE_URL` pointe bien
   sur lui (hôte `database`, port 3306 **dans** le réseau Docker).
3. Explique-moi la différence entre `.env` et `.env.local` avant de toucher à
   ces fichiers.

## À implémenter

### 1. Doctrine

- Installer `symfony/orm-pack` et, en dépendance de développement,
  `symfony/maker-bundle`.
- Vérifier la configuration générée dans `config/packages/doctrine.yaml` et la
  commenter en français là où c'est utile.

### 2. phpMyAdmin

Ajouter un service `phpmyadmin` dans `compose.yaml` :

- image `phpmyadmin:5.2`, port `8082:80` ;
- `PMA_HOST: database`, `PMA_PORT: 3306`, `PMA_USER` et `PMA_PASSWORD` renseignés
  pour que la connexion soit **automatique** (aucun écran de login) ;
- `TZ: Europe/Paris` ;
- `depends_on` avec `condition: service_healthy`.

Note dans un commentaire que c'est un outil **de développement uniquement**.

### 3. Entité `User`

Un seul type de compte pour les trois rôles :

| Propriété | Type | Règles |
|---|---|---|
| `id` | `int` | clé primaire auto |
| `email` | `?string(180)` | **unique**, nullable (les enfants n'en ont pas), validé par `#[Assert\Email]` |
| `username` | `?string(60)` | **unique**, nullable (réservé aux enfants) |
| `roles` | `json` | liste de rôles |
| `password` | `string` | mot de passe **haché**, jamais en clair |
| `pays` | `?string(80)` | facultatif |
| `ville` | `?string(80)` | facultatif |
| `createdAt` | `datetime_immutable` | rempli dans le constructeur |

Exigences :

- la classe implémente `UserInterface` et `PasswordAuthenticatedUserInterface` ;
- `getUserIdentifier()` renvoie l'email **sinon** le username ;
- `getRoles()` ajoute toujours `ROLE_USER` ;
- les rôles sont des **constantes de classe** (`ROLE_ADMIN`, `ROLE_PARENT`,
  `ROLE_CHILD`) : pas d'enum PHP ;
- `setEmail()` et `setUsername()` enregistrent en **minuscules**, sans espaces
  autour ;
- `#[UniqueEntity]` sur `email` et sur `username`, avec des messages en français
  (« Cette adresse email est déjà utilisée. ») ;
- une méthode `isParent()` pratique pour la suite.

⚠️ Les setters liés à un formulaire acceptent `null` (`?string`) : sinon un
champ vide provoque une erreur 500 **avant** la validation.

### 4. Migration

- Générer la migration, **me la faire relire** avant de l'appliquer, puis
  l'appliquer.
- Vérifier ensuite avec `doctrine:schema:validate`.

## Contraintes techniques et architecturales

- **Toute** modification du schéma passe par une migration : jamais de SQL à la
  main dans la base.
- Les requêtes Doctrine vivent dans les **repositories**, jamais dans un
  contrôleur.
- Dates : type `datetime_immutable` ; pour une date sans heure (plus tard),
  ce sera `date_immutable`.
- Pas d'enum PHP : des constantes de classe.
- Propriétés `private`, getters/setters classiques, pas de magie.

## Commandes attendues

```bash
docker compose exec app composer require symfony/orm-pack
docker compose exec app composer require --dev symfony/maker-bundle
docker compose exec app php bin/console make:entity
docker compose exec app php bin/console make:migration
docker compose exec app php bin/console doctrine:migrations:migrate --no-interaction
docker compose exec app php bin/console doctrine:schema:validate
```

## Ce qui n'est PAS dans cette phase

- Pas de formulaire d'inscription ni de connexion (phase 04).
- Pas de `security.yaml`, pas de rôle appliqué à une URL (phase 04).
- Pas d'entité `Enfant`, `JournalEntree`, `DouleurZone` ou `ContenuBienEtre`
  (phases 05, 07 et 08).
- Aucun utilisateur créé en base pour l'instant.

## Scénario de test manuel

1. Lancer la migration : `docker compose exec app php bin/console doctrine:migrations:migrate --no-interaction`.
2. Ouvrir phpMyAdmin sur `http://localhost:8082` : la connexion doit être automatique.
3. Ouvrir la base `digisante_junior`, puis la table `users`, onglet « Structure ».
4. Vérifier les colonnes `email`, `username`, `roles`, `password`, `pays`, `ville`, `created_at`, et les index uniques sur `email` et `username`.
5. **Résultat attendu** : la table existe avec les bonnes colonnes et contraintes, et `doctrine:schema:validate` affiche `[OK]` pour le mapping **et** pour la base.

## Critères de validation

- [ ] `doctrine:schema:validate` : deux `[OK]`.
- [ ] La table `users` est visible dans phpMyAdmin avec ses index uniques.
- [ ] `User` implémente les deux interfaces de sécurité et expose ses rôles en
      constantes.
- [ ] Le fichier de migration est lisible et ne contient que ce changement.
- [ ] `.env` ne contient aucun mot de passe réel autre que celui du Docker local.

## Enfin

- N'écris **aucun test automatisé** : je valide dans phpMyAdmin.
- Termine en m'expliquant en quelques lignes ce que fait exactement une
  migration, et pourquoi on ne modifie jamais une migration déjà appliquée.
