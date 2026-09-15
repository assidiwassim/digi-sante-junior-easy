# CLAUDE.md

Règles de développement de **Digi-Santé Junior**, pour Claude Code et pour
tout développeur qui reprend le projet.

> Le code, les commentaires, les routes et les messages sont **en français**,
> avec le vocabulaire métier (`Enfant`, `JournalEntree`, `getTotalEcran()`…).
> Seuls les mots imposés par Symfony restent en anglais (`User`, `getRoles()`…).

---

## 1. Le projet

Application Symfony de suivi du bien-être numérique des 8-14 ans, écrite de
manière **volontairement simple** pour rester lisible par un débutant.

Trois rôles, sans hiérarchie (un admin n'est ni parent ni enfant) :

| Rôle | Espace | Connexion |
|---|---|---|
| `ROLE_ADMIN` | `/admin` : contenus (CRUD), parents (liste, fiche, suppression) | email sur `/login` |
| `ROLE_PARENT` | `/parent` : tableau de bord, enfants (CRUD + limite d'écran), profil | email sur `/login` |
| `ROLE_CHILD` | `/enfant` : accueil, journal en 2 étapes, conseils, bibliothèque, profil | identifiant sur `/connexion-enfant` |

**Hors périmètre** (ne pas ajouter sans demande explicite) : emails,
notifications, badges, suivi du sport, du sommeil ou de l'humeur, API.

---

## 2. Stack technique

- PHP 8.4, **Symfony 7.4**, Doctrine ORM 3, MySQL 8
- Twig + **Bootstrap 5.3 par CDN** + `public/css/app.css` ; Chart.js par CDN
- Aucun bundler, aucun Node.js, pas d'AssetMapper : les fichiers de `public/`
  sont servis tels quels (d'où l'absence de dossier `assets/`)
- PHPUnit 12 + `dama/doctrine-test-bundle`
- Docker : un conteneur `app` (FrankenPHP), un conteneur `database` (MySQL) et
  un conteneur `phpmyadmin` (consultation de la base, développement uniquement)

## 3. Commandes

**Rien ne tourne sur la machine hôte** : toute commande PHP passe par le conteneur.

```bash
docker compose up -d --build                      # démarrer
docker compose exec app php bin/console <cmd>     # console Symfony
docker compose exec app composer <cmd>            # Composer (dans l'image du projet)
make                                              # liste des raccourcis
```

| URL / service | Valeur |
|---|---|
| Application | <http://localhost:8081> |
| phpMyAdmin | <http://localhost:8082> (connecté d'office, sans mot de passe à saisir) |
| MySQL (hôte) | `127.0.0.1:3308`, `digisante` / `digisante`, base `digisante_junior` |

Comptes : `admin@digisante.local` / `admin123`, `parent@digisante.local` et
`sofia@digisante.local` / `parent123`, enfants `lea` `tom` `noah` `ines` / `enfant123`.

---

## 4. Architecture

```text
Requête -> Contrôleur -> Repository / Entité (Doctrine) -> MySQL
                     \-> Formulaire (validation)
                     \-> Gabarit Twig
```

```text
src/
  Controller/{Admin,Enfant,Parent}  + HomeController, SecurityController
  Entity/          User, Enfant, JournalEntree, DouleurZone, ContenuBienEtre
  Form/            un *Type par formulaire
  Repository/      toutes les requêtes Doctrine
  Security/        EnfantVoter
  Service/         ConseilService (seul service métier)
  Twig/            DureeExtension (filtre « duree »)
  DataFixtures/    AppFixtures
templates/
  base.html.twig   + {admin,enfant,parent}/layout.html.twig
  _partials/       uniquement ce qui est réutilisé
```

### Règles d'architecture

- **Symfony classique, rien de plus.** Pas de CQRS, DDD, couche « application »,
  DTO, interfaces ou repositories abstraits, événements métier.
- Contrôleurs **simples** : lire la requête, appeler Doctrine ou un formulaire,
  rendre un gabarit. Pas de DQL dans un contrôleur (les requêtes vont dans le
  repository), pas de logique métier dans Twig.
- Un **service** n'est créé que si le code est partagé par plusieurs
  contrôleurs (c'est le cas de `ConseilService`, utilisé par l'enfant et le parent).
- Pas d'enums PHP : les listes fixes (avatars, zones du corps, types de contenu,
  règles) sont des **constantes** dans l'entité concernée (`Enfant::AVATARS`,
  `DouleurZone::ZONES`, `ContenuBienEtre::TYPES`…), avec des getters d'affichage
  (`getAvatarEmoji()`, `getZoneLabel()`…).
- Les suppressions s'appuient sur les **cascades Doctrine** (`cascade: ['remove']`) :
  parent → enfants → compte + journaux → douleurs. Pas de « manager » de suppression.
- Avant d'ajouter une classe, se demander si 20 lignes dans le contrôleur ou
  l'entité ne suffisent pas.

---

## 5. Conventions de code

- Noms explicites en français ; méthodes courtes ; une responsabilité par méthode.
- Injection des dépendances **en argument de l'action** du contrôleur
  (`EntityManagerInterface $entityManager`), utilisateur connecté via
  `#[CurrentUser] User $user`.
- Commentaires utiles uniquement : expliquer **pourquoi**, pas répéter le code.
- Entités : propriétés `private`, getters/setters classiques. Les setters liés à
  un formulaire acceptent `null` (`?string`) : sinon un champ vide provoque une
  erreur 500 avant la validation.
- Routes en attributs `#[Route]`, noms préfixés par l'espace : `admin_…`,
  `parent_…`, `enfant_…`, `app_…` pour le public. Ajouter
  `requirements: ['id' => '\d+']` sur les paramètres `{id}`.
- Pas de solution « astucieuse » (setters dynamiques, magie) : du code qu'un
  débutant lit de haut en bas.

## 6. Twig et Bootstrap

- Chaque page étend un layout : `{% extends 'parent/layout.html.twig' %}`, qui
  étend lui-même `base.html.twig`. Le contenu va dans `{% block body %}`.
- `base.html.twig` expose les blocs `title`, `body_class`, `navbar`, `logo`,
  `marque_suffixe`, `menu`, `menu_utilisateur`, `body`, `javascripts`.
- **Utiliser d'abord les classes Bootstrap** (grille, `card`, `btn`, `alert`,
  `table`, `dropdown`, `modal`, utilitaires `d-flex`, `gap-*`, `mt-*`…).
- `app.css` ne contient que la charte (palette en variables sur `:root`) et les
  composants propres au projet. Classes maison disponibles : `btn-marine`,
  `btn-or`, `btn-fantome`, `btn-supprimer`, `card-enfant`, `carte-titre`,
  `encadre`, `pastille`, `stat-libelle`, `stat-valeur`, `gros-chiffre`, `jauge`
  + `niveau-{vert|orange|rouge}`, `profil-ligne`, `avatar-bulle`, `texte-doux`.
- Formulaires : thème `bootstrap_5_layout.html.twig` (config/packages/twig.yaml).
  Toujours `{{ form_errors(form) }}` juste après `form_start()`.
- JavaScript : vanilla, dans le bloc `javascripts` de la page qui l'utilise.
  Un fichier dans `public/js/` seulement s'il sert à plusieurs pages.
  Insérer du texte avec `textContent`, jamais une donnée dans `innerHTML`.
- Données PHP vers JS : `{{ variable|json_encode|raw }}` (tableaux de nombres
  ou constantes, jamais une saisie brute d'utilisateur).
- **Préserver le design** : couleurs, polices (Baloo 2 pour les titres, Nunito
  pour le texte), boutons en pilule, cartes arrondies à ombre douce.

## 7. Doctrine, base de données et migrations

- Toute modification du schéma passe par une **migration** :
  `make:entity` → `make:migration` → relire le fichier → `doctrine:migrations:migrate`.
  Ne jamais modifier la base à la main, ni une migration déjà partagée.
- Vérifier ensuite `doctrine:schema:validate`.
- Types de colonnes explicites pour les dates : `date_immutable` pour un jour.
- Tri dans les requêtes : `->orderBy('e.prenom')` (croissant par défaut) ou
  `\SortDirection::Descending`. Passer `'ASC'`/`'DESC'` en chaîne est **déprécié**,
  y compris dans l'attribut `#[ORM\OrderBy]` (ne pas l'utiliser).
- Les données de démonstration vont dans `AppFixtures` ; les données métier
  (textes des conseils, contenus) sont en base, pas dans le code ni les gabarits.

## 8. Sécurité

- Authentification **uniquement** par `form_login` de Symfony ; ne pas écrire
  d'authenticator maison.
- Mots de passe hachés avec `UserPasswordHasherInterface` ; jamais stockés ni
  affichés en clair, jamais générés automatiquement (le parent les choisit).
- Accès par rôle dans `access_control` (security.yaml). Accès à **un enfant
  précis** : `$this->denyAccessUnlessGranted(EnfantVoter::GERER, $enfant)`.
- Toute action qui modifie des données est en **POST** et protégée par CSRF :
  formulaires Symfony (automatique) ou `isCsrfTokenValid()` + partiel
  `_partials/bouton_supprimer.html.twig`.
- CSRF **adossé à la session** (config/packages/csrf.yaml) : ne pas revenir à la
  variante « stateless » par défaut.
- Les données venant du navigateur hors formulaire Symfony (JSON du schéma
  corporel) sont **revalidées** côté serveur.
- Un identifiant d'URL qui désigne un objet d'un autre parent → 403 (voter) ;
  sur le tableau de bord, un `?enfant=` étranger retombe sur le premier enfant.

## 9. Validation

- Contraintes `#[Assert\…]` sur l'entité pour les règles permanentes (âge 8-14 ans,
  limite 15-480 min par pas de 15, titre obligatoire…).
- Contraintes dans le `*Type` pour les champs non mappés (`plainPassword`,
  `motDePasse`, `conditions`) ou propres à un formulaire (email obligatoire).
- Messages en français, rédigés pour l'utilisateur (tutoiement côté enfant).
- Un curseur (`RangeType`) envoie une chaîne : pour un champ mappé sur un `int`,
  ajouter un `CallbackTransformer` (voir `EnfantType`).
- Règle portant sur **plusieurs champs à la fois** (total des curseurs d'écran) :
  `Assert\Callback` dans l'option `constraints` du formulaire (voir
  `JournalEcransType`). L'erreur s'affiche avec `form_errors(form)`.
- Formulaire lié à l'utilisateur connecté (profil parent) : après une saisie
  invalide, `$entityManager->refresh($user)`, sinon il est déconnecté.

## 10. Tests

- Chaque fonctionnalité ou correction importante est couverte par un test dans
  `tests/` (`WebTestCase` pour un parcours, `KernelTestCase` pour un service,
  `TestCase` pour une classe pure).
- La base de test (`digisante_junior_test`) contient les fixtures ; DAMA annule
  chaque test. Un test peut donc supprimer ou créer des données librement.
- Préférer `$client->loginUser()` pour se connecter, sauf pour tester la connexion.
- Avant de terminer une tâche :

```bash
make lint      # lint:twig, lint:yaml, lint:container, doctrine:schema:validate
make tests     # PHPUnit (échoue aussi sur les dépréciations)
```

  puis exercer la fonctionnalité dans le navigateur ou en HTTP réel.

## 11. Pièges connus

| Piège | À retenir |
|---|---|
| `--bs-body-bg` | Ne pas la redéfinir : Bootstrap l'utilise pour le fond des cartes, champs, tableaux et menus. Le fond de page est posé sur `body`. |
| Jour courant | PHP est en `Europe/Paris` ; MySQL aussi (variable `TZ` dans compose.yaml). Sans cela, `CURDATE()` et `new DateTimeImmutable('today')` ne désignent pas le même jour autour de minuit. |
| `_failure_path` | Doit être un **chemin** (`path('app_enfant_login')`), pas un nom de route. |
| Champs en trop | Un formulaire Symfony refuse les champs inconnus (« ne doit pas contenir de champs supplémentaires ») : le nom des champs HTML doit correspondre au `*Type`. |
| Galerie d'avatars | Le thème Bootstrap entoure chaque radio d'un `div.form-check` : `form/avatars.html.twig` écrit les `<input>` lui-même puis appelle `setRendered`. |
| `RangeType` sans valeur | Le navigateur place le curseur **au milieu** de la plage, pas à zéro. Donner une valeur de départ (option `data` de `JournalEcransType`). |
| Recettes Flex | `extra.symfony.docker` vaut `false` dans composer.json pour qu'un `composer require` n'écrase pas `compose.yaml`. |
| Droits MySQL des tests | `docker/mysql/init.sql` ne s'exécute qu'à la création du volume. |

---

## 12. Development History / Prompt History

Décisions importantes uniquement, de la plus ancienne à la plus récente.
Ajouter une ligne à chaque changement d'architecture ou choix structurant.

| Date | Décision | Raison |
|---|---|---|
| 2026-09-14 | Écriture from scratch du projet, avec le moins de classes possible. | Obtenir une base simple et maintenable par un débutant. |
| 2026-09-14 | Symfony 7.4 LTS + PHP 8.4 + MySQL 8, Docker (FrankenPHP + MySQL) sur les ports 8081/3308. | Socle stable et à jour ; aucun outil à installer sur la machine. |
| 2026-09-14 | Tailwind remplacé par **Bootstrap 5.3 (CDN)** + un `app.css` de charte ; pas de build front. | Demande explicite ; composants prêts à l'emploi (navbar, dropdown, modal) qui remplacent le JS maison. |
| 2026-09-14 | Enums PHP → constantes d'entité ; `AvatarProvider`, `CompteEnfantManager`, `LoginSuccessHandler`, DTO de recommandation et `DataTransformer` supprimés. | Moins de classes : la logique tient dans l'entité, le contrôleur ou une cascade Doctrine ; la redirection par rôle est faite par `HomeController`. |
| 2026-09-14 | Un seul service métier, `ConseilService`, qui renvoie des tableaux simples. | Code partagé par l'espace enfant et le tableau de bord parent. |
| 2026-09-14 | Journal : étape 1 gardée en session sous forme de tableau (plus d'entité détachée). | Plus simple à lire et à sérialiser ; rien n'est écrit en base avant la fin. |
| 2026-09-14 | Page « Modifier un enfant » : la limite d'écran rejoint le formulaire du profil (2 formulaires au lieu de 3). | Même formulaire à la création et à la modification, un seul partiel Twig. |
| 2026-09-14 | Compte enfant obligatoire (`Enfant::compte` non nullable). | Chaque profil est créé avec son compte : plus de cas « profil sans compte » à gérer. |
| 2026-09-14 | Ajout de tests PHPUnit (DAMA) et d'un fuseau `Europe/Paris` pour MySQL. | Couvrir les parcours principaux ; éviter le décalage de jour entre PHP et MySQL. |
| 2026-09-15 | `ContenuBienEtre::DECLENCHEURS` réduit aux 3 règles réellement appliquées ; conseil 20-20-20 dès 2 h (≥) ; total du journal plafonné à 16 h. | Ne plus proposer des règles sans effet ; aligner le conseil sur le passage de la jauge à l'orange ; empêcher un total impossible (6 curseurs × 6 h). |
| 2026-09-15 | Section « Enfants » retirée de l'administration (`Admin\EnfantController`, ses gabarits, `findAllAvecParent()` et `findDerniers()`). | Les profils enfants relèvent de leur parent ; l'admin les voit encore, en lecture seule, sur la fiche du parent. |
