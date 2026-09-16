# Prompt Claude Code — Phase 02 : Gabarit de base, charte graphique et page d'accueil

> Copiez tout ce qui suit dans Claude Code, à la racine du projet.

---

## Contexte du projet

**Digi-Santé Junior** est une application web de suivi du bien-être numérique
des enfants de 8 à 14 ans (journal quotidien du temps d'écran et des douleurs,
conseils personnalisés, suivi par les parents). Trois rôles sans hiérarchie :
administrateur, parent, enfant.

Stack : PHP 8.4, Symfony 7.4, Twig, **Bootstrap 5.3 par CDN**, MySQL 8, Docker.
**Aucun bundler, aucun Node.js, aucun npm** : les fichiers de `public/` sont
servis tels quels.

Je débute avec Symfony : je veux du code simple, en français, lisible de haut
en bas.

## Objectif de la phase

Passer d'une réponse texte à de vraies pages HTML : un gabarit commun à tout le
site, la charte graphique du projet, et une page d'accueil publique qui
présente le service.

## Avant de coder

1. Lis `src/Controller/HomeController.php`, `composer.json` et la structure des
   dossiers pour voir ce qui existe déjà.
2. Vérifie si Twig est installé ; s'il ne l'est pas, installe-le.
3. Ne modifie pas la configuration Docker de la phase 01.

## À implémenter

### 1. Twig et les gabarits

Installer Twig et le composant Asset, puis créer `templates/base.html.twig`,
gabarit dont **toutes** les pages hériteront. Il doit exposer ces blocs, et
seulement ceux-là :

`title`, `body_class`, `navbar`, `logo`, `marque_suffixe`, `menu`,
`menu_utilisateur`, `body`, `javascripts`.

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
- les classes maison : `btn-marine`, `btn-or`, `btn-fantome`, `btn-supprimer`,
  `card-enfant`, `carte-titre`, `encadre`, `pastille`, `stat-libelle`,
  `stat-valeur`, `gros-chiffre`, `jauge` + `niveau-vert|orange|rouge`,
  `profil-ligne`, `avatar-bulle`, `texte-doux` ;
- le fond de page posé sur `body`.

⚠️ Piège à éviter : **ne redéfinis pas la variable Bootstrap `--bs-body-bg`**,
elle sert de fond aux cartes, champs, tableaux et menus. Le fond coloré de la
page se met sur `body`.

### 3. Page d'accueil publique

`templates/home/index.html.twig`, rendue par `HomeController` :

- une accroche (« Mieux vivre avec les écrans, un jour à la fois »), une
  pastille « Pour les 8 à 14 ans », un paragraphe de présentation ;
- deux boutons d'appel à l'action : **« 🚀 Je suis un enfant »** et
  **« 👨‍👩‍👧 Je suis un parent »** (pour l'instant, ils peuvent pointer vers
  `#` : les pages de connexion arrivent en phase 04) ;
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
docker compose exec app composer require twig symfony/asset
docker compose exec app php bin/console lint:twig templates
docker compose exec app php bin/console cache:clear
```

## Ce qui n'est PAS dans cette phase

- Pas de base de données, pas d'entité (phase 03).
- Pas de connexion, pas d'inscription, pas de rôle (phase 04).
- Pas de layout `parent/`, `enfant/` ou `admin/` : ils viendront avec leurs
  espaces respectifs.

## Scénario de test manuel

1. Ouvrir `http://localhost:8081`.
2. Vérifier l'affichage : polices Baloo 2 et Nunito, couleurs de la charte,
   boutons en pilule, cartes arrondies.
3. Réduire la fenêtre du navigateur à environ 400 px de large.
4. Vérifier que le menu se replie derrière le bouton hamburger et qu'aucune
   barre de défilement horizontale n'apparaît.
5. **Résultat attendu** : une page d'accueil habillée et responsive, servie par le gabarit commun.

## Critères de validation

- [ ] `lint:twig templates` affiche `[OK]`.
- [ ] La page d'accueil hérite de `base.html.twig` (aucun `<html>` en double).
- [ ] `app.css` ne contient que la charte, et ne redéfinit pas `--bs-body-bg`.
- [ ] Le rendu est correct à 400 px comme en plein écran.
- [ ] Les blocs Twig listés plus haut existent tous et sont utilisables.

## Enfin

- N'écris **aucun test automatisé** : je valide au navigateur.
- Termine par la liste des classes CSS maison que tu as créées, avec une ligne
  d'explication chacune, pour que je les réutilise dans les phases suivantes.
