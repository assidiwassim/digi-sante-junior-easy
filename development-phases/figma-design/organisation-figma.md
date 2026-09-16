# Ranger le fichier Figma

Une fois vos écrans importés, vous avez un tas de cadres posés côte à côte. Ce
document explique comment en faire un fichier propre, utilisable par un
designer.

À lire **après** avoir importé vos 5 premiers écrans.

---

## Le vocabulaire Figma, en quatre mots

| Mot | Ce que c'est | Où ça se trouve |
|---|---|---|
| **Page** | un grand classeur dans votre fichier. Un fichier peut en contenir plusieurs. | panneau de gauche, en haut |
| **Frame** | un cadre qui contient un écran | panneau de gauche, sous *Layers* |
| **Variable** | une couleur enregistrée sous un nom. La changer met à jour tout le fichier d'un coup. | panneau de gauche, icône *Variables* |
| **Composant** | un élément réutilisable (un bouton, une carte). On le dessine une fois, on s'en sert partout. | on le crée soi-même |

---

## 1. Créer les pages

Dans le panneau de gauche, cliquez sur **+** à côté de *Pages*, et créez-en six.
Renommez-les par double-clic :

```text
00 · Design system     les couleurs, les textes, les boutons
01 · Public            accueil, connexions, inscription
02 · Enfant            accueil, journal, conseils, bibliothèque, profil
03 · Parent            tableau de bord, mes enfants, profil
04 · Admin             contenus, parents
99 · Imports bruts     là où arrivent les photos — on n'y touche jamais
```

**Pourquoi une page « Imports bruts »** : les photos importées y arrivent. Vous
copiez ensuite ce dont vous avez besoin vers les pages propres, et vous
retravaillez la copie. Ainsi, vous gardez toujours l'original : le jour où vous
referez une photo, vous ne perdrez pas votre travail.

Pour déplacer une frame d'une page à l'autre : clic droit → **Copier**, aller
sur l'autre page, **Coller**.

### Comment disposer les frames dans une page

Rangez-les en colonnes, de gauche à droite dans l'ordre du parcours, et une
ligne par largeur :

```text
              Accueil      Journal 1     Journal 2     Conseils
1440 px       [cadre]      [cadre]       [cadre]       [cadre]
 390 px       [cadre]      [cadre]       [cadre]       [cadre]
```

---

## 2. Enregistrer les couleurs

Ce sont les couleurs exactes de l'application, prises dans son fichier de style.
Les enregistrer permet de toutes les changer d'un coup plus tard.

Panneau de gauche → **Variables** → **+** → créez une collection nommée
`Digi-Santé`, puis ajoutez ces couleurs une par une :

| Nom | Code couleur | À quoi ça sert dans l'application |
|---|---|---|
| `marine` | `#1F3864` | barre du haut, titres, bouton principal |
| `turquoise` | `#0E7C7B` | liens, courbe du graphique |
| `turquoise-clair` | `#14A3A1` | survol des liens |
| `or` | `#C99A2E` | boutons « voir la vidéo », liens externes |
| `or-clair` | `#E5B955` | survol de ces boutons |
| `fond` | `#E7F3F2` | fond de page |
| `bordure` | `#D6E6E5` | contour des cartes et des champs |
| `texte` | `#16233D` | texte normal |
| `texte-doux` | `#5B6B85` | texte secondaire, plus clair |
| `vert` | `#16A34A` | temps d'écran raisonnable (moins de 2 h) |
| `vert-doux` | `#DCFCE7` | fond des messages verts |
| `orange` | `#EA580C` | temps d'écran élevé (2 à 4 h) |
| `orange-doux` | `#FFEDD5` | fond des messages orange |
| `rouge` | `#DC2626` | temps d'écran excessif (plus de 4 h) |
| `rouge-doux` | `#FEE2E2` | fond des messages rouges |

⚠️ **Le fond des cartes, des champs et des tableaux reste blanc.** Ne le
remplacez pas par la couleur `fond` : dans l'application, c'est un choix
délibéré, et tout changerait d'aspect.

**Ombre des cartes**, à enregistrer comme style d'effet :
X 0, Y 10, flou 30, étalement −12, couleur `#1F3864` à 28 % d'opacité.

---

## 3. Enregistrer les styles de texte

L'application utilise deux polices, toutes deux disponibles dans Figma sans rien
installer (ce sont des Google Fonts) :

- **Baloo 2** pour les titres — ronde, chaleureuse, adaptée aux enfants ;
- **Nunito** pour le texte courant.

Sélectionnez un texte dans une frame importée, puis, dans le panneau de droite,
cliquez sur les quatre points `⠿` à côté de *Text* → **+** pour enregistrer le
style. Créez-en sept :

| Nom du style | Police | Graisse | Où on le voit |
|---|---|---|---|
| `Titre / XL` | Baloo 2 | 800 | la grande accroche de la page d'accueil |
| `Titre / L` | Baloo 2 | 700 | titre de page (« Salut Léa ! ») |
| `Titre / M` | Baloo 2 | 700 | titre de carte |
| `Corps / Normal` | Nunito | 400 | texte courant |
| `Corps / Gras` | Nunito | 700 | mises en avant |
| `Corps / Doux` | Nunito | 600 | texte secondaire (couleur `texte-doux`) |
| `Chiffre / Géant` | Baloo 2 | 800 | le temps d'écran du jour |

---

## 4. Créer les composants

Un **composant** se dessine une fois et se réutilise partout. Si vous modifiez
l'original, toutes les copies suivent.

**Comment faire** : sélectionnez l'élément dans une frame importée →
clic droit → **Create component** (ou **⌥⌘K** / **Ctrl+Alt+K**).

Les dix éléments qui reviennent le plus dans cette application :

| Composant | Variantes à prévoir | Où le trouver |
|---|---|---|
| **Bouton** | marine, or, fantôme, rouge « supprimer » | partout |
| **Carte** | simple, et version colorée de l'espace enfant | toutes les pages |
| **Pastille** | grise, verte, orange, rouge | douleurs, limites, compteurs |
| **Jauge** | verte, orange, rouge | accueil enfant, tableau de bord |
| **Avatar rond** | petit, moyen, grand | espaces enfant et parent |
| **Ligne de profil** | — | pages de profil, fiche admin |
| **Barre du haut** | enfant (colorée), parent, admin | en haut de chaque page |
| **Carte de conseil** | orange (alerte), verte (félicitations) | écran des conseils |
| **Champ de formulaire** | normal, avec texte d'aide, **en erreur** | tous les formulaires |
| **Curseur** | à 0 %, à 50 %, à 100 % | journal, étape 1 |

💡 Le plus simple pour attraper un élément isolé : utilisez
**Capture Selection** (**⌥⇧D**) dans le navigateur, en dessinant juste autour de
l'élément. Vous obtenez une petite frame propre, sans le reste de la page.

⚠️ N'oubliez pas l'état **en erreur** des champs : c'est celui qu'on invente le
plus souvent au lieu de le photographier. L'écran « Ajouter un enfant —
erreurs » de la liste est là pour ça.

---

## 5. Dans quel ordre travailler

```text
1. Importer les 5 écrans prioritaires      → page « 99 · Imports bruts »
2. Enregistrer les couleurs et les styles de texte
3. Copier les frames vers leurs pages, les renommer
4. Créer les composants à partir d'UNE seule frame bien remplie
      (l'accueil enfant contient déjà : barre du haut, avatar, jauge,
       cartes, boutons, pastilles)
5. Dans les autres frames, remplacer les éléments par ces composants
6. Importer les écrans de priorité 2, puis 3
```

L'étape 4 est celle qui fait gagner le plus de temps : tout le reste s'appuie
dessus. Ne la commencez qu'une fois les couleurs et les textes enregistrés.

---

## 6. Deux règles à ne pas oublier

**Ne modifiez jamais un import brut.** Copiez-le d'abord. Le jour où
l'application changera, vous referez la photo sans perdre votre travail.

**Gardez la maquette fidèle à l'application** tant qu'elle sert de
documentation. Si vous voulez proposer un autre design, créez une page
`05 · Propositions`. Mélanger l'existant et les idées neuves dans les mêmes
cadres rend le fichier inutilisable pour les deux usages.

---

➡️ Le guide d'utilisation de l'extension : [`README.md`](./README.md)
➡️ La liste des écrans : [`ecrans-a-capturer.md`](./ecrans-a-capturer.md)
