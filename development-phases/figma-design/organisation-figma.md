# Organiser le fichier Figma

Un import brut donne un empilement de calques exploitable, mais illisible. Ce
document décrit comment le ranger et en tirer un vrai design system, à partir
des valeurs réelles du projet.

---

## 1. Structure du fichier

Créez **cinq pages** dans le fichier Figma (panneau de gauche, `+` à côté de
*Pages*) :

```text
📄 00 · Design system     couleurs, textes, composants
📄 01 · Public            accueil, connexion parent, connexion enfant, inscription
📄 02 · Enfant            accueil, journal (2 étapes), conseils, bibliothèque, profil
📄 03 · Parent            tableau de bord, mes enfants, formulaires, profil
📄 04 · Admin             contenus, parents
📄 99 · Imports bruts     zone de dépôt, jamais retouchée
```

**La page « Imports bruts » est importante** : les captures y arrivent, vous
dupliquez ce dont vous avez besoin vers les pages propres. Vous gardez ainsi
toujours l'original sous la main, et vous pouvez réimporter sans écraser votre
travail.

Dans chaque page, disposez les frames **en colonnes par parcours** et en lignes
par largeur :

```text
          1440 px              390 px
Accueil   [frame]              [frame]
Étape 1   [frame]              [frame]
Étape 2   [frame]              [frame]
```

---

## 2. Variables de couleur

Panneau **Variables** → créer une collection `Digi-Santé`, avec ces valeurs —
ce sont exactement celles de `public/css/app.css` :

| Nom de la variable | Valeur | Usage dans l'application |
|---|---|---|
| `marine` | `#1F3864` | barre de navigation, titres, bouton principal |
| `turquoise` | `#0E7C7B` | liens, accents, courbe du graphique |
| `turquoise-clair` | `#14A3A1` | survol des liens |
| `or` | `#C99A2E` | boutons secondaires (vidéos, liens externes) |
| `or-clair` | `#E5B955` | survol |
| `fond` | `#E7F3F2` | fond de page |
| `bordure` | `#D6E6E5` | contours de cartes et de champs |
| `texte` | `#16233D` | texte courant |
| `texte-doux` | `#5B6B85` | texte secondaire |
| `vert` / `vert-doux` | `#16A34A` / `#DCFCE7` | niveau « raisonnable » (< 2 h) |
| `orange` / `orange-doux` | `#EA580C` / `#FFEDD5` | niveau « élevé » (2 à 4 h) |
| `rouge` / `rouge-doux` | `#DC2626` / `#FEE2E2` | niveau « excessif » (> 4 h) |

⚠️ Le fond des **cartes, champs, tableaux et menus** reste **blanc** : dans
l'application, c'est une variable de Bootstrap volontairement non modifiée. Ne
la remplacez pas par la couleur de fond de page.

**Effet d'ombre** à enregistrer comme style :
`0 10px 30px -12px rgba(31, 56, 100, 0.28)` — ombre douce, portée basse.

---

## 3. Styles de texte

Deux polices, toutes deux disponibles nativement dans Figma (Google Fonts) :

| Style | Police | Graisse | Usage |
|---|---|---|---|
| `Titre / XL` | Baloo 2 | 800 | accroche de la page d'accueil |
| `Titre / L` | Baloo 2 | 700 | titres de page (`h1`) |
| `Titre / M` | Baloo 2 | 700 | titres de carte (`h2`, `h3`) |
| `Corps / Normal` | Nunito | 400 | texte courant |
| `Corps / Gras` | Nunito | 700 | mises en avant |
| `Corps / Doux` | Nunito | 600 | texte secondaire, couleur `texte-doux` |
| `Chiffre / Géant` | Baloo 2 | 800 | le temps d'écran du jour |

---

## 4. Composants à créer

Extraits des captures avec **Capture Selection** (⌥⇧D) ou découpés dans les
frames importées. Ce sont les briques réellement réutilisées dans l'application :

| Composant | Variantes | Où le trouver |
|---|---|---|
| **Bouton** | marine, or, fantôme, supprimer × (normal, large) | partout |
| **Carte** | standard, `card-enfant` (bord coloré) | toutes les pages |
| **Pastille** | neutre, verte, orange, rouge | douleurs, limites, compteurs |
| **Jauge** | vert, orange, rouge × (fine, épaisse) | accueil enfant, tableau de bord |
| **Avatar** | petit, normal, grand × 12 avatars | partout dans les espaces enfant et parent |
| **Ligne de profil** | — | profils enfant et parent, fiche admin |
| **Barre de navigation** | enfant (colorée), parent, admin | en-tête de chaque espace |
| **Carte de conseil** | orange (alerte), vert (félicitations) | écran des conseils |
| **Champ de formulaire** | normal, avec aide, **en erreur** | tous les formulaires |
| **Curseur** | 0 %, 50 %, 100 % | journal, étape 1 |

L'état **en erreur** est celui qu'on oublie : capturez-le pour de bon
(écran P3 « Ajouter un enfant — erreurs ») plutôt que de l'inventer.

---

## 5. Ordre de travail conseillé

```text
1. Importer les 5 écrans P1              → page « 99 · Imports bruts »
2. Créer les variables de couleur et les styles de texte
3. Dupliquer les frames vers leurs pages, les renommer
4. Extraire les composants d'une seule frame bien remplie
      (l'accueil enfant contient : navigation, avatar, jauge, cartes, boutons)
5. Remplacer les éléments des autres frames par les composants
6. Importer les écrans P2, puis P3, en réutilisant les composants
```

L'étape 4 est celle qui fait gagner du temps : tout le reste du fichier
s'appuie dessus. Ne l'entamez qu'une fois les couleurs et les textes en place.

---

## 6. Deux principes à tenir

**Ne retouchez jamais un import brut.** Dupliquez-le, retravaillez la copie.
Le jour où l'interface changera, vous réimporterez sans rien perdre.

**Le design doit rester fidèle à l'application** tant qu'il sert de
documentation. Si vous voulez explorer une autre direction visuelle, créez une
page `05 · Exploration` : mélanger l'existant et les propositions dans les mêmes
frames rend le fichier inutilisable pour les deux usages.

---

➡️ Guide d'utilisation de l'extension : [`README.md`](./README.md)
➡️ Liste des écrans : [`ecrans-a-capturer.md`](./ecrans-a-capturer.md)
