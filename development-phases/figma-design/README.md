# Générer le design Figma depuis le site — guide pas à pas

Ce guide explique comment transformer l'application **Digi-Santé Junior**, qui
tourne **sur votre ordinateur**, en maquette Figma.

Aucune connaissance de Figma n'est nécessaire : tout est expliqué, clic par
clic.

**Ce que vous obtiendrez** : pas des captures d'écran, mais de **vrais calques
Figma**. Chaque titre reste un texte modifiable, chaque bouton reste une forme
qu'on peut déplacer et recolorer.

---

## Le vocabulaire, en trois lignes

Trois mots reviennent sans arrêt dans Figma. Les voici une fois pour toutes.

| Mot | Ce que c'est |
|---|---|
| **Plugin** | un petit logiciel qui s'ajoute **dans** Figma pour lui donner une fonction en plus. Ici : importer des pages web. |
| **Extension** | la même idée, mais **dans votre navigateur** Chrome. Ici : photographier la page que vous regardez. |
| **Frame** | un cadre dans Figma, qui contient une page ou un écran. C'est l'équivalent d'une « page » de maquette. |

---

## Avant de commencer

Il vous faut quatre choses :

1. **Un compte Figma gratuit** — <https://figma.com>, bouton *Sign up*. Le plan
   gratuit suffit.
2. **Le navigateur Google Chrome** (ou Edge, ou Brave : ce sont les mêmes
   dessous).
3. **Le projet démarré sur votre ordinateur**. Dans un terminal, à la racine du
   projet :

   ```bash
   make start
   ```

   Puis ouvrez <http://localhost:8081> dans Chrome. Si la page d'accueil de
   Digi-Santé Junior s'affiche, vous êtes prêt.

4. **Des données à afficher** dans l'application — c'est l'étape suivante.

---

## Étape 1 — Préparer les données à photographier

Une page vide donne une maquette vide. Il faut donc que l'application contienne
des enfants, des journaux et des contenus **avant** de commencer.

Deux possibilités. Choisissez-en une.

### Option A — Les données de démonstration (le plus rapide)

```bash
make fixtures
```

Vous obtenez tout de suite : 1 administrateur, 2 parents, 4 enfants, plusieurs
semaines de journaux et une quinzaine de contenus.

Les comptes à utiliser :

| Rôle | Page de connexion | Identifiant | Mot de passe |
|---|---|---|---|
| Administrateur | `/login` | `admin@digisante.local` | `admin123` |
| Parent | `/login` | `parent@digisante.local` | `parent123` |
| Enfant | `/connexion-enfant` | `lea` | `enfant123` |

⚠️ `make fixtures` **efface** le contenu de la base avant de la remplir. Si vous
avez créé des données à la main, elles disparaîtront.

### Option B — Vos propres données (plus long, plus formateur)

Si vous préférez maîtriser ce qui s'affiche dans la maquette :

1. Ouvrez <http://localhost:8081/inscription> et créez un compte parent.
2. Connectez-vous, puis **Mes enfants → Ajouter un enfant**. Notez bien
   l'identifiant que l'application génère (par exemple `lea`) et le mot de passe
   que vous avez choisi.
3. Déconnectez-vous, allez sur <http://localhost:8081/connexion-enfant> et
   connectez-vous avec cet enfant.
4. Remplissez un journal (temps d'écran, puis douleurs).

En moins de dix minutes, vous avez de quoi photographier la plupart des écrans.
Il vous manquera seulement l'historique de plusieurs semaines : les graphiques
seront donc presque vides.

> **Conseil** : pour une maquette qui a l'air vivante, prenez l'option A. Les
> journaux des semaines passées remplissent les courbes.

---

## Étape 2 — Installer le plugin dans Figma

1. Allez sur <https://figma.com> et connectez-vous.
2. Créez un fichier de design vide : bouton **+ Design file** (ou
   *Nouveau fichier*).
3. Dans la barre d'outils en bas de l'écran, cliquez sur l'icône
   **Resources** (une grille de petits carrés), puis sur l'onglet **Plugins**.
4. Tapez **`html.to.design`** dans la recherche.
5. Choisissez celui de l'éditeur **‹div›RIOTS**, intitulé
   *html.to.design — Import websites…*, puis cliquez sur **Run**.

**Ce que vous devez voir** : un panneau s'ouvre par-dessus votre fichier, avec
une rangée d'onglets : **Web · Extension · File · Editor · MCP · API**.

> Si la recherche ne donne rien, passez par le site officiel
> <https://html.to.design> : il renvoie vers la fiche du plugin.

**Pour le rouvrir plus tard** : clic droit dans le fichier →
**Plugins → html.to.design**.

---

## Étape 3 — Installer l'extension dans Chrome

### Pourquoi une extension en plus du plugin ?

C'est le point à comprendre, tout le reste en découle.

Le plugin sait importer une page **depuis son adresse** (onglet *Web*). Mais il
va la chercher **depuis les serveurs de html.to.design**, quelque part sur
Internet. Or :

- votre application tourne sur **`localhost`**, c'est-à-dire **votre
  ordinateur**. Personne d'autre ne peut y accéder. L'onglet *Web* ne
  fonctionnera donc **jamais** pour votre projet ;
- et même en ligne, la plupart des pages demandent d'**être connecté**. Un
  serveur extérieur ne connaît pas votre mot de passe.

L'**extension**, elle, photographie **la page que vous avez sous les yeux**,
dans votre navigateur, avec votre session ouverte. C'est la seule méthode qui
marche ici.

```text
Onglet « Web » du plugin  →  ✗ ne voit pas localhost
Extension Chrome          →  ✓ photographie votre écran, connexion comprise
```

### L'installation

1. Ouvrez le **Chrome Web Store** : <https://chromewebstore.google.com>
2. Recherchez **`html.to.design`** (éditeur **‹div›RIOTS**). Le lien direct est
   aussi sur <https://html.to.design>, section *Extension*.
3. Cliquez sur **Ajouter à Chrome**, puis **Ajouter l'extension**.
4. Cliquez sur l'icône pièce de puzzle 🧩 en haut à droite de Chrome, puis sur
   l'épingle 📌 à côté de html.to.design : son icône reste maintenant visible.

**Ce que vous devez voir** : une nouvelle icône dans la barre d'outils de
Chrome, en haut à droite.

---

## Étape 4 — Relier l'extension et Figma

1. Dans Figma, dans le panneau du plugin, cliquez sur l'onglet **Extension**.
2. Vous voyez une liste vide : c'est là que vos photos apparaîtront.
3. Laissez cette fenêtre ouverte pendant tout le travail.

L'interrupteur **Auto-import new captures** :

- **activé** : chaque photo arrive automatiquement dans Figma ;
- **désactivé** : vous choisissez quoi importer, avec le bouton ⬇ de chaque
  ligne.

Pour commencer, **laissez-le désactivé** : vous garderez le contrôle, et vous
éviterez de gaspiller vos imports gratuits (voir l'encadré à l'étape 6).

---

## Étape 5 — Régler la largeur avant de photographier

Cliquez sur l'icône de l'extension dans Chrome. Une fenêtre s'ouvre, avec deux
colonnes : **Viewports** et **Themes**.

**Viewport** veut simplement dire **largeur d'écran**. Un site ne s'affiche pas
pareil sur un ordinateur et sur un téléphone : vous photographiez donc les deux.

Pour ce projet :

| Case | Cochée ? | Pourquoi |
|---|---|---|
| **Browser (1512px)** | ✅ | la largeur réelle de votre fenêtre |
| **1440 px** | ✅ | la largeur de référence des maquettes sur ordinateur |
| **390 px** | ✅ pour les écrans importants | la version téléphone : le menu se replie, les cartes s'empilent |
| 1920 / 1024 / 768 | ❌ | inutiles ici, et chaque largeur prend du temps |
| **Themes** | *Browser theme* seul | l'application n'a pas de mode sombre |

---

## Étape 6 — Photographier une page

1. Dans Chrome, ouvrez <http://localhost:8081> et **connectez-vous** avec le
   compte correspondant à l'écran voulu (voir
   [`ecrans-a-capturer.md`](./ecrans-a-capturer.md)).
2. Allez sur la page à photographier.
3. **Mettez la page dans l'état voulu** : ouvrez la fenêtre qui doit être
   visible, provoquez le message d'erreur que vous voulez montrer… L'extension
   photographie ce qui est affiché **à cet instant précis**.
4. Appuyez sur **⌥⇧E** (Mac) ou **Alt+Maj+E** (Windows). Vous pouvez aussi
   cliquer sur l'icône de l'extension puis sur le bouton bleu
   **Capture Current Page**.

Le bouton jaune **Capture Selection** (**⌥⇧D**) photographie **une zone** que
vous dessinez à la souris. Très pratique pour ne récupérer qu'un bouton ou une
carte, sans toute la page.

**Ce que vous devez voir** : quelques secondes plus tard, une vignette apparaît
dans l'onglet *Extension* du plugin, côté Figma.

### La barre noire de Symfony

En local, l'application est en mode développement : une **barre noire** s'affiche
en bas de chaque page. Elle sera photographiée avec le reste.

Deux solutions, au choix :

- cherchez la petite croix **✕** à droite de cette barre et cliquez dessus pour
  la masquer avant de photographier ;
- ou photographiez sans vous en occuper, puis **supprimez son calque dans
  Figma** : il arrive tout en bas de la liste des calques, appuyez sur
  *Supprimer*.

### Deux détails qui font gagner du temps

- **Fermez la fenêtre de l'extension** avant d'appuyer sur ⌥⇧E : ce qui recouvre
  la page finit parfois sur la photo.
- **Attendez une ou deux secondes** sur les pages à graphique (accueil enfant,
  tableau de bord parent) : le graphique s'anime, et une photo trop rapide le
  capture à moitié dessiné.

---

## Étape 7 — Importer la photo dans Figma

1. Retournez dans Figma, onglet **Extension** du plugin.
2. Cliquez sur le bouton ⬇ à droite de la photo voulue.
3. Patientez : l'import prend quelques secondes.

**Ce que vous devez voir** : un grand cadre (une *frame*) apparaît dans votre
fichier, et la liste des calques à gauche se remplit de noms comme `Main`,
`Container`, `Heading`, `Form`, `Input`, `Label`.

4. **Renommez la frame tout de suite.** Double-cliquez sur son nom dans la liste
   de gauche et écrivez par exemple :

   ```text
   03 · Enfant · Accueil · 1440
   ```

   Sans cela, vous vous retrouverez avec quinze frames appelées
   `http://localhost:8081` et vous ne saurez plus laquelle est laquelle.

> 💡 **Imports gratuits.** L'extension affiche
> « Sign in to ‹div›RIOTS ONE for 10 free imports/mo » : **10 imports par
> mois** avec un compte gratuit. Le projet compte 22 écrans : commencez donc
> par les **5 écrans prioritaires** de la liste. Après votre premier import,
> regardez votre compteur : vous saurez si une photo en plusieurs largeurs
> consomme un crédit ou plusieurs.

---

## Ça ne marche pas ?

| Problème | Cause probable | Solution |
|---|---|---|
| La page ne s'ouvre pas sur `localhost:8081` | l'application n'est pas démarrée | `make start`, puis réessayez |
| Les pages sont vides, sans enfant ni contenu | pas de données en base | revenez à l'étape 1 |
| Rien n'apparaît dans l'onglet *Extension* de Figma | l'extension et Figma ne sont pas sur le même compte | reconnectez-vous des deux côtés, puis rechargez la page Figma |
| L'extension ne réagit pas au raccourci | le raccourci est pris par un autre logiciel | cliquez sur l'icône de l'extension et utilisez le bouton bleu |
| L'extension refuse de photographier la page | elle n'a pas l'autorisation sur ce site | clic droit sur son icône → *Ce site peut lire et modifier…* → **Autoriser** |
| Une barre noire apparaît en bas de la maquette | la barre de debug Symfony | voir l'encadré de l'étape 6 |
| Le graphique est une image, impossible à modifier | c'est normal, voir ci-dessous | — |

---

## Ce que l'import rend bien, et moins bien

| Élément | Résultat | Quoi faire |
|---|---|---|
| Textes, boutons, cartes, formulaires | ✅ calques modifiables | rien, c'est l'objectif |
| **Schéma du corps** (journal, étape 2) | ✅ dessin vectoriel modifiable | rien — c'est le plus bel import du projet |
| **Graphiques** (accueil enfant, tableau de bord) | ⚠️ image figée | les redessiner dans Figma si le designer doit y toucher |
| **Emojis** (avatars, menus) | ⚠️ leur dessin change d'un ordinateur à l'autre | les remplacer par des images si la maquette doit être stable |
| **Fenêtre d'intensité** (journal, étape 2) | photographiée **seulement si elle est ouverte** | ouvrez-la avant d'appuyer sur ⌥⇧E |
| Polices Baloo 2 et Nunito | ✅ disponibles dans Figma | rien, ce sont des Google Fonts |

---

## Récapitulatif

```text
1. make start          → l'application tourne sur localhost:8081
2. make fixtures       → des données à afficher
3. Plugin html.to.design installé dans Figma
4. Extension html.to.design installée dans Chrome
5. Onglet « Extension » du plugin ouvert dans Figma
6. Largeurs réglées : 1512 + 1440, et 390 pour les écrans importants
7. Connexion au site, page mise dans le bon état
8. ⌥⇧E pour photographier
9. ⬇ dans Figma pour importer, puis renommer la frame
10. Ranger le fichier et construire le design system
```

➡️ Quels écrans photographier : [`ecrans-a-capturer.md`](./ecrans-a-capturer.md)
➡️ Comment ranger le fichier Figma : [`organisation-figma.md`](./organisation-figma.md)
➡️ Retour au parcours de développement : [`../README.md`](../README.md)
