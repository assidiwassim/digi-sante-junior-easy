# Formation — Phase 08 : CRUD d'administration et bibliothèque de contenus

## 1. Objectifs pédagogiques

À la fin de cette leçon, vous devez être capable de :

- construire un **CRUD complet** (lister, créer, modifier, supprimer) en Symfony ;
- laisser Symfony charger une entité à partir de l'URL (**résolution d'entité**) ;
- réutiliser **un seul gabarit de formulaire** pour la création et l'édition ;
- choisir le bon type de champ : `ChoiceType`, `TextareaType`, `UrlType` ;
- expliquer pourquoi les **données métier vivent en base**, pas dans le code ;
- trier et regrouper des résultats dans un **repository**.

## 2. Prérequis

- Phases 01 à 07 terminées.
- Savoir créer un formulaire (phases 04 et 05).
- Connaître l'entité `WellnessContent`, ses constantes (`TYPES`,
  `TRIGGERS`) et ses contraintes : elle existe **depuis la phase 02**
  ([leçon 02](./phase-02.md)). Relisez `src/Entity/WellnessContent.php` avant de
  commencer : cette phase ne modifie aucune entité.
- Un compte `ROLE_ADMIN` existe en base.

---

## 3. Concepts à apprendre

### Concept 1 — Le CRUD

**Pourquoi ?** Quatre opérations reviennent dans toute application de gestion :
**C**reate, **R**ead, **U**pdate, **D**elete. Les connaître comme un modèle
permet d'écrire n'importe quel écran d'administration sans réfléchir à la
structure.

**Comment ça fonctionne ?** Quatre routes, quatre actions :

| Action | Route | Méthode HTTP |
|---|---|---|
| Lister | `/admin/contents` | GET |
| Créer | `/admin/contents/new` | GET + POST |
| Modifier | `/admin/contents/{id}/edit` | GET + POST |
| Supprimer | `/admin/contents/{id}/delete` | **POST uniquement** |

⚠️ La suppression n'est **jamais** en GET : un lien GET peut être déclenché par
un robot d'indexation, un préchargement de navigateur ou une image piégée.

---

### Concept 2 — La résolution d'entité par l'URL

**Pourquoi ?** Écrire à chaque action « récupérer l'id, chercher en base,
vérifier que le résultat n'est pas nul, sinon 404 » est répétitif.

**Comment ça fonctionne ?** Si un argument de l'action est typé avec une entité
et que la route contient `{id}`, Symfony fait la recherche pour vous — et lève
une 404 si rien n'est trouvé.

```php
#[Route('/contenus/{id}/edit', name: 'admin_content_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
public function modifier(WellnessContent $content, Request $request, EntityManagerInterface $entityManager): Response
//                       ^^^^^^^^^^^^^^^^^^^^^^^^ loaded automatically from {id}
```

`requirements: ['id' => '\d+']` restreint le paramètre aux chiffres : `/admin/contents/abc/edit`
ne correspond alors à aucune route (404 propre) au lieu de provoquer une erreur
de base de données. C'est une **convention du projet** : toujours l'ajouter.

---

### Concept 3 — Un gabarit de formulaire pour deux actions

**Pourquoi ?** Le formulaire de création et celui de modification sont
identiques. Deux fichiers, c'est deux fois les corrections.

**Comment ça fonctionne ?** Le même gabarit, avec un titre et un libellé de
bouton passés en variables.

```php
return $this->render('admin/contents/form.html.twig', [
    'form' => $form,
    'pageTitle' => 'Nouveau contenu',
    'bouton' => 'Ajouter le contenu',
]);
```

La seule différence de code entre les deux actions : la création fait
`persist()` avant `flush()`, la modification se contente de `flush()` (l'objet
vient de la base, Doctrine le suit déjà).

---

### Concept 4 — Les types de champs

**Pourquoi ?** Le bon type apporte le bon rendu HTML, la bonne validation et la
bonne conversion, gratuitement.

| Type | Rendu | Utilisé ici pour |
|---|---|---|
| `TextType` | `<input type="text">` | le titre |
| `TextareaType` | `<textarea>` | le contenu, sur plusieurs lignes |
| `UrlType` | `<input type="url">` | le lien externe |
| `ChoiceType` | `<select>` ou boutons radio | le type, la règle déclencheuse |

**Construire une liste de choix à partir d'une constante :**

```php
$types = [];
foreach (WellnessContent::TYPES as $key => $type) {
    $types[$type['emoji'].' '.$type['label']] = $key;   // label => value
}

$builder->add('type', ChoiceType::class, ['label' => 'Type', 'choices' => $types]);
```

⚠️ Dans `choices`, la **clé** est ce que voit l'utilisateur, la **valeur** est ce
qui est enregistré. C'est contre-intuitif la première fois.

Options utiles : `'required' => false` (champ facultatif), `'placeholder'`
(première ligne du `<select>`), `'help'` (texte d'aide sous le champ),
`'default_protocol' => 'https'` (ajoute `https://` si l'utilisateur l'oublie).

Côté entité, le lien est déjà validé par `Assert\Url`, posé en phase 02
(rappel) :

```php
#[Assert\Url(
    message: 'Merci de saisir une URL valide.',
    requireTld: true,
    tldMessage: 'Il manque le domaine du site (par exemple .fr ou .com).',
)]
private ?string $url = null;
```

`requireTld: true` est **obligatoire** : l'omettre est déprécié depuis
Symfony 7.1, et sans lui le `tldMessage` n'est jamais utilisé. C'est ici, avec
le formulaire, que ces messages apparaissent enfin à l'écran.

---

### Concept 5 — Les données métier en base

**Pourquoi ?** Les textes des fiches, des exercices et des quiz doivent pouvoir
être corrigés par un administrateur, sans développeur, sans déploiement.

**Comment ça fonctionne ?** Ils sont stockés dans une table, éditables par une
interface. Le code ne contient que la **structure**, pas le **contenu**.

**Dans ce projet.** C'est une règle explicite : « les données métier (textes des
conseils, contenus) sont en base, pas dans le code ni les gabarits ». La phase 09
ira chercher ces textes pour les afficher dans les conseils.

---

### Concept 6 — Le lien entre un contenu et une règle

**Pourquoi ?** Quand l'enfant déclenche la règle « yoga des yeux », il faut lui
proposer **le** contenu correspondant.

**Comment ça fonctionne ?** La colonne `triggerRule` (phase 02) porte la clé de
la règle. Le moteur de conseils (phase 09) cherchera le premier contenu portant
cette clé. Dans cette phase, on se contente de proposer la liste
`WellnessContent::TRIGGERS` dans un `ChoiceType` facultatif, construit comme
celle des types (Concept 4) :

```php
$builder->add('triggerRule', ChoiceType::class, [
    'required' => false,
    'placeholder' => 'Aucune — visible seulement dans la bibliothèque',
    'choices' => array_flip(WellnessContent::TRIGGERS),   // label => key
    'help' => 'Le premier contenu d\'une règle est celui proposé à l\'enfant quand elle se déclenche.',
]);
```

Les trois constantes (`20-20-20`, `neck_stretching`, `eye_yoga`) ont été
définies en phase 02 ([leçon 02](./phase-02.md)).

⚠️ **Leçon apprise sur ce projet** : la liste contenait autrefois des règles
supplémentaires (« Défi sport », « Sommeil », « Posture ») qu'aucun code ne
déclenchait jamais. Résultat : un administrateur pouvait rattacher un contenu à
une règle morte, et ce contenu n'était jamais proposé à un enfant. C'est pour
cela que `TRIGGERS` n'en compte plus que trois. **Ne proposez jamais une
option qui ne produit aucun effet.**

---

### Concept 7 — Trier et regrouper dans le repository

**Pourquoi ?** L'administrateur veut une liste triée par type puis par titre ;
l'enfant veut les contenus **regroupés** par type.

**Comment ça fonctionne ?**

```php
public function findAllSorted(): array
{
    $contents = $this->findBy([], ['title' => \SortDirection::Ascending]);

    // The type is sorted on its French label (Exercice, Fiche…), not on its
    // English code. usort() keeps the title order inside each type.
    usort($contents, fn (WellnessContent $a, WellnessContent $b) => strcmp($a->getTypeLabel(), $b->getTypeLabel()));

    return $contents;
}

public function findGroupedByType(): array
{
    $groups = [];

    foreach (array_keys(WellnessContent::TYPES) as $type) {
        $contents = $this->findBy(['type' => $type], ['title' => \SortDirection::Ascending]);

        if ([] !== $contents) {
            $groups[$type] = $contents;     // only non-empty types are added
        }
    }

    return $groups;
}
```

⚠️ **Piège du projet** : passer `'ASC'` ou `'DESC'` en **chaîne** à
`QueryBuilder::orderBy()` ou à l'attribut `#[ORM\OrderBy]` est déprécié dans
Doctrine ORM 3 (`findBy()` accepte encore les chaînes, mais la règle du projet
est de ne les utiliser nulle part). On utilise `\SortDirection::Ascending` /
`Descending`, ou on ne met rien (croissant par défaut). La barre de debug
signale chaque dépréciation (icône jaune) : ce n'est pas un détail cosmétique.

Regrouper en PHP est ici parfaitement acceptable : la bibliothèque contient
quelques dizaines de lignes, et le code reste lisible.

---

## 4. Explications avec exemples

### Une action de création, de bout en bout

```php
#[Route('/contenus/new', name: 'admin_content_new', methods: ['GET', 'POST'])]
public function nouveau(Request $request, EntityManagerInterface $entityManager): Response
{
    $content = new WellnessContent();

    $form = $this->createForm(WellnessContentType::class, $content);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $entityManager->persist($content);
        $entityManager->flush();

        $this->addFlash('success', sprintf('Le contenu « %s » a été ajouté.', $content->getTitle()));

        return $this->redirectToRoute('admin_contents');
    }

    return $this->render('admin/contents/form.html.twig', [
        'form' => $form,
        'pageTitle' => 'Nouveau contenu',
        'bouton' => 'Ajouter le contenu',
    ]);
}
```

Le **même** `return render()` sert à l'affichage initial (GET) **et** au
réaffichage après une erreur (POST invalide), erreurs comprises. C'est le patron
standard d'un formulaire Symfony ; on l'a déjà vu en phases 04, 05 et 07.

### La suppression

```php
#[Route('/contenus/{id}/delete', name: 'admin_content_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
public function supprimer(WellnessContent $content, Request $request, EntityManagerInterface $entityManager): Response
{
    if (!$this->isCsrfTokenValid('supprimer-contenu-'.$content->getId(), $request->getPayload()->getString('_token'))) {
        throw $this->createAccessDeniedException('Jeton CSRF invalide.');
    }

    $entityManager->remove($content);
    $entityManager->flush();

    $this->addFlash('success', sprintf('Le contenu « %s » a été supprimé.', $content->getTitle()));

    return $this->redirectToRoute('admin_contents');
}
```

Trois protections empilées : `methods: ['POST']`, le jeton CSRF (avec
l'identifiant dedans), et le `confirm()` du navigateur dans le partiel. On
réutilise le partiel `_partials/delete_button.html.twig` écrit en phase 05 :
un seul endroit, trois espaces.

### L'affichage groupé, côté enfant

Le contrôleur ne passe que les groupes : l'emoji et le libellé du type se
lisent sur le premier contenu du groupe (getters définis en phase 02).

```php
return $this->render('child/library.html.twig', [
    'groups' => $contentRepository->findGroupedByType(),
]);
```

```twig
{% for type, contents in groups %}
    {% set first = contents|first %}
    <section>
        <h2>
            <span>{{ premier.typeEmoji }}</span>
            {{ premier.typeLabel }}{{ contents|length > 1 ? 's' }}
            <span class="chip">{{ contents|length }}</span>
        </h2>

        {% for content in contents %}
            <article class="card card-child h-100">
                <h3>{{ content.title }}</h3>
                <p class="mt-2 mb-0">{{ content.body|nl2br }}</p>
                {% if content.url %}
                    <a href="{{ content.url }}" target="_blank" rel="noopener" class="btn btn-gold btn-sm">▶️ Ouvrir le lien</a>
                {% endif %}
            </article>
        {% endfor %}
    </section>
{% else %}
    <p>La bibliothèque est vide pour l'instant.</p>
{% endfor %}
```

Trois points à retenir :

- `{% else %}` dans une boucle `for` s'exécute si la collection est **vide** :
  pas besoin d'un `{% if %}` supplémentaire ;
- `rel="noopener"` sur un lien `target="_blank"` empêche la page ouverte
  d'accéder à la vôtre — règle de sécurité du projet ;
- le filtre `|nl2br` **échappe** d'abord le texte, puis convertit les retours à
  la ligne saisis par l'administrateur en `<br>` : ils sont conservés **sans**
  interpréter le HTML saisi.

---

## 5. Commandes

### `docker compose exec app php bin/console make:form WellnessContentType`

- **Ce qu'elle fait** : génère un `*Type` pré-rempli à partir de l'entité.
- **À observer** : l'entité existe depuis la phase 02, le générateur s'en sert
  pour proposer les champs. Le code généré est un point de départ. Les libellés, les aides
  et les listes de choix sont à écrire à la main.

### `docker compose exec app php bin/console debug:router | grep admin`

- **Quand** : vérifier les quatre routes du CRUD et leurs méthodes HTTP.
- **À observer** : la route de suppression doit être en **POST** seul, et
  `admin_home` ne doit apparaître **qu'une fois** (page d'attente de la
  phase 04 supprimée).

### `docker compose exec app php bin/console dbal:run-sql "UPDATE users SET roles = '[\"ROLE_ADMIN\"]' WHERE email = 'admin@digisante.local'"`

- **Ce qu'elle fait** : promeut un compte en administrateur.
- **Pourquoi** : aucune interface ne crée d'administrateur — c'est volontaire.

### `docker compose exec app php bin/console lint:twig templates`

- **Quand** : après avoir créé le layout admin et les gabarits du CRUD.

---

## 6. Concepts Symfony de la phase

| Composant | Rôle ici |
|---|---|
| **Routing** | `{id}`, `requirements`, `methods` |
| **Doctrine** | entité `WellnessContent` (phase 02), requêtes du repository, résolution par l'URL |
| **Form** | `ChoiceType`, `TextareaType`, `UrlType`, `help`, `placeholder` |
| **Validator** | contraintes de la phase 02 : titre et contenu obligatoires, URL valide |
| **Security** | `access_control` sur `^/admin` (déjà en place) |
| **Twig** | boucle avec `{% else %}`, partiel de suppression réutilisé |

---

## 7. Architecture et organisation du code

```text
src/
├── Controller/Admin/
│   ├── AccueilController.php        ❌ supprimé (page d'attente de la phase 04)
│   └── ContentController.php        admin_home + les 4 actions du CRUD
├── Form/
│   └── WellnessContentType.php
└── Repository/
    └── WellnessContentRepository.php  + findAllSorted(), findGroupedByType()
                                       (fichier de la phase 02)

templates/
├── admin/
│   ├── layout.html.twig             menu de l'administration
│   └── contents/
│       ├── index.html.twig          le tableau
│       └── form.html.twig           création ET modification
└── child/
    └── library.html.twig            la page « Découvrir »
```

L'entité `WellnessContent` n'apparaît pas : elle existe depuis la phase 02.

`ContentController` porte désormais la route `admin_home` (`/admin`), qui
redirige vers `admin_contents`. La page d'attente `Admin\HomeController` de
la phase 04 et son gabarit sont donc **supprimés** : sinon deux routes portent
le même nom, et la dernière déclarée gagne sans prévenir.

Une même entité sert deux publics très différents : un tableau dense pour
l'administrateur, des cartes colorées pour l'enfant. Les **données** sont
communes, la **présentation** ne l'est pas — c'est exactement le rôle des
gabarits.

---

## 8. Flux de fonctionnement

### Création d'un contenu

```text
Admin : GET /admin/contents/new
    ↓ access_control : ROLE_ADMIN
ContentController::nouveau() → formulaire vide
    ↓ POST
Validator : titre, contenu, URL
    ↓ valide                    ↓ invalide
persist + flush                réaffichage avec les erreurs (HTTP 422)
    ↓
flash + redirection vers la liste
```

### Affichage côté enfant

```text
Child : GET /child/library
    ↓ access_control : ROLE_CHILD
Child\HomeController::library()
    ↓
WellnessContentRepository::findGroupedByType()
    ↓
['sheet' => [...], 'video' => [...]]
    ↓
library.html.twig : une section par type
```

---

## 9. Application au projet

**Pourquoi cette phase ?** Elle ouvre le troisième espace (l'administration) et
alimente la bibliothèque qui servira au moteur de conseils en phase 09. Sans
contenus en base, les conseils n'auraient rien à proposer.

**Composants utilisés** : Routing, Doctrine, Form, Validator, Twig, Security.

**Fichiers créés** : voir l'arborescence.

**Pourquoi cette architecture ?**

- **Le même CRUD que partout ailleurs** : liste, formulaire partagé, suppression
  POST + CSRF. Un développeur qui arrive sur le projet reconnaît immédiatement le
  motif.
- **Trois déclencheurs seulement**, ceux qui produisent réellement un effet.
- **Le premier contenu d'une règle** (le plus petit identifiant) est celui qui
  sera proposé : simple à expliquer à un administrateur. Il n'y a pas de
  réordonnancement : pour changer, on modifie ce contenu ou on le rattache à
  une autre règle.
- **L'administrateur ne gère pas les enfants** : ces profils relèvent de leur
  parent. L'espace admin ne contient donc que les contenus et, en phase 11, les
  comptes parents.

---

## 10. Erreurs fréquentes

**404 sur `/admin/contents/5/edit` alors que le contenu existe**
→ Mauvais identifiant, ou entité supprimée.
→ Vérification : `SELECT id FROM wellness_content`.

**Erreur 500 avec `/admin/contents/abc/edit`**
→ Il manque `requirements: ['id' => '\d+']`.
→ Avec, la route ne correspond simplement pas : 404 propre.

**Le `<select>` enregistre le libellé au lieu de la clé**
→ Le tableau `choices` est inversé.
→ Rappel : `['Libellé affiché' => 'valeur_en_base']`.

**Une URL sans domaine est acceptée ou refusée avec un message technique**
→ `Assert\Url` a plusieurs messages : celui de l'URL invalide, et celui du
domaine manquant (`tldMessage`).
→ Solution : vérifier sur l'entité (phase 02) que les deux sont renseignés, en
français, et que `requireTld: true` est présent (sans lui, `tldMessage` n'est
jamais utilisé).

**Les retours à la ligne du contenu disparaissent à l'affichage**
→ Le HTML ignore les retours à la ligne.
→ Solution : le filtre `|nl2br`, qui échappe le texte puis convertit les retours
à la ligne — jamais `|raw`, qui exécuterait le HTML saisi.

**La suppression renvoie 405 (Method Not Allowed)**
→ Un lien GET a été utilisé au lieu d'un formulaire POST.

**Un contenu rattaché à une règle n'apparaît nulle part dans les conseils**
→ La règle n'est déclenchée par aucun code (voir Concept 6), ou la phase 09 n'est
pas encore faite.

---

## 11. Bonnes pratiques

- **Suppression toujours en POST**, avec jeton CSRF et confirmation.
- **Un seul gabarit de formulaire** pour créer et modifier.
- **Ne proposez jamais une option sans effet** dans une liste de choix.
- **Les textes métier vont en base**, jamais dans un gabarit.
- **Messages flash après chaque action** : l'administrateur doit savoir ce qui
  s'est passé.
- **Reprenez les conventions déjà en place** (noms de routes préfixés, partiel de
  suppression, layout par espace) plutôt que d'inventer une variante.
- **`rel="noopener"` sur tout lien `target="_blank"`.**

---

## 12. Exercice pratique

1. Créez un contenu de chaque type et vérifiez, côté enfant, que les groupes
   apparaissent **dans l'ordre de la constante `TYPES`**, et non par ordre de
   création.
2. Créez un contenu avec un texte sur plusieurs paragraphes : vérifiez que les
   retours à la ligne sont conservés côté enfant. Retirez temporairement le
   filtre `|nl2br` pour voir la différence.
3. Saisissez `exemple` dans le champ lien : lisez le message d'erreur. Puis
   `https://exemple.fr` : il doit être accepté.
4. Ouvrez `/admin/contents/99999/edit` (identifiant inexistant) : vous devez
   obtenir une 404, pas une erreur 500. Expliquez **qui** a produit cette 404.
5. Supprimez tous vos contenus de test et vérifiez que la page enfant affiche le
   message « bibliothèque vide » — c'est le `{% else %}` de la boucle.

---

## 13. Scénario de test manuel

1. Se connecter avec le compte administrateur et ouvrir `/admin/contents`.
2. Créer un contenu de type « Fiche », avec un titre, un texte et un lien.
3. Ouvrir une **fenêtre de navigation privée** (seconde session, l'admin reste connecté dans la première), s'y connecter en enfant et ouvrir « 📚 Découvrir ».
4. Dans la fenêtre admin, modifier le titre du contenu, puis rafraîchir la page enfant dans la fenêtre privée.
5. **Résultat attendu** : le contenu apparaît côté enfant dans le bon groupe, et le titre modifié s'affiche après rafraîchissement.

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

⬅️ [Phase précédente](./phase-07.md)

➡️ [Phase suivante](./phase-09.md)

➡️ [Phase de développement](../README.md#phase-08--bibliothèque-de-contenus-admin--enfant)

➡️ [Prompt Claude Code](../prompts/phase-08.md)
