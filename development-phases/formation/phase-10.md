# Formation — Phase 10 : QueryBuilder, paramètres d'URL et graphiques

## 1. Objectifs pédagogiques

À la fin de cette leçon, vous devez être capable de :

- écrire une requête avec le **QueryBuilder** de Doctrine et la placer au bon
  endroit ;
- lire des **paramètres d'URL** proprement et sans faire confiance à leur
  contenu ;
- transmettre des données **PHP → JavaScript** sans créer de faille ;
- partager un fichier JavaScript entre plusieurs pages ;
- construire une **série de dates continue**, trous compris ;
- distinguer deux réactions possibles à un accès non autorisé : **403** ou
  repli silencieux.

## 2. Prérequis

- Phases 01 à 09 terminées : journaux enregistrés, conseils opérationnels.
- Savoir écrire une méthode de repository (phases 05 et 08).
- Bases de JavaScript.

---

## 3. Concepts à apprendre

### Concept 1 — Le QueryBuilder

**Pourquoi ?** `findBy()` suffit pour « tous les enfants de ce parent ». Il ne
suffit plus pour « les journaux de cet enfant depuis 30 jours ».

**Comment ça fonctionne ?** Le QueryBuilder construit une requête **DQL**
(Doctrine Query Language) : il ressemble à du SQL, mais parle d'**entités** et de
**propriétés**, pas de tables et de colonnes.

```php
$journaux = $this->createQueryBuilder('j')      // « j » est l'alias de JournalEntree
    ->where('j.enfant = :enfant')               // j.enfant : la PROPRIÉTÉ, pas enfant_id
    ->andWhere('j.date >= :debut')
    ->setParameter('enfant', $enfant)           // on passe l'OBJET, pas son id
    ->setParameter('debut', $debut, 'date_immutable')
    ->getQuery()
    ->getResult();
```

Trois points importants :

- **jamais** de concaténation : les valeurs passent par `setParameter()`, ce qui
  interdit l'injection SQL ;
- on passe l'**objet** `$enfant` : Doctrine en extrait l'identifiant ;
- le troisième argument de `setParameter()` précise le type quand c'est une date.

**Dans ce projet.** Règle stricte : **aucun DQL dans un contrôleur**. Toute
requête vit dans un repository, avec un nom explicite en français.

---

### Concept 2 — Une série de dates continue

**Pourquoi ?** Un graphique sur 7 jours doit afficher **7 points**. Si l'enfant
n'a rien saisi mardi, le mardi doit valoir 0 — pas disparaître, ce qui
déformerait la courbe.

**Comment ça fonctionne ?** Une requête pour récupérer ce qui existe, puis une
boucle PHP pour remplir les trous.

```php
public function getGraphiqueEcran(Enfant $enfant, int $nombreJours): array
{
    $debut = new \DateTimeImmutable('today -'.($nombreJours - 1).' days');

    $journaux = /* la requête ci-dessus */;

    // On range les minutes par jour : ['2026-09-14' => 135, …]
    $minutesParJour = [];
    foreach ($journaux as $journal) {
        $minutesParJour[$journal->getDate()->format('Y-m-d')] = $journal->getTotalEcran();
    }

    $labels = [];
    $minutes = [];
    for ($i = 0; $i < $nombreJours; ++$i) {
        $jour = $debut->modify('+'.$i.' days');
        $labels[] = $jour->format('d/m');
        $minutes[] = $minutesParJour[$jour->format('Y-m-d')] ?? 0;   // ← le trou vaut 0
    }

    return ['labels' => $labels, 'minutes' => $minutes];
}
```

**Une** requête, puis du PHP simple. L'alternative — une requête par jour —
ferait 30 allers-retours en base pour afficher un mois : c'est exactement le
problème N+1 que l'on étudiera en phase 13.

À noter : `DateTimeImmutable` renvoie un **nouvel** objet à chaque `modify()`,
l'original n'est jamais altéré. C'est pour cela que le projet utilise partout des
dates immuables.

---

### Concept 3 — Lire un paramètre d'URL

**Pourquoi ?** `?enfant=3&periode=30` vient de l'utilisateur : il peut contenir
`abc`, `-1`, ou l'identifiant de l'enfant de quelqu'un d'autre.

**Comment ça fonctionne ?** `$request->query` donne accès aux paramètres, avec
des méthodes **typées** qui convertissent et sécurisent.

```php
$periode = 30 === $request->query->getInt('periode') ? 30 : 7;
```

`getInt()` renvoie `0` si le paramètre est absent ou non numérique : le code
n'échoue jamais, et toute valeur autre que 30 retombe sur 7. Une **liste
blanche**, en une ligne.

---

### Concept 4 — 403 ou repli silencieux ?

**Pourquoi ?** Deux endroits du projet traitent « un identifiant d'enfant qui ne
vous appartient pas », et ils ne réagissent pas pareil. Ce n'est pas une
incohérence : c'est un choix.

| Situation | Réaction | Pourquoi |
|---|---|---|
| `/parent/enfants/42/modifier` | **403** (voter, phase 05) | l'utilisateur a demandé une **action** précise sur un objet : le refus doit être explicite |
| `/parent?enfant=42` | on affiche **son** premier enfant | c'est un **filtre d'affichage** ; une erreur ici serait déroutante alors qu'un tableau de bord valide existe |

**Comment ça fonctionne ?** Le second cas ne cherche l'identifiant que **parmi
ses propres enfants** :

```php
$enfants = $enfantRepository->findByParent($parent);   // uniquement les siens

$enfant = $enfants[0];
foreach ($enfants as $candidat) {
    if ($candidat->getId() === $request->query->getInt('enfant')) {
        $enfant = $candidat;
    }
}
```

Aucune donnée étrangère ne peut être chargée : l'identifiant demandé n'est
comparé qu'à une liste sûre. **La sécurité vient de la construction de la
requête, pas d'un test ajouté après coup.**

---

### Concept 5 — Du PHP vers le JavaScript

**Pourquoi ?** Chart.js a besoin de deux tableaux : les étiquettes et les
valeurs. Ils sont calculés côté serveur.

**Comment ça fonctionne ?** On sérialise en JSON dans le gabarit :

```twig
<script>
    afficherGraphiqueEcran('graphiqueEcran', {{ graphique|json_encode|raw }}, {{ enfant.maxMinutesJour }}, {
        ecran: "Temps d'écran (min)",
        limite: 'Limite fixée (min)'
    });
</script>
```

`|json_encode` transforme le tableau PHP en JSON ; `|raw` empêche Twig
d'échapper les guillemets (sinon le JSON serait cassé).

⚠️ **Règle du projet** : `|raw` uniquement pour des **tableaux de nombres ou des
constantes**, jamais pour une saisie d'utilisateur. Ici, `graphique` contient des
dates formatées et des entiers produits par le serveur : aucun texte saisi.

---

### Concept 6 — Partager un fichier JavaScript

**Pourquoi ?** Le graphique s'affiche sur **deux** pages : l'accueil de l'enfant
et le tableau de bord du parent. Dupliquer 40 lignes de configuration Chart.js,
c'est deux versions qui divergeront.

**Comment ça fonctionne ?** Une fonction dans un fichier de `public/js/`, appelée
avec des paramètres différents.

```js
function afficherGraphiqueEcran(canvasId, donnees, limite, libelles) {
    new Chart(document.getElementById(canvasId), {
        type: 'line',
        data: {
            labels: donnees.labels,
            datasets: [
                { label: libelles.ecran, data: donnees.minutes, /* … */ },
                { label: libelles.limite, data: donnees.labels.map(() => limite), borderDash: [6, 6] },
            ],
        },
    });
}
```

La ligne de limite est une astuce simple : un jeu de données constant, répété
autant de fois qu'il y a de points.

**Dans ce projet.** Règle : « un fichier dans `public/js/` **seulement** s'il
sert à plusieurs pages ». Le JavaScript du journal (phase 07) est resté dans sa
page ; celui-ci mérite son fichier.

---

### Concept 7 — Le calcul, côté PHP ou côté Twig ?

**Pourquoi ?** Le pourcentage de la jauge pourrait s'écrire dans le gabarit.

**Comment ça fonctionne ?** Règle du projet : **pas de logique métier dans
Twig**. Le calcul se fait dans le contrôleur, ou mieux, dans une méthode
d'entité déjà existante.

```php
// dans le contrôleur
'pourcentage' => min(100, (int) round($totalEcran / $limite * 100)),
'niveau' => JournalEntree::niveauPourMinutes($totalEcran),   // méthode d'entité, phase 07
```

```twig
<div class="progress-bar niveau-{{ niveau }}" style="width: {{ pourcentage }}%"></div>
```

Le gabarit ne fait qu'**afficher**. Bonus : `niveauPourMinutes()` est la même
méthode que celle utilisée par le journal — un seul barème dans tout le projet.

---

## 4. Explications avec exemples

### Le tableau de bord, action complète

```php
#[Route('/parent', name: 'parent_dashboard', methods: ['GET'])]
public function index(
    #[CurrentUser] User $parent,
    Request $request,
    EnfantRepository $enfantRepository,
    JournalEntreeRepository $journalRepository,
    ConseilService $conseilService,          // le service de la phase 09
): Response {
    $enfants = $enfantRepository->findByParent($parent);

    if ([] === $enfants) {
        return $this->render('parent/dashboard_vide.html.twig');   // écran dédié
    }

    // … sélection de l'enfant (voir Concept 4)

    $periode = 30 === $request->query->getInt('periode') ? 30 : 7;
    $journalDuJour = $journalRepository->findAujourdhui($enfant);

    return $this->render('parent/dashboard.html.twig', [
        'enfants' => $enfants,
        'enfant' => $enfant,
        'journalDuJour' => $journalDuJour,
        // Les mêmes conseils que ceux reçus par l'enfant
        'conseils' => $journalDuJour ? $conseilService->getConseils($journalDuJour) : [],
        'periode' => $periode,
        'graphique' => $journalRepository->getGraphiqueEcran($enfant, $periode),
    ]);
}
```

Le contrôleur reste **simple** : il lit la requête, appelle des repositories et
un service, rend un gabarit. Aucun calcul métier, aucune requête écrite ici.

Notez aussi l'écran dédié quand le parent n'a pas encore d'enfant : un tableau de
bord vide serait une impasse. Traiter le **cas zéro** fait partie du travail.

### L'état vide, en Twig

```twig
{% for douleur in journalDuJour.douleurs %}
    <span class="pastille">{{ douleur.zoneEmoji }} {{ douleur.zoneLabel }} · {{ douleur.intensite }}/5</span>
{% else %}
    <p class="fw-bold texte-vert mb-0">🌟 Aucune douleur signalée aujourd'hui.</p>
{% endfor %}
```

Le `{% else %}` d'une boucle `for` (déjà croisé en phase 08) évite un `{% if %}`
supplémentaire. Et l'absence de douleur est présentée comme une **bonne
nouvelle**, pas comme un vide.

---

## 5. Commandes

### `docker compose exec app php bin/console dbal:run-sql "SELECT date, ecran_tv FROM journal_entree ORDER BY date DESC LIMIT 5"`

- **Ce qu'elle fait** : montre les données brutes.
- **Quand** : « le graphique est plat » — vérifiez d'abord qu'il y a des données.

### La barre de debug, onglet **Doctrine**

- **Ce qu'elle fait** : liste **toutes** les requêtes SQL de la page, avec leur
  durée.
- **À observer** : le tableau de bord doit exécuter une poignée de requêtes. Si
  vous en voyez trente, c'est un problème N+1 (phase 13).
- **Astuce** : cliquez sur « Explain » pour voir la requête réellement envoyée à
  MySQL.

### La console du navigateur (F12)

- **Quand** : le graphique ne s'affiche pas.
- **À observer** : `Chart is not defined` (le CDN n'est pas chargé, ou chargé
  **après** votre script), ou une erreur JSON (le `|raw` manque).

### `docker compose exec app php bin/console lint:twig templates`

- **Quand** : après avoir ajouté les blocs `javascripts` dans les deux pages.

---

## 6. Concepts Symfony de la phase

| Composant | Rôle ici |
|---|---|
| **Doctrine (QueryBuilder)** | requêtes sur mesure dans les repositories |
| **HttpFoundation** | `$request->query->getInt()` |
| **Twig** | `json_encode`, `asset()`, bloc `javascripts` |
| **DependencyInjection** | `ConseilService` injecté dans un second contrôleur |

---

## 7. Architecture et organisation du code

```text
src/
├── Controller/Parent/
│   └── TableauDeBordController.php    la page /parent
└── Repository/
    └── JournalEntreeRepository.php    + getGraphiqueEcran()

templates/parent/
├── dashboard.html.twig                sélecteur, journal du jour, conseils, graphique
└── dashboard_vide.html.twig           aucun enfant : invitation à en créer un

public/js/
└── graphique-ecran.js                 PARTAGÉ entre parent et enfant
```

Pourquoi `dashboard_vide.html.twig` séparé : deux situations très différentes,
deux gabarits. Un seul fichier truffé de `{% if %}` serait plus difficile à lire
qu'un aiguillage explicite dans le contrôleur.

---

## 8. Flux de fonctionnement

```text
Parent : GET /parent?enfant=3&periode=30
    ↓
access_control : ROLE_PARENT
    ↓
EnfantRepository::findByParent()      ← uniquement SES enfants
    ↓  liste vide ? → écran dédié
Sélection de l'enfant demandé parmi cette liste sûre
    ↓
JournalEntreeRepository::findAujourdhui()   → le journal du jour
ConseilService::getConseils()               → les mêmes conseils que l'enfant
JournalEntreeRepository::getGraphiqueEcran() → labels + minutes (trous à 0)
    ↓
dashboard.html.twig
    ↓  {{ graphique|json_encode|raw }}
graphique-ecran.js + Chart.js (CDN)
    ↓
Courbe + ligne de limite en pointillés
```

---

## 9. Application au projet

**Pourquoi cette phase ?** Le parent a créé les comptes (phase 05) mais ne voyait
encore rien. C'est ici qu'il obtient ce pour quoi il est venu : **comprendre
l'évolution** de son enfant.

**Composants utilisés** : Doctrine (QueryBuilder), HttpFoundation, Twig,
injection de dépendances.

**Fichiers créés** : voir l'arborescence.

**Pourquoi cette architecture ?**

- **Les mêmes conseils pour le parent et l'enfant**, via le service : la
  cohérence est garantie par le code, pas par la vigilance.
- **Un seul fichier JavaScript** pour deux pages, avec des libellés en
  paramètres : l'enfant lit « Mon temps d'écran », le parent « Temps d'écran
  (min) ». Même code, deux voix.
- **Les trous à zéro** : un graphique honnête montre aussi les jours sans saisie.
- **Chart.js par CDN** : cohérent avec le choix « pas de bundler » du projet.

**Ce qui n'est pas là** : l'historique des douleurs sur plusieurs jours. Seules
celles du jour s'affichent — c'est une limite connue, notée comme évolution
possible dans le cahier des charges.

---

## 10. Erreurs fréquentes

**Le graphique reste vide, mais la page s'affiche**
→ Regardez la console : `Chart is not defined` signifie que le script du CDN est
chargé **après** votre appel.
→ Solution : charger Chart.js, puis `graphique-ecran.js`, puis votre appel.

**`SyntaxError: Unexpected token &` en JavaScript**
→ Le `|raw` manque : Twig a échappé les guillemets du JSON.

**La courbe n'a que 3 points au lieu de 7**
→ La boucle de remplissage des trous manque ; seuls les jours existants sont
envoyés.

**Tous les points valent 0**
→ Aucun journal sur la période, ou comparaison de dates incorrecte.
→ Vérification : la requête SQL de la section Commandes.

**`Invalid parameter type` sur la date**
→ Doctrine ne sait pas convertir l'objet date.
→ Solution : `->setParameter('debut', $debut, 'date_immutable')`.

**Le tableau de bord exécute 30 requêtes**
→ Les journaux sont chargés un par un.
→ Solution : une seule requête sur la période, remplissage en PHP.

**`?enfant=999` provoque une erreur**
→ L'identifiant est cherché en base au lieu d'être comparé à la liste des
enfants du parent.
→ Solution : Concept 4.

---

## 11. Bonnes pratiques

- **Aucune requête dans un contrôleur** : tout dans un repository, avec un nom
  explicite.
- **Toujours `setParameter()`**, jamais de concaténation dans une requête.
- **Ne faites jamais confiance à un paramètre d'URL** : `getInt()` + liste
  blanche, et ne cherchez que dans des données déjà filtrées.
- **Traitez le cas zéro** (aucun enfant, aucun journal, aucune douleur) : c'est
  souvent le premier état que verra un vrai utilisateur.
- **`|raw` seulement pour des données produites par le serveur.**
- **Partagez un fichier JS uniquement s'il sert à plusieurs pages.**
- **Surveillez l'onglet Doctrine du profiler** dès qu'une page affiche une liste.

---

## 12. Exercice pratique

1. Ouvrez le tableau de bord et notez le nombre de requêtes SQL dans l'onglet
   Doctrine du profiler. Passez de 7 à 30 jours : ce nombre doit rester
   **identique** (la période ne change pas le nombre de requêtes).
2. Supprimez le journal d'hier en SQL, rechargez le graphique : le point doit
   tomber à 0, pas disparaître. Quelle ligne produit ce comportement ?
3. Essayez `?periode=abc`, puis `?periode=365` : dans les deux cas, la période
   doit retomber à 7 jours. Expliquez pourquoi.
4. Essayez `?enfant=999` : vous devez voir votre premier enfant, sans erreur.
   Comparez avec `/parent/enfants/999/modifier`, qui renvoie 403 (ou 404).
   Expliquez la différence de traitement.
5. Dans la console du navigateur, tapez `donnees` — il n'existe pas. Ajoutez
   temporairement `console.log({{ graphique|json_encode|raw }})` dans le gabarit
   pour observer la structure exacte transmise, puis retirez-le.

---

## 13. Scénario de test manuel

1. Se connecter en parent et ouvrir `/parent`.
2. Vérifier le temps d'écran du jour, la jauge colorée et les douleurs signalées.
3. Basculer sur « 30 derniers jours » et vérifier que la courbe change.
4. Modifier l'URL avec l'identifiant d'un enfant qui ne vous appartient pas (`/parent?enfant=999`).
5. **Résultat attendu** : les deux périodes s'affichent avec la ligne de limite en pointillés, et l'identifiant étranger affiche simplement votre premier enfant.

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

➡️ [Phase suivante](./phase-11.md)

➡️ [Phase de développement](../README.md#phase-10--tableau-de-bord-parent-et-graphiques)

➡️ [Prompt Claude Code](../prompts/phase-10.md)
