# Prompt Claude Code — Phase 08 : Bibliothèque de contenus (admin + enfant)

> Copiez tout ce qui suit dans Claude Code, à la racine du projet.
>
> 📸 **Joignez aussi les 4 captures** listées dans la section « Captures
> d'écran de référence » : elles sont dans le dossier [`captures/`](../captures/).
> Glissez chaque fichier dans la fenêtre de Claude Code (ou copiez l'image puis
> collez-la avec Ctrl+V) avant d'envoyer le prompt.

---

## Contexte du projet

**Digi-Santé Junior** : application Symfony de suivi du bien-être numérique des
enfants de 8 à 14 ans. Trois rôles sans hiérarchie : `ROLE_ADMIN`,
`ROLE_PARENT`, `ROLE_CHILD`.

Déjà en place : les cinq entités depuis la phase 02 (dont `WellnessContent`),
comptes et connexion, espace parent (profils enfants), espace
enfant (accueil, profil, journal quotidien en 2 étapes avec temps d'écran et
douleurs).

Stack : PHP 8.4, Symfony 7.4, Doctrine ORM 3, Twig, Bootstrap 5.3 par CDN,
MySQL 8, Docker. Code simple.

**Langue du projet** : tout le **code est en anglais** — classes, méthodes,
propriétés, variables, routes et URLs, tables et colonnes, classes CSS,
fonctions JavaScript et **commentaires** (ex. `Child`, `JournalEntry`,
`getTotalScreenTime()`, `/parent/children`, `child_home`). Tout ce que voit
l'utilisateur reste en **français** : libellés, boutons, messages flash,
messages de validation, titres de pages, contenus.

## Objectif de la phase

Ouvrir l'**espace d'administration** avec un CRUD complet sur les contenus
pédagogiques (fiches, vidéos, quiz, glossaire, exercices), et afficher ces
contenus à l'enfant dans une page « Découvrir ».

Règle du projet : **les données métier vivent en base**, pas dans le code ni
dans les gabarits. L'administrateur doit pouvoir corriger un texte sans
développeur.

## Avant de coder

1. Lis `src/Entity/WellnessContent.php`, `config/packages/security.yaml` (l'`access_control` sur `/admin` existe
   déjà), `templates/base.html.twig`, `templates/child/layout.html.twig` et
   `public/css/app.css`.
2. Regarde comment l'espace parent est structuré (layout, liste, formulaire,
   suppression) : l'espace admin doit suivre **les mêmes conventions**.
3. Annonce-moi les fichiers que tu vas créer avant de les créer.

## Captures d'écran de référence

Je joins à ce prompt des captures de l'application terminée, qui montrent le
rendu attendu pour cette phase :

- `08-admin-contenus-liste.png`
- `08-admin-contenu-nouveau.png`
- `08-admin-contenu-nouveau-erreurs.png`
- `08-enfant-bibliotheque.png`

Reproduis la mise en page, les textes, les emojis et les couleurs visibles, avec
les classes Bootstrap et la charte de `public/css/app.css`. Les captures ne
remplacent pas ce prompt : en cas de doute, le texte du prompt fait foi.

L'entrée de menu « 👨‍👩‍👧 Parents » visible dans l'administration arrive en phase
11 : ne l'ajoute pas maintenant.

## À implémenter

### 1. Entité `WellnessContent` (déjà en place)

L'entité existe depuis la phase 02 : lis-la, ne la recrée pas et ne génère
**aucune migration** dans cette phase. Rappels utiles pour la suite :

- Propriétés : `type` (défaut `sheet`), `title` (160), `body` (`text`,
  retours à la ligne à conserver à l'affichage), `url` (facultative, 500),
  `triggerRule` (facultatif, 40), `createdAt` (rempli dans le constructeur).
- Constantes : `TYPES` (`sheet` 📄, `video` 🎬, `quiz` ❓, `glossary` 📚,
  `exercise` 🤸) et `TRIGGERS`, limité aux **trois** règles que le moteur de
  conseils de la phase 09 appliquera réellement — `20-20-20`,
  `neck_stretching`, `eye_yoga` (aussi déclarées en constantes nommées).
  ⚠️ N'en ajoute **aucune** autre : une règle proposée mais jamais déclenchée
  rendrait invisibles les contenus qui lui sont rattachés.
- Getters d'affichage : `getTypeLabel()`, `getTypeEmoji()`, `getTriggerRuleLabel()`.
- Les contraintes sont déjà sur l'entité : « Le titre est obligatoire. »,
  « Merci de saisir une URL valide. », et sur `url`
  `#[Assert\Url(message: …, requireTld: true, tldMessage: …)]`
  (`requireTld: true` est obligatoire : l'omettre est déprécié depuis
  Symfony 7.1, et sans lui `tldMessage` n'est jamais utilisé). Le formulaire de
  cette phase les déclenche ; vérifie que la barre de debug ne signale aucune
  dépréciation.

Si une propriété ou une constante citée ici manque, signale-le avant de coder.

### 2. Espace d'administration

- `templates/admin/layout.html.twig` (étend `base.html.twig`) : même charte que
  l'espace parent, logo 🛠️, suffixe de marque « Admin », menu **📚 Contenus**,
  et le menu utilisateur (initiale de l'email, déconnexion).
- `Admin\ContentController`, préfixe `/admin` :
  - `admin_home` : `/admin` redirige vers la liste des contenus. Cette
    action **remplace** la page d'attente de la phase 04 : supprime
    `Admin\HomeController` et son gabarit (sinon deux routes portent le
    même nom) ;
  - `admin_contents` : tableau (type, titre avec 🔗 si un lien est renseigné,
    règle déclenchée, date d'ajout, actions), nombre total, tri par type puis
    par titre ;
  - `admin_content_new` et `admin_content_edit` : le **même** gabarit de
    formulaire, avec un titre et un libellé de bouton passés en variables ;
  - `admin_content_delete` : **POST**, jeton CSRF vérifié, confirmation
    navigateur via le partiel `_partials/delete_button.html.twig`.
- `requirements: ['id' => '\d+']` sur les paramètres `{id}`.
- Messages flash après chaque action : « Le contenu « [titre] » a été ajouté. »,
  « Le contenu « [titre] » a été mis à jour. », « Le contenu « [titre] » a été
  supprimé. »

### 3. Formulaire `WellnessContentType`

- `type` : `ChoiceType` construit à partir de `WellnessContent::TYPES`
  (« 📄 Fiche », « 🎬 Vidéo »…).
- `triggerRule` : `ChoiceType` non requis, construit depuis `TRIGGERS`, avec
  un `placeholder` « Aucune — visible seulement dans la bibliothèque » et une
  aide expliquant que **le premier contenu d'une règle** est celui proposé à
  l'enfant quand elle se déclenche.
- `title` (`TextType`), `body` (`TextareaType`, 8 lignes),
  `url` (`UrlType`, `default_protocol: 'https'`, facultatif, avec une aide).

### 4. Page « Découvrir » côté enfant

- Route `/child/library` (nom `child_library`) dans le contrôleur
  d'accueil enfant existant.
- Contenus **groupés par type**, dans l'ordre de la constante `TYPES`, chaque
  groupe affichant son emoji, son libellé (suivi d'un « s » s'il contient plusieurs contenus) et le nombre de
  contenus. Seuls les types non vides apparaissent.
- Pour chaque contenu : titre, pastille de la règle associée s'il y en a une,
  texte (retours à la ligne conservés) et bouton « ▶️ Ouvrir le lien » quand une
  URL existe — ouverture dans un nouvel onglet avec `rel="noopener"`.
- Message dédié si la bibliothèque est vide.
- Ajoute l'entrée **📚 Découvrir** au menu du layout enfant.

### 5. Repository

Dans `WellnessContentRepository` : `findAllSorted()` (par type, dans l'ordre
alphabétique de son **libellé affiché** — Exercice, Fiche, Glossaire, Quiz,
Vidéo —, puis par titre ; le code anglais du type ne donnerait pas cet ordre) et
`findGroupedByType()` (tableau `type => contenus`, triés par titre).
⚠️ Le tri se fait avec `->orderBy('c.title')` ou `\SortDirection::Ascending` :
passer `'ASC'`/`'DESC'` en chaîne est **déprécié**.

## Contraintes techniques et architecturales

- Toutes les requêtes dans les repositories, aucune dans un contrôleur.
- Contrôleurs simples ; injection des dépendances en argument de l'action.
- Toute action qui modifie des données est en **POST** + CSRF.
- Réutilise le partiel de suppression et les classes maison existantes.
- Aucune logique métier dans Twig.

## Commandes attendues

```bash
docker compose exec app php bin/console doctrine:schema:validate
docker compose exec app php bin/console debug:router | grep admin
docker compose exec app php bin/console lint:twig templates
```

Si aucun compte administrateur n'existe encore :

```bash
docker compose exec app php bin/console dbal:run-sql "UPDATE users SET roles = '[\"ROLE_ADMIN\"]' WHERE email = 'admin@digisante.local'"
```

## Ce qui n'est PAS dans cette phase

- Pas de création d'entité ni de migration (tout le schéma date de la phase 02).
- Pas de moteur de conseils : le champ `triggerRule` est seulement **enregistré**
  (phase 09).
- Pas d'administration des comptes parents (phase 11).
- Pas de gestion des profils enfants par l'admin : ils relèvent de leur parent.
- Pas de données de démonstration (phase 12).

## Scénario de test manuel

1. Se connecter avec le compte administrateur et ouvrir `/admin/contents`.
2. Créer un contenu de type « Fiche », avec un titre, un texte sur plusieurs lignes et un lien `https://exemple.fr`.
3. Dans une **fenêtre de navigation privée** (pour garder la session admin ouverte à côté), se connecter en enfant, ouvrir « 📚 Découvrir » et vérifier que le contenu apparaît dans le groupe « 📄 Fiche » (un seul contenu, donc au singulier), avec son bouton de lien.
4. Dans la fenêtre administrateur, modifier le titre, puis rafraîchir la page enfant de la fenêtre privée.
5. **Résultat attendu** : le contenu s'affiche côté enfant avec ses retours à la ligne, le titre modifié apparaît après rafraîchissement, et la suppression le fait disparaître des deux côtés.

## Critères de validation

- [ ] Un titre ou un contenu vide affiche un message d'erreur, sans erreur 500.
- [ ] Une URL invalide est refusée avec un message écrit pour l'utilisateur.
- [ ] La liste des règles proposées ne contient que les **trois** déclencheurs.
- [ ] La suppression sans jeton CSRF est refusée (403).
- [ ] Un parent ou un enfant qui ouvre `/admin/contents` reçoit **403**.
- [ ] Aucune nouvelle migration : `doctrine:schema:validate` reste au vert
      (schéma synchronisé), comme `lint:twig templates`.

## Enfin

- N'écris **aucun test automatisé** : je valide au navigateur.
- Termine en m'expliquant en quelques lignes pourquoi les textes des conseils
  sont stockés en base plutôt qu'écrits dans le code.
