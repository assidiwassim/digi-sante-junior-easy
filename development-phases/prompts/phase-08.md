# Prompt Claude Code — Phase 08 : Bibliothèque de contenus (administration et page enfant)

> Copiez tout ce qui suit dans Claude Code, à la racine du projet.

---

## Contexte du projet

**Digi-Santé Junior** : application Symfony de suivi du bien-être numérique des
enfants de 8 à 14 ans. Trois rôles sans hiérarchie : `ROLE_ADMIN`,
`ROLE_PARENT`, `ROLE_CHILD`.

Déjà en place : comptes et connexion, espace parent (profils enfants), espace
enfant (accueil, profil, journal quotidien en 2 étapes avec temps d'écran et
douleurs).

Stack : PHP 8.4, Symfony 7.4, Doctrine ORM 3, Twig, Bootstrap 5.3 par CDN,
MySQL 8, Docker. Code simple, en français.

## Objectif de la phase

Ouvrir l'**espace d'administration** avec un CRUD complet sur les contenus
pédagogiques (fiches, vidéos, quiz, glossaire, exercices), et afficher ces
contenus à l'enfant dans une page « Découvrir ».

Règle du projet : **les données métier vivent en base**, pas dans le code ni
dans les gabarits. L'administrateur doit pouvoir corriger un texte sans
développeur.

## Avant de coder

1. Lis `config/packages/security.yaml` (l'`access_control` sur `/admin` existe
   déjà), `templates/base.html.twig`, `templates/enfant/layout.html.twig` et
   `public/css/app.css`.
2. Regarde comment l'espace parent est structuré (layout, liste, formulaire,
   suppression) : l'espace admin doit suivre **les mêmes conventions**.
3. Annonce-moi les fichiers que tu vas créer avant de les créer.

## À implémenter

### 1. Entité `ContenuBienEtre`

| Propriété | Type | Règles |
|---|---|---|
| `type` | `string(20)` | obligatoire, défaut `fiche` |
| `titre` | `string(160)` | obligatoire |
| `contenu` | `text` | obligatoire, retours à la ligne conservés à l'affichage |
| `url` | `?string(500)` | facultatif, URL valide |
| `declencheur` | `?string(40)` | facultatif, règle du moteur de conseils |
| `createdAt` | `datetime_immutable` | rempli dans le constructeur |

- **Constantes de classe**, pas d'enum :
  - `TYPES` : `fiche` 📄, `video` 🎬, `quiz` ❓, `glossaire` 📚, `exercice` 🤸,
    chacun avec son libellé et son emoji ;
  - `DECLENCHEURS` : **uniquement** les règles qui seront réellement appliquées
    par le moteur de conseils de la phase 09 — `20-20-20` (« Règle du
    20-20-20 »), `etirement_cervical` (« Étirements du cou »), `yoga_yeux`
    (« Yoga des yeux »). Déclare-les aussi en constantes nommées.
    ⚠️ N'ajoute **aucune** autre règle : une règle proposée mais jamais
    déclenchée rendrait invisibles les contenus qui lui sont rattachés.
- Getters d'affichage : `getTypeLabel()`, `getTypeEmoji()`, `getDeclencheurLabel()`.
- Messages de validation en français : « Le titre est obligatoire. », « Merci de
  saisir une URL valide. » (pense aussi au message lorsqu'il manque le domaine).

Génère la migration, fais-la-moi relire, applique-la.

### 2. Espace d'administration

- `templates/admin/layout.html.twig` (étend `base.html.twig`) : même charte que
  l'espace parent, logo 🛠️, suffixe de marque « Admin », menu **📚 Contenus**,
  et le menu utilisateur (initiale de l'email, déconnexion).
- `Admin\ContenuController`, préfixe `/admin` :
  - `admin_accueil` : `/admin` redirige vers la liste des contenus ;
  - `admin_contenus` : tableau (type, titre avec 🔗 si un lien est renseigné,
    règle déclenchée, date d'ajout, actions), nombre total, tri par type puis
    par titre ;
  - `admin_contenu_nouveau` et `admin_contenu_modifier` : le **même** gabarit de
    formulaire, avec un titre et un libellé de bouton passés en variables ;
  - `admin_contenu_supprimer` : **POST**, jeton CSRF vérifié, confirmation
    navigateur via le partiel `_partials/bouton_supprimer.html.twig`.
- `requirements: ['id' => '\d+']` sur les paramètres `{id}`.
- Messages flash après chaque action : « Le contenu « … » a été ajouté. »

### 3. Formulaire `ContenuBienEtreType`

- `type` : `ChoiceType` construit à partir de `ContenuBienEtre::TYPES`
  (« 📄 Fiche », « 🎬 Vidéo »…).
- `declencheur` : `ChoiceType` non requis, construit depuis `DECLENCHEURS`, avec
  un `placeholder` « Aucune — visible seulement dans la bibliothèque » et une
  aide expliquant que **le premier contenu d'une règle** est celui proposé à
  l'enfant quand elle se déclenche.
- `titre` (`TextType`), `contenu` (`TextareaType`, 8 lignes),
  `url` (`UrlType`, `default_protocol: 'https'`, facultatif, avec une aide).

### 4. Page « Découvrir » côté enfant

- Route `/enfant/bibliotheque` (nom `enfant_bibliotheque`) dans le contrôleur
  d'accueil enfant existant.
- Contenus **groupés par type**, dans l'ordre de la constante `TYPES`, chaque
  groupe affichant son emoji, son libellé (au pluriel si besoin) et le nombre de
  contenus. Seuls les types non vides apparaissent.
- Pour chaque contenu : titre, pastille de la règle associée s'il y en a une,
  texte (retours à la ligne conservés) et bouton « ▶️ Ouvrir le lien » quand une
  URL existe — ouverture dans un nouvel onglet avec `rel="noopener"`.
- Message dédié si la bibliothèque est vide.
- Ajoute l'entrée **📚 Découvrir** au menu du layout enfant.

### 5. Repository

Dans `ContenuBienEtreRepository` : `findTousTries()` (type puis titre) et
`findGroupesParType()` (tableau `type => contenus`, triés par titre).
⚠️ Le tri se fait avec `->orderBy('c.titre')` ou `\SortDirection::Ascending` :
passer `'ASC'`/`'DESC'` en chaîne est **déprécié**.

## Contraintes techniques et architecturales

- Toutes les requêtes dans les repositories, aucune dans un contrôleur.
- Contrôleurs simples ; injection des dépendances en argument de l'action.
- Toute action qui modifie des données est en **POST** + CSRF.
- Réutilise le partiel de suppression et les classes maison existantes.
- Aucune logique métier dans Twig.

## Commandes attendues

```bash
docker compose exec app php bin/console make:entity ContenuBienEtre
docker compose exec app php bin/console make:migration
docker compose exec app php bin/console doctrine:migrations:migrate --no-interaction
docker compose exec app php bin/console debug:router | grep admin
docker compose exec app php bin/console lint:twig templates
```

Si aucun compte administrateur n'existe encore :

```bash
docker compose exec app php bin/console dbal:run-sql "UPDATE users SET roles = '[\"ROLE_ADMIN\"]' WHERE email = 'admin@digisante.local'"
```

## Ce qui n'est PAS dans cette phase

- Pas de moteur de conseils : le champ `declencheur` est seulement **enregistré**
  (phase 09).
- Pas d'administration des comptes parents (phase 11).
- Pas de gestion des profils enfants par l'admin : ils relèvent de leur parent.
- Pas de données de démonstration (phase 12).

## Scénario de test manuel

1. Se connecter avec le compte administrateur et ouvrir `/admin/contenus`.
2. Créer un contenu de type « Fiche », avec un titre, un texte sur plusieurs lignes et un lien `https://exemple.fr`.
3. Se déconnecter, se connecter en enfant, ouvrir « 📚 Découvrir » et vérifier que le contenu apparaît dans le groupe « Fiches », avec son bouton de lien.
4. Revenir en administrateur, modifier le titre, puis rafraîchir la page enfant.
5. **Résultat attendu** : le contenu s'affiche côté enfant avec ses retours à la ligne, le titre modifié apparaît après rafraîchissement, et la suppression le fait disparaître des deux côtés.

## Critères de validation

- [ ] Un titre ou un contenu vide affiche un message d'erreur, sans erreur 500.
- [ ] Une URL invalide est refusée avec un message écrit pour l'utilisateur.
- [ ] La liste des règles proposées ne contient que les **trois** déclencheurs.
- [ ] La suppression sans jeton CSRF est refusée (403).
- [ ] Un parent ou un enfant qui ouvre `/admin/contenus` reçoit **403**.
- [ ] `lint:twig templates` et `doctrine:schema:validate` sont au vert.

## Enfin

- N'écris **aucun test automatisé** : je valide au navigateur.
- Termine en m'expliquant en quelques lignes pourquoi les textes des conseils
  sont stockés en base plutôt qu'écrits dans le code.
