# Prompt Claude Code — Phase 03 : Gabarit de base, charte graphique et page d'accueil

> Copiez tout ce qui suit dans Claude Code, à la racine du projet.
>
> 📸 **Joignez aussi les 3 captures** listées dans la section « Captures
> d'écran de référence » : elles sont dans le dossier [`captures/`](../captures/).
> Glissez chaque fichier dans la fenêtre de Claude Code (ou copiez l'image puis
> collez-la avec Ctrl+V) avant d'envoyer le prompt.

---

## Contexte du projet

**Digi-Santé Junior** est une application web de suivi du bien-être numérique
des enfants de 8 à 14 ans (journal quotidien du temps d'écran et des douleurs,
conseils personnalisés, suivi par les parents). Trois rôles sans hiérarchie :
administrateur, parent, enfant.

Déjà en place : Docker (`app`, `database`, `phpmyadmin`), squelette Symfony 7.4
avec **tous les paquets du projet** (phase 01), base MySQL et les cinq entités
`User`, `Child`, `JournalEntry`, `PainZone`, `WellnessContent` (phase 02).
`HomeController` renvoie encore une simple réponse texte.

Stack : PHP 8.4, Symfony 7.4, Twig, **Bootstrap 5.3 par CDN**, MySQL 8, Docker.
**Aucun bundler, aucun Node.js, aucun npm** : les fichiers de `public/` sont
servis tels quels.

Je débute avec Symfony : je veux du code simple, lisible de haut
en bas.

**Langue du projet** : tout le **code est en anglais** — classes, méthodes,
propriétés, variables, routes et URLs, tables et colonnes, classes CSS,
fonctions JavaScript et **commentaires** (ex. `Child`, `JournalEntry`,
`getTotalScreenTime()`, `/parent/children`, `child_home`). Tout ce que voit
l'utilisateur reste en **français** : libellés, boutons, messages flash,
messages de validation, titres de pages, contenus.

## Objectif de la phase

Passer d'une réponse texte à de vraies pages HTML : un gabarit commun à tout le
site, la charte graphique du projet, et une page d'accueil publique qui
présente le service.

## Avant de coder

1. Lis `src/Controller/HomeController.php`, `composer.json` et la structure des
   dossiers pour voir ce qui existe déjà.
2. Twig, Asset et la barre de debug sont installés depuis la phase 01 : n'installe
   aucun paquet ; s'il en manque un, signale-le moi.
3. Ne modifie pas la configuration Docker de la phase 01 ni les entités de la
   phase 02.

## Captures d'écran de référence

Je joins à ce prompt des captures de l'application terminée, qui montrent le
rendu attendu pour cette phase :

- `03-accueil.png`
- `03-accueil-mobile.png`
- `03-accueil-mobile-menu-ouvert.png`

Reproduis la mise en page, les textes, les emojis et les couleurs visibles, avec
les classes Bootstrap et la charte de `public/css/app.css`. Les captures ne
remplacent pas ce prompt : en cas de doute, le texte du prompt fait foi.

Les boutons « Je suis un enfant » / « Je suis un parent » et les liens du menu
pointent vers des pages créées aux phases 04 et 06 : garde-les avec les liens
prévus par ce prompt.

## À implémenter

### 1. Twig et les gabarits

Twig, le composant Asset, la barre de debug, le profiler et `dump()` sont déjà
installés (phase 01). La barre de debug apparaîtra dès cette première page HTML :
elle servira à chaque phase pour comprendre ce que fait Symfony.

Remplacer le `templates/base.html.twig` créé par la recette par le gabarit dont
**toutes** les pages hériteront. Il doit exposer ces blocs, et seulement
ceux-là :

`title`, `body_class`, `navbar`, `logo`, `brand_suffix`, `menu`,
`user_menu`, `body`, `javascripts`.

Le gabarit contient :

- `<html lang="fr">`, un `<meta viewport>`, le favicon ;
- les polices Google Fonts **Baloo 2** (titres) et **Nunito** (texte) ;
- Bootstrap 5.3 par CDN (CSS dans le `<head>`, bundle JS avant `</body>`) ;
- `public/css/app.css` via `asset()` ;
- une barre de navigation Bootstrap repliable (`navbar-expand-lg`) avec la
  marque « Digi-Santé Junior » ;
- l'affichage des **messages flash** (`app.flashes`), prêt pour les phases
  suivantes ;
- un pied de page discret.

### 2. Charte graphique

`public/css/app.css` contient **uniquement** la charte et les composants du
projet, pas de recopie de Bootstrap :

- la palette en variables CSS sur `:root` (bleu marine, turquoise, or, teintes
  douces) ;
- les polices : Baloo 2 pour `h1`-`h3`, Nunito pour le texte ;
- des boutons **en pilule** et des cartes **arrondies à ombre douce** ;
- les classes maison : `btn-navy`, `btn-gold`, `btn-ghost`, `btn-delete`,
  `card-child`, `card-heading`, `callout`, `chip`, `stat-label`,
  `stat-value`, `big-number`, `gauge` + `level-green|orange|red`,
  `profile-row`, `avatar-bubble`, `text-soft` ;
- le fond de page posé sur `body`.

⚠️ Piège à éviter : **ne redéfinis pas la variable Bootstrap `--bs-body-bg`**,
elle sert de fond aux cartes, champs, tableaux et menus. Le fond coloré de la
page se met sur `body`.

### 3. Page d'accueil publique

`templates/home/index.html.twig`, rendue par `HomeController` :

- une accroche (« Mieux vivre avec les écrans, un jour à la fois »), une
  pastille « Pour les 8 à 14 ans », un paragraphe de présentation ;
- deux boutons d'appel à l'action : **« 🚀 Je suis un enfant »** et
  **« 👨‍👩‍👧 Je suis un parent »** (pour l'instant, ils pointent vers `#` :
  la connexion parent arrive en phase 04, la connexion enfant en phase 06) ;
- une carte de présentation de la mascotte 🦊 ;
- trois cartes d'arguments : conseils sur mesure, suivi clair pour les parents,
  ton toujours positif.

## Contraintes techniques et architecturales

- **Utiliser d'abord les classes Bootstrap** (grille, `card`, `btn`, `alert`,
  utilitaires `d-flex`, `gap-*`, `mt-*`…). `app.css` ne sert qu'à ce que
  Bootstrap ne fait pas.
- Le contenu de chaque page va dans `{% block body %}`.
- Aucune logique métier dans Twig.
- JavaScript vanilla uniquement, et seulement s'il est nécessaire.
- Le site doit être **responsive** : lisible à 400 px de large, sans défilement
  horizontal.

## Commandes attendues

```bash
docker compose exec app php bin/console lint:twig templates
docker compose exec app php bin/console cache:clear
```

## Ce qui n'est PAS dans cette phase

- Aucune nouvelle entité, aucune migration : le modèle de données est complet
  depuis la phase 02.
- Aucune installation de paquet (tout est installé depuis la phase 01).
- Pas de connexion, pas d'inscription, pas de rôle (phase 04).
- Pas de layout `parent/`, `child/` ou `admin/` : ils viendront avec leurs
  espaces respectifs.

## Scénario de test manuel

1. Ouvrir `http://localhost:8081`.
2. Vérifier l'affichage : polices Baloo 2 et Nunito, couleurs de la charte,
   boutons en pilule, cartes arrondies.
3. Réduire la fenêtre du navigateur à environ 400 px de large.
4. Vérifier que le menu se replie derrière le bouton hamburger et qu'aucune
   barre de défilement horizontale n'apparaît.
5. **Résultat attendu** : une page d'accueil habillée et responsive, servie par le gabarit commun, et la barre de debug s'affiche en bas.

## Critères de validation

- [ ] `lint:twig templates` affiche `[OK]`.
- [ ] La page d'accueil hérite de `base.html.twig` (aucun `<html>` en double).
- [ ] `app.css` ne contient que la charte, et ne redéfinit pas `--bs-body-bg`.
- [ ] Le rendu est correct à 400 px comme en plein écran.
- [ ] Les blocs Twig listés plus haut existent tous et sont utilisables.
- [ ] La barre de debug de Symfony apparaît en bas de la page (environnement de
      développement).

## Enfin

- N'écris **aucun test automatisé** : je valide au navigateur.
- Termine par la liste des classes CSS maison que tu as créées, avec une ligne
  d'explication chacune, pour que je les réutilise dans les phases suivantes.
