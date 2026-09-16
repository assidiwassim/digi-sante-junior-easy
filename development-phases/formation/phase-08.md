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
- Savoir créer une entité, une migration, un formulaire (phases 03, 04, 05).
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
| Lister | `/admin/contenus` | GET |
| Créer | `/admin/contenus/nouveau` | GET + POST |
| Modifier | `/admin/contenus/{id}/modifier` | GET + POST |
| Supprimer | `/admin/contenus/{id}/supprimer` | **POST uniquement** |

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
#[Route('/contenus/{id}/modifier', name: 'admin_contenu_modifier', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
public function modifier(ContenuBienEtre $contenu, Request $request, EntityManagerInterface $entityManager): Response
//                       ^^^^^^^^^^^^^^^^^^^^^^^^ chargé automatiquement depuis {id}
```

`requirements: ['id' => '\d+']` restreint le paramètre aux chiffres : `/admin/contenus/abc/modifier`
ne correspond alors à aucune route (404 propre) au lieu de provoquer une erreur
de base de données. C'est une **convention du projet** : toujours l'ajouter.

---

### Concept 3 — Un gabarit de formulaire pour deux actions

**Pourquoi ?** Le formulaire de création et celui de modification sont
identiques. Deux fichiers, c'est deux fois les corrections.

**Comment ça fonctionne ?** Le même gabarit, avec un titre et un libellé de
bouton passés en variables.

```php
return $this->render('admin/contenus/formulaire.html.twig', [
    'form' => $form,
    'titrePage' => 'Nouveau contenu',
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
foreach (ContenuBienEtre::TYPES as $cle => $type) {
    $types[$type['emoji'].' '.$type['label']] = $cle;   // libellé => valeur
}

$builder->add('type', ChoiceType::class, ['label' => 'Type', 'choices' => $types]);
```

⚠️ Dans `choices`, la **clé** est ce que voit l'utilisateur, la **valeur** est ce
qui est enregistré. C'est contre-intuitif la première fois.

Options utiles : `'required' => false` (champ facultatif), `'placeholder'`
(première ligne du `<select>`), `'help'` (texte d'aide sous le champ),
`'default_protocol' => 'https'` (ajoute `https://` si l'utilisateur l'oublie).

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

**Comment ça fonctionne ?** Une colonne `declencheur` porte la clé de la règle.
Le moteur de conseils (phase 09) cherchera le premier contenu portant cette clé.

```php
public const DECLENCHEUR_20_20_20 = '20-20-20';
public const DECLENCHEUR_ETIREMENT = 'etirement_cervical';
public const DECLENCHEUR_YOGA_YEUX = 'yoga_yeux';

public const DECLENCHEURS = [
    self::DECLENCHEUR_20_20_20 => 'Règle du 20-20-20',
    self::DECLENCHEUR_ETIREMENT => 'Étirements du cou',
    self::DECLENCHEUR_YOGA_YEUX => 'Yoga des yeux',
];
```

⚠️ **Leçon apprise sur ce projet** : la liste contenait autrefois des règles
supplémentaires (« Défi sport », « Sommeil », « Posture ») qu'aucun code ne
déclenchait jamais. Résultat : un administrateur pouvait rattacher un contenu à
une règle morte, et ce contenu n'était jamais proposé à un enfant. **Ne proposez
jamais une option qui ne produit aucun effet.**

---

### Concept 7 — Trier et regrouper dans le repository

**Pourquoi ?** L'administrateur veut une liste triée par type puis par titre ;
l'enfant veut les contenus **regroupés** par type.

**Comment ça fonctionne ?**

```php
public function findTousTries(): array
{
    return $this->createQueryBuilder('c')
        ->orderBy('c.type')          // croissant par défaut
        ->addOrderBy('c.titre')
        ->getQuery()
        ->getResult();
}

public function findGroupesParType(): array
{
    $groupes = [];

    foreach (array_keys(ContenuBienEtre::TYPES) as $type) {
        $contenus = $this->findBy(['type' => $type], ['titre' => \SortDirection::Ascending]);

        if ([] !== $contenus) {
            $groupes[$type] = $contenus;     // on n'ajoute que les types non vides
        }
    }

    return $groupes;
}
```

⚠️ **Piège du projet** : passer `'ASC'` ou `'DESC'` en **chaîne** est déprécié
dans Doctrine ORM 3. On utilise `\SortDirection::Ascending` / `Descending`, ou on
ne met rien (croissant par défaut). Les tests du projet échouent sur les
dépréciations : ce n'est pas un détail cosmétique.

Regrouper en PHP est ici parfaitement acceptable : la bibliothèque contient
quelques dizaines de lignes, et le code reste lisible.

---

## 4. Explications avec exemples

### Une action de création, de bout en bout

```php
#[Route('/contenus/nouveau', name: 'admin_contenu_nouveau', methods: ['GET', 'POST'])]
public function nouveau(Request $request, EntityManagerInterface $entityManager): Response
{
    $contenu = new ContenuBienEtre();

    $form = $this->createForm(ContenuBienEtreType::class, $contenu);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $entityManager->persist($contenu);
        $entityManager->flush();

        $this->addFlash('success', sprintf('Le contenu « %s » a été ajouté.', $contenu->getTitre()));

        return $this->redirectToRoute('admin_contenus');
    }

    return $this->render('admin/contenus/formulaire.html.twig', [
        'form' => $form,
        'titrePage' => 'Nouveau contenu',
        'bouton' => 'Ajouter le contenu',
    ]);
}
```

Le **même** `return render()` sert à l'affichage initial (GET) **et** au
réaffichage après une erreur (POST invalide), erreurs comprises. C'est le patron
standard d'un formulaire Symfony ; on l'a déjà vu en phases 04, 05 et 07.

### La suppression

```php
#[Route('/contenus/{id}/supprimer', name: 'admin_contenu_supprimer', requirements: ['id' => '\d+'], methods: ['POST'])]
public function supprimer(ContenuBienEtre $contenu, Request $request, EntityManagerInterface $entityManager): Response
{
    if (!$this->isCsrfTokenValid('supprimer-contenu-'.$contenu->getId(), $request->getPayload()->getString('_token'))) {
        throw $this->createAccessDeniedException('Jeton CSRF invalide.');
    }

    $entityManager->remove($contenu);
    $entityManager->flush();

    $this->addFlash('success', sprintf('Le contenu « %s » a été supprimé.', $contenu->getTitre()));

    return $this->redirectToRoute('admin_contenus');
}
```

Trois protections empilées : `methods: ['POST']`, le jeton CSRF (avec
l'identifiant dedans), et le `confirm()` du navigateur dans le partiel. On
réutilise le partiel `_partials/bouton_supprimer.html.twig` écrit en phase 05 :
un seul endroit, trois espaces.

### L'affichage groupé, côté enfant

```twig
{% for type, contenus in groupes %}
    {% set premier = contenus|first %}
    <section>
        <h2>
            <span>{{ premier.typeEmoji }}</span>
            {{ premier.typeLabel }}{{ contenus|length > 1 ? 's' }}
            <span class="pastille">{{ contenus|length }}</span>
        </h2>

        {% for contenu in contenus %}
            <article class="card card-enfant h-100">
                <h3>{{ contenu.titre }}</h3>
                <p class="texte-multiligne">{{ contenu.contenu }}</p>
                {% if contenu.url %}
                    <a href="{{ contenu.url }}" target="_blank" rel="noopener" class="btn btn-or btn-sm">▶️ Ouvrir le lien</a>
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
- `texte-multiligne` est une classe maison (`white-space: pre-line`) qui conserve
  les retours à la ligne saisis par l'administrateur, **sans** interpréter de
  HTML.

---

## 5. Commandes

### `docker compose exec app php bin/console make:entity ContenuBienEtre`

- **À observer** : le type `text` pour le contenu (long), `string` pour le titre.

### `docker compose exec app php bin/console make:form ContenuBienEtreType`

- **Ce qu'elle fait** : génère un `*Type` pré-rempli à partir de l'entité.
- **À observer** : le code généré est un point de départ. Les libellés, les aides
  et les listes de choix sont à écrire à la main.

### `docker compose exec app php bin/console debug:router | grep admin`

- **Quand** : vérifier les quatre routes du CRUD et leurs méthodes HTTP.
- **À observer** : la route de suppression doit être en **POST** seul.

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
| **Doctrine** | entité `ContenuBienEtre`, repository, résolution par l'URL |
| **Form** | `ChoiceType`, `TextareaType`, `UrlType`, `help`, `placeholder` |
| **Validator** | titre et contenu obligatoires, URL valide |
| **Security** | `access_control` sur `^/admin` (déjà en place) |
| **Twig** | boucle avec `{% else %}`, partiel de suppression réutilisé |

---

## 7. Architecture et organisation du code

```text
src/
├── Controller/Admin/
│   └── ContenuController.php        les 4 actions du CRUD
├── Entity/
│   └── ContenuBienEtre.php          TYPES, DECLENCHEURS, getters d'affichage
├── Form/
│   └── ContenuBienEtreType.php
└── Repository/
    └── ContenuBienEtreRepository.php  findTousTries(), findGroupesParType()

templates/
├── admin/
│   ├── layout.html.twig             menu de l'administration
│   └── contenus/
│       ├── index.html.twig          le tableau
│       └── formulaire.html.twig     création ET modification
└── enfant/
    └── bibliotheque.html.twig       la page « Découvrir »
```

Une même entité sert deux publics très différents : un tableau dense pour
l'administrateur, des cartes colorées pour l'enfant. Les **données** sont
communes, la **présentation** ne l'est pas — c'est exactement le rôle des
gabarits.

---

## 8. Flux de fonctionnement

### Création d'un contenu

```text
Admin : GET /admin/contenus/nouveau
    ↓ access_control : ROLE_ADMIN
ContenuController::nouveau() → formulaire vide
    ↓ POST
Validator : titre, contenu, URL
    ↓ valide                    ↓ invalide
persist + flush                réaffichage avec les erreurs (HTTP 422)
    ↓
flash + redirection vers la liste
```

### Affichage côté enfant

```text
Enfant : GET /enfant/bibliotheque
    ↓ access_control : ROLE_CHILD
AccueilController::bibliotheque()
    ↓
ContenuBienEtreRepository::findGroupesParType()
    ↓
['fiche' => [...], 'video' => [...]]
    ↓
bibliotheque.html.twig : une section par type
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
- **Le premier contenu d'une règle** est celui qui sera proposé : simple à
  expliquer à un administrateur, et il suffit de réordonner pour changer.
- **L'administrateur ne gère pas les enfants** : ces profils relèvent de leur
  parent. L'espace admin ne contient donc que les contenus et, en phase 11, les
  comptes parents.

---

## 10. Erreurs fréquentes

**404 sur `/admin/contenus/5/modifier` alors que le contenu existe**
→ Mauvais identifiant, ou entité supprimée.
→ Vérification : `SELECT id FROM contenu_bien_etre`.

**Erreur 500 avec `/admin/contenus/abc/modifier`**
→ Il manque `requirements: ['id' => '\d+']`.
→ Avec, la route ne correspond simplement pas : 404 propre.

**Le `<select>` enregistre le libellé au lieu de la clé**
→ Le tableau `choices` est inversé.
→ Rappel : `['Libellé affiché' => 'valeur_en_base']`.

**Une URL sans domaine est acceptée ou refusée avec un message technique**
→ `Assert\Url` a plusieurs messages : celui de l'URL invalide, et celui du
domaine manquant (`tldMessage`).
→ Solution : renseigner les deux, en français, écrits pour l'utilisateur.

**Les retours à la ligne du contenu disparaissent à l'affichage**
→ Le HTML ignore les retours à la ligne.
→ Solution : la classe `texte-multiligne` (`white-space: pre-line`) — jamais
`|raw`, qui exécuterait le HTML saisi.

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
   retours à la ligne sont conservés côté enfant. Retirez temporairement la
   classe `texte-multiligne` pour voir la différence.
3. Saisissez `exemple` dans le champ lien : lisez le message d'erreur. Puis
   `https://exemple.fr` : il doit être accepté.
4. Ouvrez `/admin/contenus/99999/modifier` (identifiant inexistant) : vous devez
   obtenir une 404, pas une erreur 500. Expliquez **qui** a produit cette 404.
5. Supprimez tous vos contenus de test et vérifiez que la page enfant affiche le
   message « bibliothèque vide » — c'est le `{% else %}` de la boucle.

---

## 13. Scénario de test manuel

1. Se connecter avec le compte administrateur et ouvrir `/admin/contenus`.
2. Créer un contenu de type « Fiche », avec un titre, un texte et un lien.
3. Se déconnecter, se connecter en enfant et ouvrir « 📚 Découvrir ».
4. Retourner en admin, modifier le titre du contenu, puis rafraîchir la page enfant.
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

➡️ [Phase suivante](./phase-09.md)

➡️ [Phase de développement](../README.md#phase-08--bibliothèque-de-contenus-admin--enfant)

➡️ [Prompt Claude Code](../prompts/phase-08.md)
