# Prompt Claude Code — Phase 05 : Espace parent : profils enfants

> Copiez tout ce qui suit dans Claude Code, à la racine du projet.
>
> 📸 **Joignez aussi les 7 captures** listées dans la section « Captures
> d'écran de référence » : elles sont dans le dossier [`captures/`](../captures/).
> Glissez chaque fichier dans la fenêtre de Claude Code (ou copiez l'image puis
> collez-la avec Ctrl+V) avant d'envoyer le prompt.

---

## Contexte du projet

**Digi-Santé Junior** : application Symfony de suivi du bien-être numérique des
enfants de 8 à 14 ans. Trois rôles sans hiérarchie : `ROLE_ADMIN`,
`ROLE_PARENT`, `ROLE_CHILD`.

Déjà en place : les cinq entités et leurs relations (phase 02, dont `User` et
`Child`), gabarit `base.html.twig` et charte Bootstrap (phase 03), inscription
et connexion par email, `access_control` sur `/admin`, `/parent`, `/child`
(phase 04). Tous les paquets sont installés depuis la phase 01.

Stack : PHP 8.4, Symfony 7.4, Doctrine ORM 3, Twig, Bootstrap 5.3 par CDN,
MySQL 8, Docker. Code simple, sans sur-ingénierie.

**Langue du projet** : tout le **code est en anglais** — classes, méthodes,
propriétés, variables, routes et URLs, tables et colonnes, classes CSS,
fonctions JavaScript et **commentaires** (ex. `Child`, `JournalEntry`,
`getTotalScreenTime()`, `/parent/children`, `child_home`). Tout ce que voit
l'utilisateur reste en **français** : libellés, boutons, messages flash,
messages de validation, titres de pages, contenus.

## Objectif de la phase

Permettre au parent connecté de **créer, modifier et supprimer les profils de
ses enfants**. Chaque profil est créé avec **son compte de connexion**, dont le
parent choisit le mot de passe.

## Avant de coder

1. Lis `src/Entity/User.php`, `src/Entity/Child.php`,
   `src/Repository/UserRepository.php`, `config/packages/security.yaml`, `templates/base.html.twig` et
   `public/css/app.css` (classes maison disponibles).
2. Repère les conventions déjà utilisées (noms de routes, injection dans
   l'action, messages flash) et suis-les.
3. Annonce-moi le plan avant de créer les fichiers.

## Captures d'écran de référence

Je joins à ce prompt des captures de l'application terminée, qui montrent le
rendu attendu pour cette phase :

- `05-parent-enfants-liste.png`
- `05-parent-menu-utilisateur-ouvert.png`
- `05-parent-enfant-nouveau.png`
- `05-parent-enfant-nouveau-erreurs.png`
- `05-parent-enfant-cree.png`
- `05-parent-enfant-modifier.png`
- `05-parent-profil.png`

Reproduis la mise en page, les textes, les emojis et les couleurs visibles, avec
les classes Bootstrap et la charte de `public/css/app.css`. Les captures ne
remplacent pas ce prompt : en cas de doute, le texte du prompt fait foi.

Les captures montrent des enfants qui ont déjà des journaux (données de
démonstration de la phase 12) : chez toi, le compteur « 📔 journée(s) » affichera
0 pour l'instant.

## À implémenter

### 1. Entité `Child` (déjà en place)

L'entité existe depuis la phase 02 : lis-la, ne la recrée pas et ne génère
**aucune migration** dans cette phase. Rappels utiles pour la suite :

- `parent` (`ManyToOne` vers `User`, `onDelete: 'CASCADE'`) et `account`
  (`OneToOne` **non nullable**, `cascade: ['persist', 'remove']`) : persister
  l'enfant persiste aussi son compte ; le supprimer supprime le compte et, via
  `journalEntries` (`cascade: ['remove']`), ses journaux.
- Côté `User` : `children` (`cascade: ['remove']`) et `childProfile`.
- Constantes : `Child::AVATARS` (12 avatars : `emoji`, `name`, `color`),
  `LIMIT_MIN`, `LIMIT_MAX`, `LIMIT_STEP` ; getters `getFullName()`,
  `getAge()`, `getAvatarEmoji()`, `getAvatarName()`.
- Les contraintes (8-14 ans, limite 15-480 min par pas de 15) et leurs messages
  (« L'application est réservée aux enfants de 8 à 14 ans. », « La limite se
  règle par tranches de 15 minutes. ») sont déjà sur l'entité : les formulaires
  de cette phase les déclenchent.

Si une propriété ou une constante citée ici manque, signale-le avant de coder.

### 2. Identifiant de connexion de l'enfant

Dans `UserRepository`, une méthode `generateUsername(string $firstName): string` :

- le prénom en minuscules, sans accents ni caractères spéciaux (`Léa` → `lea`,
  `Élodie-Marie` → `elodiemarie`), 40 caractères maximum (le `AsciiSlugger` du
  composant String, avec un séparateur vide, fait le travail) ;
- si l'identifiant est pris, ajouter un numéro : `lea2`, `lea3`… ;
- si le prénom ne donne rien d'exploitable : `enfant`.

### 3. Contrôleur `Parent\ChildController` (préfixe `/parent/children`)

- `parent_children` : la liste des enfants **du parent connecté**, sous forme de
  cartes : avatar, nom complet, âge, identifiant, limite, nombre de journaux
  (« 📔 [n] journée(s) », via `child.journalEntries|length`) et actions
  **📊 Suivi** (lien vers `parent_dashboard` avec `?child=[id]`, que le tableau
  de bord exploitera en phase 10), **Modifier**, **Supprimer**.
- `parent_child_new` : crée le profil **et** son compte `User`
  (`ROLE_CHILD`, username généré, mot de passe choisi par le parent et haché),
  puis flash `success` « Le compte de [prénom] est créé. Son identifiant de
  connexion est « [identifiant] ». » et retour à la liste.
- `parent_child_edit` : deux formulaires distincts sur la même page, le
  profil (avec la limite) et le changement de mot de passe de l'enfant :
  - profil enregistré → flash « Le profil de [prénom] a été mis à jour. » et
    retour à la liste ;
  - mot de passe changé → flash « Le mot de passe de [prénom] a été modifié. »
    et retour sur la **même page de modification**.
- `parent_child_delete` : **POST uniquement**, jeton CSRF vérifié, puis
  suppression (le compte et les journaux partent en cascade) et flash « Le
  profil de [prénom] et son compte ont été supprimés. ».
  Jeton invalide ou absent → **403** avec
  `throw $this->createAccessDeniedException('Jeton CSRF invalide.')` (même règle
  pour toutes les suppressions du projet).

Ajoute `requirements: ['id' => '\d+']` sur chaque paramètre `{id}`.

### 4. Sécurité : `ChildVoter`

Un voter généré par `make:voter ChildVoter` (fichier
`src/Security/Voter/ChildVoter.php` ; remplace les attributs d'exemple
`POST_EDIT`/`POST_VIEW` du squelette) avec l'attribut `CHILD_MANAGE` : un parent ne peut consulter, modifier
ou supprimer **que ses propres enfants**. Chaque action ciblant un enfant
appelle `$this->denyAccessUnlessGranted(ChildVoter::MANAGE, $child)` →
**403** pour l'enfant d'un autre foyer.

### 5. Formulaires

- `ChildType` : prénom, nom, date de naissance (`DateType` `single_text` avec
  `min`/`max` calculés pour les 8-14 ans), avatar (`ChoiceType` `expanded`),
  limite (`RangeType`), et — **option `creation` uniquement** — le mot de passe
  du compte `password` : un `TextType` (visible, pour que le parent le relise
  avant de le noter), non mappé, avec `NotBlank` « Choisissez un mot de passe
  pour votre enfant. » et `Length(min: 6, max: 4096)` « Le mot de passe doit
  contenir au moins {{ limit }} caractères. ».
  ⚠️ Un `RangeType` envoie une **chaîne** : ajoute un `CallbackTransformer` pour
  le champ entier `dailyLimit`.
- `PasswordChangeType` : un champ `plainPassword` (`RepeatedType` de
  `PasswordType`, même nom que dans `RegistrationType`), `invalid_message`
  « Les deux mots de passe ne sont pas identiques. », `NotBlank` « Merci de
  saisir un mot de passe. », `Length(min: 6, max: 4096)` « Le mot de passe doit
  contenir au moins {{ limit }} caractères. » ; réutilisable ailleurs (espace
  enfant, phase 06).
- Un partiel `templates/form/avatars.html.twig` pour la galerie.
  ⚠️ Le thème Bootstrap entoure chaque radio d'un `div.form-check` : écris les
  `<input>` toi-même dans le partiel, puis appelle `setRendered`.

### 6. Affichage des durées

Crée une extension Twig `DurationExtension` fournissant le filtre `duration` :
`45` → « 45 min », `120` → « 2 h », `150` → « 2 h 30 ». Utilise-le partout où
une durée est affichée.

### 7. Gabarits

- `templates/parent/layout.html.twig` (étend `base.html.twig`) avec le menu
  **Tableau de bord** / **Mes enfants** et le menu utilisateur (initiale +
  début de l'email, lien « Mon profil », déconnexion).
- Pages liste / nouveau / modifier, avec **un seul partiel** de formulaire
  réutilisé à la création et à la modification.
- Un partiel `_partials/delete_button.html.twig` : petit formulaire POST avec
  jeton CSRF et `confirm()` du navigateur.

### 8. Profil du parent

Le menu mène à « Mon profil » : crée la page, sinon le lien pointe vers une
route inexistante (erreur 500 sur toutes les pages parent).

- `Parent\ProfileController`, route `/parent/profile` (GET + POST), nom
  `parent_profile`.
- Deux formulaires sur la page : `ParentProfileType` (email **obligatoire** :
  « Merci de saisir votre email. », pays, ville) et `PasswordChangeType` pour
  changer son mot de passe (haché).
- Profil enregistré → flash « Votre profil a été mis à jour. » ; mot de passe
  changé → flash « Votre mot de passe a été modifié. » ; dans les deux cas,
  retour sur `/parent/profile`.
- ⚠️ Ce formulaire modifie **l'utilisateur connecté** : après une saisie
  invalide (email vide ou déjà pris), appelle `$entityManager->refresh($user)`,
  sinon Symfony compare un utilisateur modifié à celui de la session et le
  déconnecte à la requête suivante.

## Contraintes techniques et architecturales

- Les requêtes Doctrine vont dans les **repositories** (`findByParent()` trié
  par prénom), jamais dans le contrôleur.
- Les suppressions s'appuient sur les **cascades Doctrine** : pas de classe
  « manager » de suppression.
- Pas d'enum PHP : des constantes d'entité avec des getters d'affichage.
- Avant d'ajouter une classe, demande-toi si 20 lignes dans le contrôleur ou
  l'entité ne suffisent pas.
- Toute action qui modifie des données est en **POST** et protégée par CSRF.

## Commandes attendues

```bash
docker compose exec app php bin/console make:voter ChildVoter
docker compose exec app php bin/console doctrine:schema:validate
```

## Ce qui n'est PAS dans cette phase

- Pas de création d'entité ni de migration (tout le schéma date de la phase 02).
- Pas de connexion enfant ni d'espace `/child` (phase 06).
- Pas de journal, pas de douleurs (phase 07).
- Pas de tableau de bord ni de graphique (phase 10).
- Pas d'administration des comptes (phase 11).

## Scénario de test manuel

1. Connecté en parent, ouvrir `/parent/children`, puis « Ajouter un enfant ».
2. Saisir prénom, nom, une date de naissance correspondant à 10 ans, un avatar, une limite de 1 h 30 et un mot de passe de 6 caractères minimum.
3. Valider et lire le message : il annonce l'identifiant généré (ex. « lea »).
4. Ajouter un second enfant avec une date de naissance correspondant à 4 ans.
5. **Résultat attendu** : le premier enfant apparaît dans la liste avec son identifiant et sa limite affichée « 1 h 30 » ; le second est refusé avec le message « L'application est réservée aux enfants de 8 à 14 ans. », sans erreur 500.

## Critères de validation

- [ ] Créer un enfant crée **aussi** son compte `User` avec `ROLE_CHILD`
      (vérifiable dans phpMyAdmin, table `users`).
- [ ] Deux enfants prénommés « Léa » reçoivent `lea` puis `lea2`.
- [ ] Modifier l'URL avec l'identifiant de l'enfant d'un autre parent renvoie **403**.
- [ ] Supprimer un enfant supprime son compte **et** ses données liées.
- [ ] La suppression sans jeton CSRF est refusée (**403**).
- [ ] Sur « Mon profil », un email vide affiche une erreur **sans** déconnecter le parent.
- [ ] Aucune nouvelle migration : `doctrine:schema:validate` reste au vert
      (schéma synchronisé), comme `lint:twig templates`.

## Enfin

- N'écris **aucun test automatisé** : je valide au navigateur.
- Termine en m'expliquant en quelques lignes la différence entre
  `access_control` (par URL) et un **voter** (par objet).
