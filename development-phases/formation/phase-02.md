# Formation — Phase 02 : Gabarit de base, charte graphique et accueil

## 1. Objectifs pédagogiques

À la fin de cette leçon, vous devez être capable de :

- expliquer ce qu'est un **moteur de gabarits** et pourquoi on n'écrit pas du
  HTML dans un contrôleur ;
- écrire un gabarit Twig : variables, conditions, boucles, inclusions ;
- construire un **héritage de gabarits** avec `{% extends %}` et `{% block %}` ;
- générer des liens avec `path()` et des URL de fichiers avec `asset()` ;
- comprendre l'**échappement automatique** et pourquoi il vous protège ;
- organiser du CSS entre une bibliothèque (Bootstrap) et une charte maison.

## 2. Prérequis

- Phase 01 terminée : l'application répond sur `http://localhost:8081`.
- Savoir écrire du HTML et du CSS.
- Comprendre ce qu'est une route et un contrôleur (phase 01).

---

## 3. Concepts à apprendre

### Concept 1 — Le moteur de gabarits (Twig)

**Pourquoi ?** Écrire du HTML avec des `echo` en PHP mélange la logique et
l'affichage, devient illisible, et expose aux failles XSS dès qu'on affiche une
donnée saisie par un utilisateur.

**Comment ça fonctionne ?** Le contrôleur prépare des **données**, Twig produit
le **HTML**. Twig compile chaque gabarit en PHP une fois, puis réutilise la
version compilée : c'est rapide.

**Exemple.**

```php
// Dans le contrôleur : on prépare des données
return $this->render('home/index.html.twig', [
    'titre' => 'Mieux vivre avec les écrans',
]);
```

```twig
{# Dans le gabarit : on affiche #}
<h1>{{ titre }}</h1>
```

**Dans ce projet.** Toutes les pages sont des gabarits Twig. Règle du projet :
**aucune logique métier dans Twig** — un gabarit affiche, il ne décide pas.

---

### Concept 2 — La syntaxe Twig

Trois balises à retenir :

| Balise | Rôle | Exemple |
|---|---|---|
| `{{ … }}` | **affiche** une valeur | `{{ enfant.prenom }}` |
| `{% … %}` | **exécute** une instruction | `{% if … %}`, `{% for … %}` |
| `{# … #}` | **commente** (invisible dans le HTML) | `{# note pour l'équipe #}` |

```twig
{% if contenus is empty %}
    <p>La bibliothèque est vide.</p>
{% else %}
    <ul>
        {% for contenu in contenus %}
            <li>{{ contenu.titre }}</li>
        {% endfor %}
    </ul>
{% endif %}
```

`{{ enfant.prenom }}` essaie, dans l'ordre : la propriété publique `prenom`,
puis `getPrenom()`, puis `isPrenom()`. C'est pour cela qu'on peut écrire
`{{ enfant.avatarEmoji }}` alors que la méthode s'appelle `getAvatarEmoji()`.

---

### Concept 3 — L'héritage de gabarits

**Pourquoi ?** La barre de navigation, les polices, le pied de page sont
identiques sur toutes les pages. Les dupliquer, c'est se condamner à les
corriger vingt fois.

**Comment ça fonctionne ?** Un gabarit **parent** définit la structure et
réserve des emplacements (`{% block %}`). Un gabarit **enfant** hérite du parent
et remplit ces emplacements.

**Exemple.**

```twig
{# templates/base.html.twig — le parent #}
<!DOCTYPE html>
<html lang="fr">
<head>
    <title>{% block title %}Digi-Santé Junior{% endblock %}</title>
</head>
<body class="{% block body_class %}{% endblock %}">
    <nav>{% block menu %}{% endblock %}</nav>
    <main>{% block body %}{% endblock %}</main>
    {% block javascripts %}{% endblock %}
</body>
</html>
```

```twig
{# templates/home/index.html.twig — l'enfant #}
{% extends 'base.html.twig' %}

{% block title %}Accueil — Digi-Santé Junior{% endblock %}

{% block body %}
    <h1>Mieux vivre avec les écrans, un jour à la fois.</h1>
{% endblock %}
```

`{% extends %}` doit être la **première** instruction du fichier. Tout ce qui est
écrit en dehors d'un `{% block %}` dans un gabarit enfant est ignoré.

**Dans ce projet.** Une hiérarchie à deux niveaux :

```text
base.html.twig                  navigation, polices, flash, pied de page
    ├── parent/layout.html.twig  menu du parent      (phase 05)
    ├── enfant/layout.html.twig  menu de l'enfant    (phase 06)
    └── admin/layout.html.twig   menu de l'admin     (phase 08)
```

Les blocs exposés par `base.html.twig` sont fixés dès maintenant : `title`,
`body_class`, `navbar`, `logo`, `marque_suffixe`, `menu`, `menu_utilisateur`,
`body`, `javascripts`.

---

### Concept 4 — L'échappement automatique

**Pourquoi ?** Si un utilisateur saisit `<script>alert('vol')</script>` comme
prénom et que vous l'affichez tel quel, le navigateur **exécute** ce script :
c'est une faille XSS.

**Comment ça fonctionne ?** Twig échappe automatiquement tout ce qui passe par
`{{ … }}` : les chevrons deviennent `&lt;` et `&gt;`, le texte s'affiche sans
être exécuté.

**Exemple.**

```twig
{{ '<b>gras</b>' }}       {# affiche littéralement <b>gras</b>  #}
{{ '<b>gras</b>'|raw }}   {# affiche du texte en gras — DANGEREUX #}
```

**Dans ce projet.** `|raw` n'est utilisé qu'à un seul endroit, en phase 10, pour
envoyer au JavaScript un tableau de nombres produit par le serveur — **jamais**
une saisie d'utilisateur.

---

### Concept 5 — `path()` et `asset()`

**Pourquoi ?** Écrire `/parent/enfants/12/modifier` à la main dans vingt
gabarits, c'est vingt corrections le jour où l'URL change.

**Comment ça fonctionne ?**

- `path('nom_de_route')` génère l'URL à partir du **nom** de la route ;
- `asset('css/app.css')` génère l'URL d'un fichier de `public/`.

```twig
<a href="{{ path('app_home') }}">Accueil</a>
<a href="{{ path('parent_enfant_modifier', {id: enfant.id}) }}">Modifier</a>
<link href="{{ asset('css/app.css') }}" rel="stylesheet">
```

Si le nom de route n'existe pas, Twig lève une erreur **au rendu** : c'est une
bonne nouvelle, vous découvrez le problème tout de suite.

---

### Concept 6 — Bibliothèque CSS et charte maison

**Pourquoi ?** Bootstrap fournit une grille, des cartes, des boutons, une barre
de navigation repliable, des modales — testés et accessibles. Le réécrire serait
du temps perdu.

**Comment ça fonctionne ?** Bootstrap est chargé **par CDN** (une simple balise
`<link>`), et `public/css/app.css` ne contient que ce que Bootstrap ne fait pas :
la palette, les polices, et les composants propres au projet.

```css
:root {
    --marine: #1F3864;
    --turquoise: #0E7C7B;
    --or: #C99A2E;
}

.btn-marine { background: var(--marine); color: #fff; border-radius: 999px; }
.pastille   { border-radius: 999px; padding: .15rem .7rem; font-weight: 700; }
```

⚠️ **Piège du projet** : ne redéfinissez **jamais** la variable Bootstrap
`--bs-body-bg`. Elle sert de fond aux cartes, aux champs, aux tableaux et aux
menus déroulants : la changer repeint tout en cascade. Le fond coloré de la page
se pose sur `body`.

---

## 4. Explications avec exemples

### Rendre un gabarit depuis un contrôleur

```php
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class HomeController extends AbstractController
//                   ^^^^^^^^^^^^^^^^^^^^^^^^^ donne accès à render(), redirectToRoute()…
{
    #[Route('/', name: 'app_home', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('home/index.html.twig');
        //                    ^^^ chemin relatif au dossier templates/
    }
}
```

`render()` fait trois choses : il rend le gabarit, en fait une chaîne, et
l'emballe dans une `Response` avec le bon type de contenu.

### Une page responsive avec la grille Bootstrap

```twig
<section class="row g-3">
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body">
                <h3>Des conseils sur mesure</h3>
                <p class="texte-doux mb-0">Chaque conseil découle de ce que l'enfant a saisi.</p>
            </div>
        </div>
    </div>
</section>
```

- `row` + `col-md-4` : trois colonnes sur écran moyen, empilées sur mobile ;
- `g-3` : l'espace entre les colonnes ;
- `h-100` : cartes de même hauteur ;
- `texte-doux` : classe **maison**, définie dans `app.css`.

Le réflexe : **chercher d'abord une classe Bootstrap**, n'écrire du CSS que si
elle n'existe pas.

---

## 5. Commandes

### `docker compose exec app composer require twig symfony/asset`

- **Ce qu'elle fait** : installe Twig et le composant Asset ; Flex crée au
  passage `config/packages/twig.yaml` et le dossier `templates/`.
- **À observer** : la ligne « Configuring twig-bundle » dans la sortie.

### `docker compose exec app php bin/console lint:twig templates`

- **Ce qu'elle fait** : vérifie la syntaxe de tous les gabarits.
- **Pourquoi** : une erreur Twig ne se voit qu'au moment d'afficher la page ;
  le linter la trouve sans ouvrir le navigateur.
- **Quand** : avant de terminer la phase, et dès qu'une page blanche apparaît.
- **À observer** : `[OK] All N Twig files contain valid syntax.`

### `docker compose exec app php bin/console cache:clear`

- **Ce qu'elle fait** : vide le cache (gabarits compilés, configuration).
- **Quand** : après un changement de configuration, ou si une modification
  semble ignorée.

### `docker compose exec app php bin/console debug:twig`

- **Ce qu'elle fait** : liste les fonctions, filtres et tests Twig disponibles.
- **Quand** : « existe-t-il déjà un filtre pour ça ? » — souvent, oui.

---

## 6. Concepts Symfony de la phase

| Composant | Rôle ici |
|---|---|
| **Twig** | le moteur de gabarits |
| **TwigBundle** | l'intègre à Symfony (`render()`, `path()`, `asset()`) |
| **Asset** | génère les URL des fichiers de `public/` |
| **Routing** | `path()` s'appuie sur les noms de routes (phase 01) |

---

## 7. Architecture et organisation du code

```text
templates/
├── base.html.twig          gabarit racine : toutes les pages en héritent
├── home/
│   └── index.html.twig     la page d'accueil publique
└── _partials/              morceaux réutilisés (arrivent phase 05)

public/
├── index.php               point d'entrée (phase 01)
├── favicon.svg
└── css/
    └── app.css             charte du projet UNIQUEMENT
```

Pourquoi :

- un dossier par « espace » dans `templates/` : on retrouve une page en
  devinant son chemin ;
- `_partials/` préfixé d'un underscore : ce ne sont pas des pages entières ;
- `app.css` dans `public/` : servi tel quel, sans compilation, conformément au
  choix « pas de bundler » du projet.

---

## 8. Flux de fonctionnement

```text
Navigateur
    ↓
Route app_home
    ↓
HomeController::index()
    ↓
render('home/index.html.twig')
    ↓
Twig : index.html.twig  {% extends 'base.html.twig' %}
    ↓        remplit les blocs title, body…
base.html.twig assemble la page complète
    ↓
HTML + <link> Bootstrap (CDN) + <link> app.css
    ↓
Navigateur : le CSS est chargé, la page s'affiche
```

---

## 9. Application au projet

**Pourquoi cette phase ?** L'accueil est la vitrine : c'est la première page que
verront un enfant et un parent. Et surtout, `base.html.twig` créé ici servira
**toutes** les pages des onze phases suivantes. Une base bien pensée évite des
dizaines de corrections plus tard.

**Composants utilisés** : Twig, Asset, Routing.

**Fichiers créés** : `templates/base.html.twig`,
`templates/home/index.html.twig`, `public/css/app.css`, et la modification de
`HomeController` pour rendre un gabarit au lieu d'un texte.

**Pourquoi cette architecture ?** Les blocs de `base.html.twig` sont définis
**maintenant** parce que les trois espaces (parent, enfant, admin) s'y
brancheront sans le modifier : l'espace enfant changera `body_class` et `logo`,
l'admin changera `marque_suffixe`, chacun remplira `menu`.

**Ce qui n'est pas encore là** : les boutons « Je suis un enfant / un parent »
pointent vers `#`, car les pages de connexion arrivent en phase 04.

---

## 10. Erreurs fréquentes

**`Unable to find template "home/index.html.twig"`**
→ Chemin ou nom de fichier erroné (Twig est sensible à la casse).
→ Signe : erreur 500 avec le chemin cherché affiché.
→ Solution : vérifier le nom exact dans `templates/`.

**`Variable "titre" does not exist`**
→ La variable n'a pas été passée par le contrôleur.
→ Solution : l'ajouter au tableau de `render()`, ou utiliser
`{{ titre|default('—') }}`.

**Du HTML s'affiche en toutes lettres (`<b>gras</b>`)**
→ C'est l'échappement automatique, et c'est **voulu**.
→ Solution : ne recourez à `|raw` que pour du contenu que **vous** générez.

**Les modifications du CSS ne s'affichent pas**
→ Le navigateur a mis le fichier en cache.
→ Solution : rechargement forcé (Cmd/Ctrl + Maj + R).

**Toute la page devient blanche ou grise après un ajout dans `app.css`**
→ Vous avez sans doute redéfini `--bs-body-bg`.
→ Solution : retirez cette règle, mettez le fond sur `body`.

**Une barre de défilement horizontale apparaît sur mobile**
→ Un élément a une largeur fixe supérieure à l'écran.
→ Solution : largeurs relatives, `max-width: 100%` sur les images, et laisser la
grille Bootstrap empiler les colonnes.

---

## 11. Bonnes pratiques

- **Un gabarit affiche, il ne décide pas.** Un calcul se fait dans le contrôleur
  ou dans une méthode d'entité.
- **Bootstrap d'abord**, CSS maison ensuite : moins de code, plus de cohérence.
- **Nommez les classes maison en français**, comme le reste du projet
  (`carte-titre`, `texte-doux`, `pastille`).
- **Jamais d'URL écrite en dur** : toujours `path('nom_de_route')`.
- **Testez à 400 px de large** à chaque page : le public visé consulte souvent
  sur téléphone.
- **Vérifiez avec `lint:twig`** avant de considérer la phase terminée.

---

## 12. Exercice pratique

1. Dans `base.html.twig`, ajoutez un bloc `{% block pied_de_page %}` contenant
   l'année courante :

```twig
<footer class="text-center small py-4 texte-doux">
    {% block pied_de_page %}
        Digi-Santé Junior — {{ 'now'|date('Y') }}
    {% endblock %}
</footer>
```

2. Dans `home/index.html.twig`, redéfinissez ce bloc pour afficher un autre
   texte, et vérifiez que seule la page d'accueil change.
3. Affichez volontairement une variable inexistante (`{{ inconnue }}`) : lisez le
   message d'erreur, puis corrigez avec le filtre `default`.
4. Ajoutez une quatrième carte d'argument dans la grille et vérifiez qu'elle
   s'empile correctement sur mobile.
5. Remettez ensuite le gabarit dans l'état attendu par la phase.

Vous devez savoir expliquer ce que fait `{% extends %}` et pourquoi le contenu
écrit hors d'un bloc n'apparaît pas.

---

## 13. Scénario de test manuel

1. Ouvrir `http://localhost:8081`.
2. Vérifier que les polices, les couleurs et les boutons arrondis s'affichent.
3. Réduire la fenêtre à la largeur d'un téléphone (~400 px).
4. Vérifier que le contenu reste lisible, sans barre de défilement horizontale.
5. **Résultat attendu** : la page est habillée, responsive, et le menu se replie sur mobile.

---

## Checklist

- [ ] J'ai compris les concepts principaux
- [ ] Je comprends le rôle des fichiers créés
- [ ] Je comprends les commandes utilisées
- [ ] Je peux expliquer le fonctionnement de cette phase
- [ ] J'ai réalisé l'exercice pratique
- [ ] J'ai exécuté le scénario de test manuel
- [ ] Le résultat attendu est obtenu

### Aller plus loin

➡️ [Phase suivante](./phase-03.md)

➡️ [Phase de développement](../README.md#phase-02--gabarit-de-base-charte-graphique-et-accueil)

➡️ [Prompt Claude Code](../prompts/phase-02.md)
