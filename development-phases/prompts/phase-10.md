# Prompt Claude Code — Phase 10 : Tableau de bord parent et graphiques

> Copiez tout ce qui suit dans Claude Code, à la racine du projet.

---

## Contexte du projet

**Digi-Santé Junior** : application Symfony de suivi du bien-être numérique des
enfants de 8 à 14 ans.

Déjà en place : comptes et rôles, espace parent (profils enfants, limite
quotidienne d'écran, `EnfantVoter`), espace enfant (accueil, journal quotidien
en 2 étapes, bibliothèque), moteur de conseils `ConseilService` et filtre Twig
`duree`.

Stack : PHP 8.4, Symfony 7.4, Doctrine ORM 3, Twig, Bootstrap 5.3 par CDN,
**Chart.js par CDN**, MySQL 8, Docker. Pas de bundler, pas de npm.

## Objectif de la phase

Donner au parent un vrai **tableau de bord** : la journée en cours de l'enfant
choisi, les conseils qu'il a reçus, et la courbe de son temps d'écran sur 7 ou
30 jours. Le même graphique est ajouté sur l'accueil de l'enfant.

## Avant de coder

1. Lis `src/Controller/Parent/`, `src/Repository/JournalEntreeRepository.php`,
   `src/Service/ConseilService.php`, `templates/parent/layout.html.twig` et
   `templates/enfant/accueil.html.twig`.
2. Repère la page d'attente de `/parent` créée en phase 04 : c'est elle que tu
   remplaces.
3. Annonce-moi le plan avant de coder.

## À implémenter

### 1. Données du graphique (repository)

Dans `JournalEntreeRepository` :

```php
public function getGraphiqueEcran(Enfant $enfant, int $nombreJours): array
```

- renvoie `['labels' => ['08/09', …], 'minutes' => [95, …]]` ;
- **une entrée par jour** sur toute la période, même sans journal : un jour sans
  journal vaut **0 minute** ;
- une seule requête pour récupérer les journaux de la période, puis le tableau
  est complété en PHP ;
- paramètre de date passé avec le type `date_immutable`.

⚠️ Aucun DQL dans un contrôleur : tout ici.

### 2. `Parent\TableauDeBordController`

Route `/parent` (nom `parent_dashboard`), réservée à `ROLE_PARENT` :

- **aucun enfant** → un écran d'accueil dédié qui invite à créer un premier
  profil (gabarit séparé) ;
- **sélecteur d'enfant** : boutons avatar + prénom, l'enfant courant étant celui
  passé en `?enfant=<id>`, sinon le premier par ordre alphabétique ;
  ⚠️ **sécurité** : ne cherche l'identifiant demandé **que parmi les enfants du
  parent connecté**. Un identifiant étranger retombe silencieusement sur le
  premier enfant — jamais de données d'un autre foyer, jamais d'erreur ;
- en-tête : nom complet, âge, limite quotidienne, lien « Modifier le profil » ;
- **journal du jour** : temps d'écran total coloré selon le niveau, rappel de la
  limite, jauge de progression, pastilles des douleurs signalées, et les
  **mêmes conseils** que ceux reçus par l'enfant (réutilise `ConseilService`,
  ne réimplémente rien) ;
- si le journal n'est pas rempli : « [Prénom] n'a pas encore rempli son journal
  aujourd'hui. » ;
- **période** : `?periode=7` (défaut) ou `?periode=30`, avec deux boutons.

### 3. Le graphique, partagé entre deux espaces

- `public/js/graphique-ecran.js` : une fonction
  `afficherGraphiqueEcran(canvasId, donnees, limite, libelles)` qui construit un
  graphique en **courbe** avec Chart.js :
  - la série du temps d'écran (minutes) ;
  - une **ligne de limite** en pointillés rouges ;
  - des libellés passés en paramètre, pour tutoyer l'enfant (« Mon temps
    d'écran ») et vouvoyer le parent (« Temps d'écran (min) »).
- Ce fichier va dans `public/js/` **parce qu'il sert à deux pages** ; le reste
  du JavaScript reste dans le bloc `javascripts` de sa page.
- Chart.js est chargé par CDN dans le bloc `javascripts` des deux pages.
- Données PHP → JS avec `{{ graphique|json_encode|raw }}` (tableaux de nombres
  et de dates, jamais une saisie utilisateur).

### 4. Compléter l'accueil enfant

Ajouter le même graphique sur `/enfant`, avec le sélecteur **7 / 30 derniers
jours** (`?periode=`), sous la jauge du jour existante.

## Contraintes techniques et architecturales

- Contrôleurs simples : lire la requête, appeler le repository ou le service,
  rendre le gabarit. Aucune requête Doctrine dans le contrôleur.
- Lire les paramètres d'URL avec `$request->query->getInt(…)`.
- Pas de logique métier dans Twig : le pourcentage de la jauge et le niveau de
  couleur sont calculés côté PHP (ou par une méthode d'entité existante).
- Réutilise les classes maison (`jauge`, `niveau-*`, `stat-valeur`, `pastille`,
  `carte-titre`…) plutôt que d'écrire du CSS neuf.
- Le graphique doit rester lisible sur mobile (conteneur à hauteur fixe,
  `maintainAspectRatio: false`).

## Commandes attendues

```bash
docker compose exec app php bin/console cache:clear
docker compose exec app php bin/console lint:twig templates
docker compose exec app php bin/console dbal:run-sql "SELECT date, ecran_tv FROM journal_entree ORDER BY date DESC LIMIT 5"
```

Pour avoir de l'historique à afficher pendant les essais, insère quelques
journaux de jours passés en SQL, ou attends la phase 12 (fixtures).

## Ce qui n'est PAS dans cette phase

- Pas d'administration des comptes parents (phase 11).
- Pas de fixtures (phase 12).
- Pas d'historique des douleurs sur plusieurs jours : seules celles du jour
  s'affichent.
- Pas d'export de données : **hors périmètre**.

## Scénario de test manuel

1. Se connecter en parent et ouvrir `/parent`.
2. Vérifier le temps d'écran du jour, la jauge colorée, les douleurs signalées et les conseils reçus par l'enfant.
3. Cliquer sur « 30 derniers jours » et vérifier que la courbe et la ligne de limite changent d'échelle.
4. Modifier l'URL avec l'identifiant d'un enfant qui ne vous appartient pas, par exemple `/parent?enfant=999`.
5. **Résultat attendu** : les deux périodes s'affichent correctement, les jours sans journal valent 0, et l'identifiant étranger affiche simplement votre premier enfant, sans erreur ni donnée d'un autre foyer.

## Critères de validation

- [ ] Un parent sans enfant voit l'écran d'accueil dédié, pas une page vide.
- [ ] Le graphique s'affiche côté parent **et** côté enfant, avec le même
      fichier JS.
- [ ] Les conseils affichés au parent sont identiques à ceux vus par l'enfant.
- [ ] La courbe compte bien 7 (ou 30) points, zéros inclus.
- [ ] `lint:twig templates` est au vert et aucune erreur n'apparaît dans la
      console du navigateur.

## Enfin

- N'écris **aucun test automatisé** : je valide au navigateur.
- Termine en m'expliquant en quelques lignes pourquoi un `?enfant=` étranger
  retombe sur le premier enfant ici, alors qu'une modification de profil renvoie
  une 403 via le voter.
