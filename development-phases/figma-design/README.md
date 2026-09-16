# Générer le design Figma depuis le site

Guide pas à pas pour transformer l'application **Digi-Santé Junior** en design
Figma éditable, avec le plugin **html.to.design** et son extension navigateur.

Le résultat n'est pas une capture d'écran : ce sont de **vrais calques Figma**
(frames, textes, autolayout, vecteurs), retravaillables par un designer.

| | |
|---|---|
| Site à capturer | <https://digisante.150.lebondeveloppeur.net> |
| Comptes de démonstration | voir [`../../test-prod.txt`](../../test-prod.txt) |
| Écrans à capturer | [`ecrans-a-capturer.md`](./ecrans-a-capturer.md) |
| Organisation du fichier Figma | [`organisation-figma.md`](./organisation-figma.md) |

---

## Pourquoi l'extension navigateur, et pas seulement l'URL

Le plugin sait importer une page depuis son URL. Mais **il la récupère depuis
ses propres serveurs** : il ne peut pas se connecter à votre application.

Sur les 22 écrans du projet, **4 seulement sont publics**. Les espaces enfant,
parent et administrateur sont derrière une authentification : un import par URL
n'y verrait que la page de connexion.

L'**extension navigateur** capture l'onglet **que vous avez sous les yeux**,
avec votre session ouverte. C'est le seul moyen d'obtenir les écrans connectés.

```text
Import par URL          →  4 écrans publics
Extension navigateur    →  les 22 écrans, connexion comprise   ← la bonne méthode
```

---

## Étape 1 — Installer le plugin dans Figma

1. Ouvrir Figma (application de bureau ou navigateur) et créer un fichier de
   design vide.
2. Menu **Resources** (icône en forme de grille dans la barre d'outils) →
   onglet **Plugins** → rechercher **`html.to.design`**.
3. Le plugin recherché est celui de **‹div›RIOTS**, intitulé
   « html.to.design — Import websites… ». Cliquer sur **Run** / **Ouvrir**.
4. Le panneau du plugin s'ouvre, avec ses onglets :
   **Web · Extension · File · Editor · MCP · API**.

> Le site officiel <https://html.to.design> renvoie vers la fiche du plugin et
> vers l'extension : c'est le point d'entrée le plus sûr si la recherche dans
> Figma ne donne rien.

Pour le retrouver ensuite : clic droit dans le fichier →
**Plugins → html.to.design**, ou le raccourci **⌥⌘P** (macOS) /
**Ctrl+Alt+P** (Windows) qui relance le dernier plugin utilisé.

---

## Étape 2 — Installer l'extension dans le navigateur

1. Ouvrir le **Chrome Web Store** et rechercher **`html.to.design`**
   (éditeur ‹div›RIOTS). Le lien direct figure aussi sur
   <https://html.to.design>, section *Extension*.
2. **Ajouter à Chrome**, puis épingler l'icône dans la barre d'outils : vous
   allez vous en servir une vingtaine de fois.
3. L'extension fonctionne sur les navigateurs Chromium (Chrome, Edge, Brave).

---

## Étape 3 — Relier l'extension au plugin

1. Dans Figma, panneau du plugin → onglet **Extension**.
2. Vous y voyez la liste des captures faites depuis le navigateur. Elle est
   vide au départ, c'est normal.
3. Activer **Auto-import new captures** si vous voulez que chaque capture
   arrive automatiquement dans le fichier Figma. Sinon, vous importerez au cas
   par cas avec le bouton ⬇ de chaque ligne.

Les deux logiciels communiquent par votre compte : la capture faite dans Chrome
apparaît dans le panneau Figma en quelques secondes.

---

## Étape 4 — Régler les viewports avant de capturer

Dans la fenêtre de l'extension (icône dans la barre d'outils de Chrome) :

| Réglage | Valeur conseillée pour ce projet | Pourquoi |
|---|---|---|
| **Browser (1512px)** | coché | reprend la largeur réelle de votre fenêtre |
| **1440 px** | coché | largeur de référence des maquettes bureau |
| **390 px** | coché pour les écrans clés | la grille Bootstrap réorganise tout sur mobile |
| **1920 / 1024 / 768** | décochés | trois largeurs suffisent, chaque largeur coûte du temps et du quota |
| **Themes** | *Browser theme* seul | l'application n'a pas de mode sombre |

⚠️ **Quota du plan gratuit** : l'extension affiche
« Sign in to ‹div›RIOTS ONE for 10 free imports/mo ». **10 imports par mois**.
Avec 22 écrans et 2 largeurs, vous êtes très au-dessus. Deux conséquences :

- capturez **dans l'ordre de priorité** indiqué dans
  [`ecrans-a-capturer.md`](./ecrans-a-capturer.md) — les 5 écrans P1 d'abord ;
- vérifiez votre compteur après le premier import, pour savoir si une capture
  multi-viewports consomme un crédit ou plusieurs.

---

## Étape 5 — Capturer les pages

1. Dans Chrome, ouvrir <https://digisante.150.lebondeveloppeur.net> et
   **se connecter** avec le compte correspondant à l'écran visé
   (voir la colonne « Compte » de la liste des écrans).
2. Aller sur la page à capturer et **la mettre dans l'état voulu** : dérouler
   un menu, ouvrir une modale, provoquer un message d'erreur… L'extension
   capture ce qui est affiché à l'instant T.
3. Lancer la capture :
   - **Capture Current Page** — toute la page (raccourci **⌥⇧E**) ;
   - **Capture Selection** — une zone choisie à la souris (**⌥⇧D**), utile pour
     n'extraire qu'un composant (une carte, la jauge, la barre de navigation).
4. Attendre la vignette dans la liste des captures.

**Conseils de capture pour ce projet**

- Fermez la fenêtre du plugin et les panneaux qui recouvrent la page avant de
  déclencher : ce qui masque la page se retrouve parfois dans le rendu.
- Laissez le **graphique Chart.js** finir de s'animer (1 à 2 secondes) avant de
  capturer, sinon il apparaît à moitié dessiné.
- Pour les états d'erreur, soumettez réellement le formulaire fautif : la page
  revient avec ses messages, c'est cet état-là qu'il faut capturer.

---

## Étape 6 — Importer dans Figma

1. Revenir dans Figma, panneau du plugin, onglet **Extension**.
2. Cliquer sur ⬇ à droite de la capture voulue (ou ne rien faire si
   *Auto-import* est actif).
3. Le contenu arrive sous forme de frame, avec une arborescence de calques
   nommés (`Main`, `Container`, `Heading`, `Form`, `Input`, `Label`…).
4. **Renommer la frame immédiatement** — par exemple
   `05 · Enfant · Accueil · 1440` — sinon vous vous retrouverez avec quinze
   frames appelées `https://digisante.150…`.

Passez ensuite à [`organisation-figma.md`](./organisation-figma.md) : sans
rangement ni design system, l'import n'est qu'un tas de calques.

---

## Ce que l'import rend mal, et quoi faire

| Élément | Résultat | Solution |
|---|---|---|
| **Graphiques Chart.js** (accueil enfant, tableau de bord parent) | image matricielle : c'est un `<canvas>`, il n'y a pas de vecteur à récupérer | redessiner dans Figma si le designer doit le retravailler, sinon garder l'image comme référence |
| **Schéma corporel** (journal, étape 2) | vectoriel éditable — c'est du SVG inline | rien à faire, c'est le meilleur élément de l'import |
| **Emojis** (avatars, menus, zones du corps) | texte système, rendu variable selon la machine | les transformer en composants Figma si le design doit être stable |
| **Modale d'intensité** | capturée seulement si elle est **ouverte** à l'écran | ouvrir la modale avant de déclencher la capture |
| **Polices** | Baloo 2 et Nunito sont des Google Fonts | disponibles nativement dans Figma, rien à installer |

Un point en votre faveur : le site est en environnement de **production**, donc
**sans la barre de debug Symfony**. Vos captures sont propres, sans bandeau noir
en bas de page.

---

## Récapitulatif

```text
1. Plugin html.to.design installé dans Figma
2. Extension html.to.design installée dans Chrome
3. Onglet « Extension » du plugin ouvert (auto-import au choix)
4. Viewports réglés : 1512 + 1440, et 390 pour les écrans clés
5. Connexion au site avec le bon compte, page mise dans le bon état
6. ⌥⇧E pour capturer
7. ⬇ dans Figma pour importer, puis renommer la frame
8. Ranger et construire le design system
```

➡️ Liste des écrans : [`ecrans-a-capturer.md`](./ecrans-a-capturer.md)
➡️ Rangement et design system : [`organisation-figma.md`](./organisation-figma.md)
➡️ Retour au parcours : [`../README.md`](../README.md)
