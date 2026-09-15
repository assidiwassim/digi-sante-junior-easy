# Cahier des charges — Digi-Santé Junior

| | |
|---|---|
| **Projet** | Digi-Santé Junior |
| **Objet** | Application web de suivi du bien-être numérique des enfants de 8 à 14 ans |
| **Version du document** | 1.0 |
| **Date** | 15/09/2026 |
| **Statut** | Rédigé à partir de l'analyse de l'application existante |

---

## Sommaire

1. [Présentation du projet](#1-présentation-du-projet)
2. [Acteurs et rôles](#2-acteurs-et-rôles)
3. [Fonctionnalités par rôle](#3-fonctionnalités-par-rôle)
   - 3.1 [Visiteur (non connecté)](#31-visiteur-non-connecté)
   - 3.2 [Enfant](#32-enfant-role_child)
   - 3.3 [Parent](#33-parent-role_parent)
   - 3.4 [Administrateur](#34-administrateur-role_admin)
4. [Règles de gestion transverses](#4-règles-de-gestion-transverses)
5. [Modèle de données](#5-modèle-de-données)
6. [Matrice des droits d'accès](#6-matrice-des-droits-daccès)
7. [Exigences non fonctionnelles](#7-exigences-non-fonctionnelles)
8. [Contraintes techniques](#8-contraintes-techniques)
9. [Annexes](#9-annexes)

---

## 1. Présentation du projet

### 1.1 Contexte

L'usage croissant des écrans chez les enfants s'accompagne de troubles physiques
légers mais récurrents : fatigue visuelle, douleurs au cou, aux épaules, au dos
ou aux poignets. Digi-Santé Junior propose un outil simple pour aider les enfants
à **prendre conscience** de leur temps d'écran et de ses effets, et pour permettre
aux parents d'en **suivre l'évolution**.

L'application est volontairement simple, tant dans son interface que dans son
code, afin de rester accessible à un développeur débutant.

### 1.2 Objectifs

| # | Objectif |
|---|---|
| O1 | Permettre à l'enfant de déclarer chaque jour, en moins d'une minute, son temps d'écran et ses éventuelles douleurs. |
| O2 | Lui proposer immédiatement des conseils personnalisés, formulés de manière positive et non culpabilisante. |
| O3 | Donner au parent une vue claire de la situation du jour et de l'évolution sur 7 ou 30 jours. |
| O4 | Permettre au parent de fixer une limite quotidienne de temps d'écran par enfant. |
| O5 | Permettre à un administrateur de gérer les contenus pédagogiques sans intervention technique. |

### 1.3 Public cible

- **Enfants de 8 à 14 ans** (utilisateurs principaux) : interface ludique, tutoiement, emojis, gros boutons.
- **Parents** : interface sobre, orientée suivi.
- **Administrateur** de la plateforme : gestion des contenus et modération des comptes.

---

## 2. Acteurs et rôles

Les rôles sont **indépendants et sans hiérarchie** : un administrateur n'a pas
accès aux espaces parent ou enfant, et inversement.

| Acteur | Rôle technique | Espace | Mode de connexion | Création du compte |
|---|---|---|---|---|
| Visiteur | — | Pages publiques | — | — |
| Enfant | `ROLE_CHILD` | `/enfant` | Identifiant + mot de passe sur `/connexion-enfant` | Par son parent |
| Parent | `ROLE_PARENT` | `/parent` | Email + mot de passe sur `/login` | Inscription libre |
| Administrateur | `ROLE_ADMIN` | `/admin` | Email + mot de passe sur `/login` | Hors application (initialisation des données) |

Après authentification, chaque utilisateur est automatiquement redirigé vers
l'accueil de son espace.

---

## 3. Fonctionnalités par rôle

Chaque fonctionnalité porte un identifiant unique (`PUB-xx`, `ENF-xx`, `PAR-xx`,
`ADM-xx`) pour faciliter le suivi et la recette.

### 3.1 Visiteur (non connecté)

#### Vue d'ensemble

| ID | Fonctionnalité | Priorité |
|---|---|---|
| PUB-01 | Consulter la page d'accueil | Essentielle |
| PUB-02 | Créer un compte parent | Essentielle |
| PUB-03 | Se connecter en tant que parent ou administrateur | Essentielle |
| PUB-04 | Se connecter en tant qu'enfant | Essentielle |

#### PUB-01 — Consulter la page d'accueil

- Présentation du service : accroche, public visé (8-14 ans), mascotte « Digi ».
- Mise en avant des trois bénéfices : conseils sur mesure, suivi clair pour les parents, ton toujours positif.
- Deux appels à l'action : **« Je suis un enfant »** et **« Je suis un parent »**, et un lien vers l'inscription.
- Un utilisateur déjà connecté qui accède à l'accueil est redirigé vers son espace.

#### PUB-02 — Créer un compte parent

| Champ | Obligatoire | Règle |
|---|---|---|
| Adresse email | Oui | Format email valide, unique dans l'application, enregistrée en minuscules |
| Pays | Non | 80 caractères max. |
| Ville | Non | 80 caractères max. |
| Mot de passe | Oui | 6 caractères minimum, saisi deux fois (confirmation) |
| Consentement | Oui | Case « J'accepte que les données de suivi de mes enfants soient enregistrées » |

- Le compte créé reçoit le rôle Parent.
- Après succès : message de confirmation et redirection vers la page de connexion.
- Aucun email n'est envoyé (pas de validation d'adresse).

#### PUB-03 — Connexion parent / administrateur

- Formulaire email + mot de passe, protégé contre la falsification de requête (CSRF).
- Option **« Se souvenir de moi »** : session conservée 7 jours.
- En cas d'échec : message d'erreur, email pré-rempli.
- Liens vers l'inscription et vers la connexion enfant.

#### PUB-04 — Connexion enfant

- Page dédiée au ton enfantin (« Coucou, c'est toi ? »), sans barre de navigation.
- Formulaire **identifiant** + mot de passe (l'identifiant est insensible à la casse).
- En cas d'échec : message bienveillant (« Oups ! … Essaie encore. ») et retour sur cette même page.
- Lien « Je suis un parent ».

---

### 3.2 Enfant (`ROLE_CHILD`)

#### Vue d'ensemble

| ID | Fonctionnalité | Priorité |
|---|---|---|
| ENF-01 | Consulter son tableau de bord du jour | Essentielle |
| ENF-02 | Remplir le journal — étape 1 : temps d'écran | Essentielle |
| ENF-03 | Remplir le journal — étape 2 : douleurs corporelles | Essentielle |
| ENF-04 | Recevoir des conseils personnalisés | Essentielle |
| ENF-05 | Consulter l'historique de son temps d'écran (graphique) | Importante |
| ENF-06 | Explorer la bibliothèque de contenus | Importante |
| ENF-07 | Consulter son profil | Secondaire |
| ENF-08 | Changer son mot de passe | Importante |
| ENF-09 | Se déconnecter | Essentielle |

Navigation de l'espace : **🏠 Accueil**, **📔 Mon journal**, **📚 Découvrir**, et un
menu utilisateur (avatar + prénom) donnant accès au profil et à la déconnexion.

#### ENF-01 — Tableau de bord du jour

- Salutation personnalisée avec l'avatar et le prénom de l'enfant, et la date du jour en toutes lettres.
- **Jauge du temps d'écran** du jour par rapport à la limite fixée par le parent :
  - couleur selon le niveau (voir [RG-03](#rg-03--niveaux-de-temps-décran)) ;
  - message adapté : « Super, c'est raisonnable ! », « Attention, ça commence à faire beaucoup », « C'est vraiment beaucoup pour aujourd'hui ».
- **Alerte** si la limite quotidienne est dépassée, avec invitation à faire une pause.
- Bloc journal :
  - journal non rempli : invitation « Raconte-moi ta journée ! » et bouton **Remplir mon journal** ;
  - journal rempli : félicitations, rappel des douleurs signalées et bouton **Revoir mes conseils**.
- Graphique d'historique (voir ENF-05).

#### ENF-02 — Journal, étape 1 : temps d'écran

- Un **curseur par type d'écran** : Télévision, Ordinateur, Téléphone, Tablette, Console de jeux, Autre écran.
- Plage de chaque curseur : **0 à 6 h**, par pas de **15 minutes** ; tous les
  curseurs sont positionnés sur **0** à l'ouverture du formulaire.
- Total de la journée plafonné à **16 h**, tous écrans confondus (voir [RG-02](#rg-02--limite-quotidienne-décran)).
- **Total calculé en direct** pendant la saisie, avec jauge colorée et message d'encouragement.
- Indicateur de progression (étape 1 sur 2).
- Boutons **Annuler** (retour à l'accueil) et **Suivant : mon corps**.

**Règles de gestion**

- Les valeurs sont conservées **temporairement** (session) : rien n'est enregistré en base tant que l'étape 2 n'est pas validée.
- Si l'enfant revient de l'étape 2, ses valeurs sont réaffichées.
- Chaque valeur est revalidée côté serveur (0 à 360 minutes), ainsi que le total
  de la journée : au-delà de 16 h, le formulaire est refusé avec un message et
  rien n'est enregistré.
- Si le journal du jour existe déjà, l'enfant est redirigé vers ses conseils.

#### ENF-03 — Journal, étape 2 : douleurs corporelles

- **Schéma interactif du corps** (bonhomme) avec six zones cliquables : Yeux, Cou / nuque, Épaules, Dos, Poignets, Doigts / main.
- Au clic sur une zone : fenêtre de choix de l'**intensité de 1 à 5** (« 1 = un tout petit peu, 5 = très très mal »), avec possibilité d'**enlever** la douleur.
- La zone se colore selon l'intensité ; la liste « Ce que tu as signalé » se met à jour.
- L'étape est facultative : l'enfant peut terminer sans signaler de douleur.
- Boutons **Retour** (étape 1) et **Terminer mon journal**.

**Règles de gestion**

- Accès impossible sans avoir validé l'étape 1 (redirection).
- À la validation, le journal complet (écrans + douleurs) est enregistré en une seule fois.
- Les douleurs transmises par le navigateur sont **revérifiées** : toute zone inconnue ou intensité hors 1-5 est ignorée.
- **Un seul journal par enfant et par jour** (garanti en base de données).
- Le journal enregistré n'est plus modifiable.

#### ENF-04 — Conseils personnalisés

Écran affiché après la validation du journal, et accessible à nouveau depuis
l'accueil pendant toute la journée.

- Félicitations et récapitulatif : temps d'écran total et douleurs signalées.
- Liste des conseils produits par le moteur de conseils (voir [RG-01](#rg-01--moteur-de-conseils)).
- Chaque conseil peut être accompagné d'un **contenu de la bibliothèque** (type, titre, texte) et d'un bouton vers une ressource externe (vidéo…), ouverte dans un nouvel onglet.
- Boutons **Retour à l'accueil** et **Découvrir d'autres conseils** (bibliothèque).
- Sans journal du jour, l'enfant est renvoyé vers le formulaire.

#### ENF-05 — Historique du temps d'écran

- Graphique en courbe du temps d'écran quotidien (en minutes).
- Période au choix : **7 derniers jours** (par défaut) ou **30 derniers jours**.
- Ligne de référence en pointillés rouges représentant la limite quotidienne.
- Un jour sans journal est affiché à 0 minute.

#### ENF-06 — Bibliothèque de contenus

- Contenus regroupés par type, dans l'ordre : Fiches, Vidéos, Quiz, Glossaire, Exercices, avec le nombre de contenus par groupe.
- Pour chaque contenu : titre, thème associé (le cas échéant), texte, et bouton **Ouvrir le lien** si une URL est renseignée.
- Contenus triés par titre au sein de chaque groupe ; seuls les types contenant au moins un contenu sont affichés.
- Message dédié si la bibliothèque est vide.

#### ENF-07 — Profil

Carte d'identité en lecture seule : avatar, prénom, identifiant de connexion, âge,
limite d'écran quotidienne et nombre de journées remplies.

#### ENF-08 — Changement de mot de passe

- Nouveau mot de passe saisi deux fois, 6 caractères minimum.
- Conseils de sécurité adaptés à l'âge (« C'est ton secret : ne le donne à personne, sauf à tes parents ») et exemple de mot de passe facile à retenir.
- Message de confirmation après enregistrement.

#### ENF-09 — Déconnexion

Accessible depuis le menu utilisateur ; retour à la page d'accueil publique.

---

### 3.3 Parent (`ROLE_PARENT`)

#### Vue d'ensemble

| ID | Fonctionnalité | Priorité |
|---|---|---|
| PAR-01 | Consulter le tableau de bord de suivi | Essentielle |
| PAR-02 | Lister ses enfants | Essentielle |
| PAR-03 | Créer un profil enfant et son compte de connexion | Essentielle |
| PAR-04 | Modifier le profil et la limite d'écran d'un enfant | Essentielle |
| PAR-05 | Réinitialiser le mot de passe d'un enfant | Importante |
| PAR-06 | Supprimer un profil enfant | Importante |
| PAR-07 | Gérer son profil | Importante |
| PAR-08 | Changer son mot de passe | Importante |
| PAR-09 | Se déconnecter | Essentielle |

Navigation de l'espace : **Tableau de bord**, **Mes enfants**, et un menu
utilisateur (initiale + début de l'email) donnant accès au profil et à la déconnexion.

#### PAR-01 — Tableau de bord de suivi

**Sans enfant** : écran d'accueil invitant à créer un premier profil (bouton
**Ajouter mon enfant**).

**Avec au moins un enfant** :

- **Sélecteur d'enfant** (boutons avatar + prénom) ; par défaut, le premier enfant par ordre alphabétique.
- En-tête : nom complet, âge, limite quotidienne, lien **Modifier le profil**.
- **Journal d'aujourd'hui** :
  - temps d'écran total coloré selon le niveau, rappel de la limite, jauge de progression ;
  - douleurs signalées (zone et intensité sur 5) ;
  - **conseils reçus par l'enfant** (identiques à ceux affichés dans son espace), avec le contenu associé ;
  - si le journal n'est pas rempli : message « [Prénom] n'a pas encore rempli son journal aujourd'hui ».
- **Graphique** du temps d'écran sur 7 ou 30 jours, avec la ligne de limite.

**Règle de sécurité** : si l'enfant demandé dans l'adresse n'appartient pas au
parent connecté, le tableau de bord affiche simplement le premier enfant du
parent (aucune donnée d'un autre foyer n'est exposée).

#### PAR-02 — Liste des enfants

- Une carte par enfant : avatar, nom complet, âge, **identifiant de connexion**, limite quotidienne, nombre de journées remplies.
- Actions par enfant : **Suivi** (tableau de bord), **Modifier**, **Supprimer**.
- Bouton **Ajouter un enfant** ; message et bouton de création si la liste est vide.
- Tri par prénom.

#### PAR-03 — Créer un profil enfant

| Champ | Obligatoire | Règle |
|---|---|---|
| Prénom | Oui | 80 caractères max. |
| Nom | Oui | 80 caractères max. |
| Date de naissance | Oui | L'enfant doit avoir **entre 8 et 14 ans** révolus à la date du jour ; le calendrier ne propose que les dates autorisées |
| Avatar | Oui | Choix dans une galerie de 12 avatars (voir [annexe 9.2](#92-listes-de-référence)) |
| Limite quotidienne d'écran | Oui | Curseur de **15 min à 8 h**, par pas de **15 min** ; 2 h par défaut |
| Mot de passe de connexion | Oui | 6 caractères minimum, **choisi par le parent** |

**Règles de gestion**

- La création du profil entraîne **automatiquement** la création du compte de connexion de l'enfant.
- L'**identifiant** est calculé à partir du prénom (voir [RG-04](#rg-04--génération-de-lidentifiant-enfant)).
- Le message de confirmation affiche l'identifiant attribué ; le mot de passe n'est plus jamais affiché ensuite.
- Le profil est rattaché au parent connecté.

#### PAR-04 — Modifier un profil enfant

- Mêmes champs que la création, sans le mot de passe : prénom, nom, date de naissance, avatar, limite quotidienne.
- Mêmes règles de validation.
- L'identifiant de connexion n'est pas modifié.

#### PAR-05 — Réinitialiser le mot de passe d'un enfant

- Formulaire distinct sur la page de modification : nouveau mot de passe saisi deux fois, 6 caractères minimum.
- Utile lorsque l'enfant a oublié son mot de passe (aucune récupération par email n'existe).

#### PAR-06 — Supprimer un profil enfant

- Action confirmée par une boîte de dialogue.
- Supprime **définitivement** le profil, son compte de connexion, tous ses journaux et les douleurs associées.

#### PAR-07 — Gérer son profil

- Modification de l'email (obligatoire, unique), du pays et de la ville.
- En cas de saisie invalide, les erreurs sont affichées sans que le parent ne soit déconnecté.

#### PAR-08 — Changer son mot de passe

Formulaire distinct sur la page profil : nouveau mot de passe saisi deux fois, 6 caractères minimum.

#### PAR-09 — Déconnexion

Accessible depuis le menu utilisateur.

**Contrôle d'accès commun à PAR-04, PAR-05 et PAR-06** : un parent ne peut agir
que sur **ses propres enfants**. Toute tentative sur l'enfant d'un autre parent
est refusée (erreur 403).

---

### 3.4 Administrateur (`ROLE_ADMIN`)

#### Vue d'ensemble

| ID | Fonctionnalité | Priorité |
|---|---|---|
| ADM-01 | Lister les contenus de la bibliothèque | Essentielle |
| ADM-02 | Créer un contenu | Essentielle |
| ADM-03 | Modifier un contenu | Essentielle |
| ADM-04 | Supprimer un contenu | Essentielle |
| ADM-05 | Lister les comptes parents | Importante |
| ADM-06 | Consulter la fiche d'un parent | Importante |
| ADM-07 | Supprimer un compte parent | Importante |
| ADM-08 | Se déconnecter | Essentielle |

Navigation de l'espace : **📚 Contenus** (page d'arrivée), **👨‍👩‍👧 Parents**,
et un menu utilisateur donnant accès à la déconnexion.

#### ADM-01 — Liste des contenus

- Tableau : type, titre (avec indicateur 🔗 si un lien est renseigné), règle de conseil associée, date d'ajout, actions.
- Nombre total de contenus ; tri par type puis par titre.
- Bouton **Nouveau contenu**.

#### ADM-02 / ADM-03 — Créer ou modifier un contenu

| Champ | Obligatoire | Règle |
|---|---|---|
| Type | Oui | Fiche, Vidéo, Quiz, Glossaire ou Exercice |
| Règle du moteur de conseils | Non | Thème déclencheur (voir [annexe 9.2](#92-listes-de-référence)) ; vide = contenu visible uniquement dans la bibliothèque |
| Titre | Oui | 160 caractères max. |
| Contenu | Oui | Texte libre, retours à la ligne conservés à l'affichage |
| Lien | Non | URL valide (https ajouté par défaut), 500 caractères max. |

- Le **premier contenu créé** pour une règle donnée est celui proposé à l'enfant lorsque cette règle se déclenche.
- Ce mécanisme permet de modifier le texte des exercices proposés en conseil **sans intervention technique**.
- Message de confirmation après enregistrement.

#### ADM-04 — Supprimer un contenu

Suppression définitive après confirmation. Si le contenu était associé à une
règle, le conseil correspondant s'affiche sans contenu (ou avec le contenu
suivant rattaché à la même règle).

#### ADM-05 — Liste des parents

- Tableau : email, localisation (ville, pays), nombre d'enfants, date d'inscription, actions (**Voir**, **Supprimer**).
- Nombre total de comptes ; tri du plus récent au plus ancien.

#### ADM-06 — Fiche d'un parent

- Informations du compte : email, ville, pays, date d'inscription.
- Liste de ses enfants, en consultation seule (avatar, nom, âge, identifiant, limite) : les profils enfants se gèrent depuis l'espace du parent.
- Bouton de suppression.
- Un identifiant qui ne correspond pas à un compte parent renvoie une erreur 404.

#### ADM-07 — Supprimer un compte parent

- Confirmation obligatoire, avec avertissement sur le caractère définitif.
- Supprime **en cascade** : le compte parent, tous ses profils enfants, leurs comptes de connexion, leurs journaux et leurs douleurs.
- Le message de confirmation indique le nombre de profils enfants supprimés.

#### ADM-08 — Déconnexion

Accessible depuis le menu utilisateur.

---

## 4. Règles de gestion transverses

### RG-01 — Moteur de conseils

Les conseils sont calculés à partir du journal du jour. Ils sont identiques dans
l'espace enfant et dans le tableau de bord parent.

| # | Condition | Conseil | Contenu associé |
|---|---|---|---|
| R1 | Temps d'écran total **≥ 2 h** et limite non dépassée | « Repose tes yeux avec le 20-20-20 » | Règle du 20-20-20 |
| R2 | Temps d'écran total **> limite** fixée par le parent | « Tu as dépassé ta limite d'écran » (rappel du temps et de la limite) | Règle du 20-20-20 |
| R3 | Douleur au **cou** ou aux **épaules** d'intensité **≥ 3** | « Détends ton cou et tes épaules » | Étirements du cou |
| R4 | Douleur aux **yeux**, quelle que soit l'intensité | « Un peu de yoga des yeux » | Yoga des yeux |
| R5 | Aucune des règles ci-dessus | « Super journée ! » (félicitations) | — |

- R1 et R2 sont **exclusives** : un seul conseil sur les écrans est affiché ; R2 est prioritaire.
- R3 et R4 peuvent s'ajouter au conseil sur les écrans.
- Les messages sont rédigés au **tutoiement**, sur un ton encourageant.

### RG-02 — Limite quotidienne d'écran

- Fixée par le parent pour chaque enfant : de 15 à 480 minutes, par tranches de 15 minutes.
- Valeur par défaut : 120 minutes (2 h).
- Utilisée pour la jauge, l'alerte de dépassement, le graphique et la règle R2.
- Indépendamment de cette limite, un journal ne peut pas déclarer plus de **16 h**
  d'écran sur la journée : au-delà, la saisie est refusée.

### RG-03 — Niveaux de temps d'écran

| Niveau | Temps d'écran total | Couleur |
|---|---|---|
| Raisonnable | moins de 2 h | Vert |
| Élevé | de 2 h à 4 h | Orange |
| Excessif | plus de 4 h | Rouge |

### RG-04 — Génération de l'identifiant enfant

- Prénom converti en minuscules, sans accents ni caractères spéciaux (« Léa » → `lea`), 40 caractères max.
- Si l'identifiant est déjà pris, un numéro est ajouté : `lea2`, `lea3`…
- Si le prénom ne produit aucun caractère exploitable : `enfant`.

### RG-05 — Journal quotidien

- Un seul journal par enfant et par jour calendaire (fuseau Europe/Paris).
- Le journal ne peut être rempli que pour le jour en cours.
- Il n'est enregistré qu'à la fin de l'étape 2 : un journal abandonné en cours de route ne laisse aucune trace.

### RG-06 — Âge

- L'application est réservée aux enfants de **8 à 14 ans** au moment de la création ou de la modification du profil.
- L'âge affiché est calculé en années révolues à la date du jour.

### RG-07 — Suppressions en cascade

```text
Parent ──► Enfants ──► Compte de connexion de l'enfant
                  └──► Journaux ──► Douleurs
```

Toute suppression est définitive et précédée d'une confirmation.

### RG-08 — Format des durées

Les durées sont affichées sous une forme lisible : `45 min`, `2 h`, `2 h 30`.

---

## 5. Modèle de données

```text
User (compte de connexion)
 ├── parent ──< Enfant >── compte (User de l'enfant, 1-1)
 │                └──< JournalEntree (1 par jour) ──< DouleurZone
 │
ContenuBienEtre (bibliothèque, indépendante)
```

| Entité | Description | Données principales |
|---|---|---|
| **User** | Compte de connexion, commun aux trois rôles | email (parents/admin, unique), identifiant (enfants, unique), rôles, mot de passe haché, pays, ville, date de création |
| **Enfant** | Profil d'un enfant, rattaché à un parent | prénom, nom, date de naissance, avatar, limite quotidienne (min), parent, compte de connexion |
| **JournalEntree** | Journal quotidien d'un enfant | date, minutes par écran (TV, ordinateur, téléphone, tablette, console, autre) ; unicité (enfant, date) |
| **DouleurZone** | Douleur signalée dans un journal | zone du corps, intensité (1 à 5) |
| **ContenuBienEtre** | Contenu pédagogique de la bibliothèque | type, titre, texte, lien, règle déclencheuse, date de création |

---

## 6. Matrice des droits d'accès

Légende : ✅ autorisé · 🔒 limité à ses propres données · — refusé

| Fonction | Visiteur | Enfant | Parent | Admin |
|---|:---:|:---:|:---:|:---:|
| Page d'accueil publique | ✅ | redirigé | redirigé | redirigé |
| Inscription parent | ✅ | — | — | — |
| Connexion | ✅ | — | — | — |
| Journal quotidien et conseils | — | 🔒 | — | — |
| Bibliothèque (lecture) | — | ✅ | — | — |
| Profil et mot de passe enfant | — | 🔒 | — | — |
| Tableau de bord de suivi | — | — | 🔒 | — |
| Créer / modifier / supprimer un enfant | — | — | 🔒 | — |
| Profil et mot de passe parent | — | — | 🔒 | — |
| Gestion des contenus (CRUD) | — | — | — | ✅ |
| Consultation des comptes parents (et de leurs enfants) | — | — | — | ✅ |
| Suppression d'un compte parent | — | — | — | ✅ |

---

## 7. Exigences non fonctionnelles

### 7.1 Sécurité

| ID | Exigence |
|---|---|
| SEC-01 | Mots de passe **hachés** avec l'algorithme recommandé par le framework ; jamais stockés ni affichés en clair, jamais générés automatiquement. |
| SEC-02 | Accès aux espaces contrôlé par rôle : `/admin` → Admin, `/parent` → Parent, `/enfant` → Enfant. |
| SEC-03 | Un parent n'accède qu'aux données de **ses** enfants (contrôle sur chaque action ciblant un enfant). |
| SEC-04 | Toute action modifiant des données utilise la méthode **POST** et est protégée par un **jeton CSRF** adossé à la session. |
| SEC-05 | Les données envoyées par le navigateur hors formulaire standard (schéma corporel en JSON) sont **revalidées côté serveur**. |
| SEC-06 | Aucune donnée saisie n'est injectée comme HTML dans les pages (protection XSS). |
| SEC-07 | Les liens externes s'ouvrent dans un nouvel onglet avec `rel="noopener"`. |
| SEC-08 | Consentement explicite du parent à l'enregistrement des données de suivi lors de l'inscription. |

### 7.2 Ergonomie et design

- **Espace enfant** : fond coloré, gros boutons, emojis, mascotte, tutoiement, messages positifs et non culpabilisants ; parcours du journal réalisable en moins d'une minute.
- **Espaces parent et admin** : interface sobre et lisible, orientée données.
- Charte graphique : polices **Baloo 2** (titres) et **Nunito** (texte), boutons en pilule, cartes arrondies à ombre douce.
- Messages de confirmation (« flash ») après chaque action réussie.
- Confirmation systématique avant toute suppression.

### 7.3 Responsive et accessibilité

- Interface **responsive** (grille Bootstrap), menu repliable sur mobile.
- Jauges dotées d'attributs ARIA (`role="progressbar"`, valeurs min/max/courante).
- Schéma corporel doté d'un libellé accessible.
- Langue du document déclarée (`lang="fr"`).

### 7.4 Langue et localisation

- Interface, messages d'erreur et contenus **entièrement en français**.
- Dates au format français (`jj/mm/aaaa`, date longue sur l'accueil enfant).
- Fuseau horaire **Europe/Paris**, identique pour l'application et la base de données, afin que le « jour courant » soit cohérent.

### 7.5 Qualité et maintenabilité

- Architecture Symfony classique (contrôleurs, entités, formulaires, repositories), sans sur-ingénierie.
- Code, routes et commentaires en français, avec le vocabulaire métier.
- Toute évolution du schéma de données passe par une **migration**.
- **Tests automatisés** (PHPUnit) couvrant : pages publiques, connexion et inscription, contrôle d'accès par rôle, gestion des enfants et cloisonnement entre parents, parcours du journal et validation des données, règles du moteur de conseils, formatage des durées.
- Chaque test s'exécute dans une transaction annulée, sur une base de test dédiée.

---

## 8. Contraintes techniques

| Domaine | Choix |
|---|---|
| Langage | PHP 8.4 |
| Framework | Symfony 7.4 (LTS) |
| Base de données | MySQL 8 |
| ORM | Doctrine ORM 3 (entités, migrations, fixtures) |
| Gabarits | Twig |
| Interface | Bootstrap 5.3 (CDN) + feuille de style de charte |
| Graphiques | Chart.js 4 (CDN) |
| JavaScript | Vanilla, sans bundler ni Node.js |
| Tests | PHPUnit 12 + dama/doctrine-test-bundle |
| Environnement | Docker : conteneur applicatif FrankenPHP + conteneur MySQL, plus phpMyAdmin pour le développement |
| Ports | Application `8081`, phpMyAdmin `8082`, MySQL `3308` |


---

## 9. Annexes

### 9.1 Plan des routes

| Espace | Méthode | URL | Fonction |
|---|---|---|---|
| Public | GET | `/` | Accueil (ou redirection vers l'espace de l'utilisateur connecté) |
| Public | GET, POST | `/login` | Connexion parent / admin (et traitement de toutes les connexions) |
| Public | GET | `/connexion-enfant` | Connexion enfant |
| Public | GET, POST | `/inscription` | Inscription parent |
| Public | GET | `/logout` | Déconnexion |
| Enfant | GET | `/enfant` | Accueil enfant (`?periode=7\|30`) |
| Enfant | GET | `/enfant/journal` | Point d'entrée du journal |
| Enfant | GET, POST | `/enfant/journal/etape/1` | Étape 1 : écrans |
| Enfant | GET, POST | `/enfant/journal/etape/2` | Étape 2 : douleurs |
| Enfant | GET | `/enfant/journal/conseils` | Conseils du jour |
| Enfant | GET | `/enfant/bibliotheque` | Bibliothèque |
| Enfant | GET, POST | `/enfant/profil` | Profil et mot de passe |
| Parent | GET | `/parent` | Tableau de bord (`?enfant=<id>&periode=7\|30`) |
| Parent | GET | `/parent/enfants` | Liste des enfants |
| Parent | GET, POST | `/parent/enfants/nouveau` | Création d'un enfant |
| Parent | GET, POST | `/parent/enfants/{id}/modifier` | Modification et mot de passe d'un enfant |
| Parent | POST | `/parent/enfants/{id}/supprimer` | Suppression d'un enfant |
| Parent | GET, POST | `/parent/profil` | Profil et mot de passe parent |
| Admin | GET | `/admin` | Redirection vers les contenus |
| Admin | GET | `/admin/contenus` | Liste des contenus |
| Admin | GET, POST | `/admin/contenus/nouveau` | Création d'un contenu |
| Admin | GET, POST | `/admin/contenus/{id}/modifier` | Modification d'un contenu |
| Admin | POST | `/admin/contenus/{id}/supprimer` | Suppression d'un contenu |
| Admin | GET | `/admin/parents` | Liste des parents |
| Admin | GET | `/admin/parents/{id}` | Fiche parent |
| Admin | POST | `/admin/parents/{id}/supprimer` | Suppression d'un parent |

### 9.2 Listes de référence

**Types d'écrans** : 📺 Télévision · 💻 Ordinateur · 📱 Téléphone · 📲 Tablette · 🎮 Console de jeux · 🖥️ Autre écran

**Zones du corps** : 👀 Yeux · 🦴 Cou / nuque · 💪 Épaules · 🔙 Dos · 🤚 Poignets · ✋ Doigts / main

**Types de contenus** : 📄 Fiche · 🎬 Vidéo · ❓ Quiz · 📚 Glossaire · 🤸 Exercice

**Règles déclencheuses** : Règle du 20-20-20 · Étirements du cou · Yoga des yeux

**Avatars** : 🦊 Renard malin · 🐼 Panda calme · 🐱 Chat curieux · 🐶 Chien fidèle ·
🐰 Lapin rapide · 🦁 Lion courageux · 🐸 Grenouille sportive · 🐙 Poulpe créatif ·
🦄 Licorne magique · 🐲 Dragon rigolo · 🐧 Pingouin cool · 🧑‍🚀 Astronaute

### 9.3 Comptes de démonstration

| Rôle | Identifiant | Mot de passe |
|---|---|---|
| Administrateur | `admin@digisante.local` | `admin123` |
| Parent | `parent@digisante.local`, `sofia@digisante.local` | `parent123` |
| Enfant | `lea`, `tom`, `noah`, `ines` | `enfant123` |

> Comptes destinés uniquement aux environnements de développement et de recette.
