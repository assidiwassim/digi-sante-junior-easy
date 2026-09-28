# Prompt Claude Code — Phase 05 : Espace parent : profils enfants

> Copiez tout ce qui suit dans Claude Code, à la racine du projet.

---

## Contexte du projet

**Digi-Santé Junior** : application Symfony de suivi du bien-être numérique des
enfants de 8 à 14 ans. Trois rôles sans hiérarchie : `ROLE_ADMIN`,
`ROLE_PARENT`, `ROLE_CHILD`.

Déjà en place : les cinq entités et leurs relations (phase 02, dont `User` et
`Enfant`), gabarit `base.html.twig` et charte Bootstrap (phase 03), inscription
et connexion par email, `access_control` sur `/admin`, `/parent`, `/enfant`
(phase 04). Tous les paquets sont installés depuis la phase 01.

Stack : PHP 8.4, Symfony 7.4, Doctrine ORM 3, Twig, Bootstrap 5.3 par CDN,
MySQL 8, Docker. Code simple, en français, sans sur-ingénierie.

## Objectif de la phase

Permettre au parent connecté de **créer, modifier et supprimer les profils de
ses enfants**. Chaque profil est créé avec **son compte de connexion**, dont le
parent choisit le mot de passe.

## Avant de coder

1. Lis `src/Entity/User.php`, `src/Entity/Enfant.php`,
   `src/Repository/UserRepository.php`, `config/packages/security.yaml`, `templates/base.html.twig` et
   `public/css/app.css` (classes maison disponibles).
2. Repère les conventions déjà utilisées (noms de routes, injection dans
   l'action, messages flash) et suis-les.
3. Annonce-moi le plan avant de créer les fichiers.

## À implémenter

### 1. Entité `Enfant` (déjà en place)

L'entité existe depuis la phase 02 : lis-la, ne la recrée pas et ne génère
**aucune migration** dans cette phase. Rappels utiles pour la suite :

- `parent` (`ManyToOne` vers `User`, `onDelete: 'CASCADE'`) et `compte`
  (`OneToOne` **non nullable**, `cascade: ['persist', 'remove']`) : persister
  l'enfant persiste aussi son compte ; le supprimer supprime le compte et, via
  `journalEntrees` (`cascade: ['remove']`), ses journaux.
- Côté `User` : `enfants` (`cascade: ['remove']`) et `profilEnfant`.
- Constantes : `Enfant::AVATARS` (12 avatars : `emoji`, `nom`, `couleur`),
  `LIMITE_MIN`, `LIMITE_MAX`, `LIMITE_PAS`, `LIMITE_DEFAUT`, `AGE_MIN`,
  `AGE_MAX` ; getters `getNomComplet()`, `getAge()`, `getAvatarEmoji()`,
  `getAvatarNom()`, `getAvatarCouleur()`.
- Les contraintes (8-14 ans, limite 15-480 min par pas de 15) et leurs messages
  (« L'application est réservée aux enfants de 8 à 14 ans. », « La limite se
  règle par tranches de 15 minutes. ») sont déjà sur l'entité : les formulaires
  de cette phase les déclenchent.

Si une propriété ou une constante citée ici manque, signale-le avant de coder.

### 2. Identifiant de connexion de l'enfant

Dans `UserRepository`, une méthode `genererUsername(string $prenom): string` :

- le prénom en minuscules, sans accents ni caractères spéciaux (`Léa` → `lea`),
  40 caractères maximum ;
- si l'identifiant est pris, ajouter un numéro : `lea2`, `lea3`… ;
- si le prénom ne donne rien d'exploitable : `enfant`.

### 3. Contrôleur `Parent\EnfantController` (préfixe `/parent/enfants`)

- `parent_enfants` : la liste des enfants **du parent connecté**, sous forme de
  cartes (avatar, nom complet, âge, identifiant, limite, actions).
- `parent_enfant_nouveau` : crée le profil **et** son compte `User`
  (`ROLE_CHILD`, username généré, mot de passe choisi par le parent et haché).
  Le message flash affiche l'identifiant attribué — il ne sera plus montré
  ensuite.
- `parent_enfant_modifier` : deux formulaires distincts sur la même page, le
  profil (avec la limite) et le changement de mot de passe de l'enfant.
- `parent_enfant_supprimer` : **POST uniquement**, jeton CSRF vérifié, puis
  suppression (le compte et les journaux partent en cascade).
  Jeton invalide ou absent → **403** avec
  `throw $this->createAccessDeniedException('Jeton CSRF invalide.')` (même règle
  pour toutes les suppressions du projet).

Ajoute `requirements: ['id' => '\d+']` sur chaque paramètre `{id}`.

### 4. Sécurité : `EnfantVoter`

Un voter généré par `make:voter EnfantVoter` (fichier
`src/Security/Voter/EnfantVoter.php` ; remplace les attributs d'exemple
`POST_EDIT`/`POST_VIEW` du squelette) avec l'attribut `ENFANT_GERER` : un parent ne peut consulter, modifier
ou supprimer **que ses propres enfants**. Chaque action ciblant un enfant
appelle `$this->denyAccessUnlessGranted(EnfantVoter::GERER, $enfant)` →
**403** pour l'enfant d'un autre foyer.

### 5. Formulaires

- `EnfantType` : prénom, nom, date de naissance (`DateType` `single_text` avec
  `min`/`max` calculés pour les 8-14 ans), avatar (`ChoiceType` `expanded`),
  limite (`RangeType`), et — **option `creation` uniquement** — le mot de passe
  du compte (champ non mappé, 6 caractères minimum).
  ⚠️ Un `RangeType` envoie une **chaîne** : ajoute un `CallbackTransformer` pour
  le champ entier `maxMinutesJour`.
- `MotDePasseType` : un champ `plainPassword` (`RepeatedType`, non mappé,
  même nom que dans `InscriptionType`), 6 caractères minimum, réutilisable
  ailleurs (espace enfant, phase 06).
- Un partiel `templates/form/avatars.html.twig` pour la galerie.
  ⚠️ Le thème Bootstrap entoure chaque radio d'un `div.form-check` : écris les
  `<input>` toi-même dans le partiel, puis appelle `setRendered`.

### 6. Affichage des durées

Crée une extension Twig `DureeExtension` fournissant le filtre `duree` :
`45` → « 45 min », `120` → « 2 h », `150` → « 2 h 30 ». Utilise-le partout où
une durée est affichée.

### 7. Gabarits

- `templates/parent/layout.html.twig` (étend `base.html.twig`) avec le menu
  **Tableau de bord** / **Mes enfants** et le menu utilisateur (initiale +
  début de l'email, lien « Mon profil », déconnexion).
- Pages liste / nouveau / modifier, avec **un seul partiel** de formulaire
  réutilisé à la création et à la modification.
- Un partiel `_partials/bouton_supprimer.html.twig` : petit formulaire POST avec
  jeton CSRF et `confirm()` du navigateur.

### 8. Profil du parent

Le menu mène à « Mon profil » : crée la page, sinon le lien pointe vers une
route inexistante (erreur 500 sur toutes les pages parent).

- `Parent\ProfilController`, route `/parent/profil` (GET + POST), nom
  `parent_profil`.
- Deux formulaires sur la page : `ProfilParentType` (email **obligatoire**,
  pays, ville) et `MotDePasseType` pour changer son mot de passe (haché).
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
docker compose exec app php bin/console make:voter EnfantVoter
docker compose exec app php bin/console doctrine:schema:validate
```

## Ce qui n'est PAS dans cette phase

- Pas de création d'entité ni de migration (tout le schéma date de la phase 02).
- Pas de connexion enfant ni d'espace `/enfant` (phase 06).
- Pas de journal, pas de douleurs (phase 07).
- Pas de tableau de bord ni de graphique (phase 10).
- Pas d'administration des comptes (phase 11).

## Scénario de test manuel

1. Connecté en parent, ouvrir `/parent/enfants`, puis « Ajouter un enfant ».
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
