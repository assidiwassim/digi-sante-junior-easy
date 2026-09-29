# Formation — Phase 07 : Espace enfant, journal quotidien en 2 étapes

## 1. Objectifs pédagogiques

À la fin de cette leçon, vous devez être capable de :

- expliquer ce qu'est une **session** et l'utiliser pour un parcours en
  plusieurs étapes ;
- construire un formulaire **non lié à une entité** ;
- écrire une contrainte qui porte sur **plusieurs champs à la fois** ;
- s'appuyer sur l'**index unique** déclaré en phase 02 pour garantir une règle
  métier en base ;
- faire communiquer du **JavaScript** et un contrôleur Symfony via un champ
  caché ;
- expliquer pourquoi toute donnée venant du navigateur doit être **revalidée** ;
- éviter le piège du **fuseau horaire** sur la notion de « aujourd'hui ».

## 2. Prérequis

- Phases 01 à 06 terminées : l'enfant se connecte et accède à son espace.
- Connaître les entités `JournalEntry` et `PainZone`, leurs constantes
  (`SCREENS`, `ZONES`), leurs cascades et l'index unique `(child_id, date)` :
  tout cela existe **depuis la phase 02** ([leçon 02](./phase-02.md)). Relisez
  `src/Entity/JournalEntry.php` et `src/Entity/PainZone.php` avant de
  commencer : cette phase ne modifie aucune entité.
- Bases de JavaScript : sélectionner un élément, écouter un événement.

---

## 3. Concepts à apprendre

### Concept 1 — La session

**Pourquoi ?** HTTP est **sans mémoire** : deux requêtes successives ne savent
rien l'une de l'autre. Pour un parcours en deux étapes, il faut se souvenir de
l'étape 1 pendant l'étape 2.

**Comment ça fonctionne ?** Le serveur garde des données côté serveur et envoie
au navigateur un cookie contenant seulement un **identifiant de session**.

```php
$session = $request->getSession();

$session->set('journal_screens', $form->getData());   // write
$screens = $session->get('journal_screens');           // lire
$session->remove('journal_screens');                  // effacer
```

**Dans ce projet.** L'étape 1 range les minutes d'écran en session ; l'étape 2
les récupère et enregistre **tout d'un coup**. Conséquence importante : si
l'enfant abandonne en route, **rien** n'est écrit en base. Pas de journal à
moitié rempli à nettoyer plus tard.

C'est aussi un choix de simplicité : un tableau PHP en session est plus facile à
comprendre qu'une entité détachée à moitié enregistrée.

---

### Concept 2 — Un formulaire sans entité

**Pourquoi ?** Le formulaire de l'étape 1 ne correspond à **aucun** objet à
enregistrer : le `JournalEntry` n'existera qu'à la fin de l'étape 2.

**Comment ça fonctionne ?** Un `*Type` sans `data_class` renvoie un simple
tableau associatif.

```php
class JournalScreensType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        foreach (JournalEntry::SCREENS as $field => $label) {
            $builder->add($field, RangeType::class, [
                'label' => $label,
                'attr' => ['min' => 0, 'max' => 360, 'step' => 15],
                'constraints' => [new Assert\Range(min: 0, max: 360, /* … */)],
            ]);
        }
    }
}
```

`$form->getData()` renvoie alors `['screenTv' => '30', 'screenComputer' => '0', …]`.

Remarquez la boucle sur la constante `JournalEntry::SCREENS` : ajouter un type
d'écran se fera **à un seul endroit**, dans l'entité.

---

### Concept 3 — La valeur de départ d'un curseur

**Pourquoi ?** Un `<input type="range">` **sans valeur** se place au **milieu**
de sa plage. Avec six curseurs de 0 à 360 minutes, l'enfant ouvrirait la page
avec 18 h d'écran déjà déclarées.

**Comment ça fonctionne ?** On donne une valeur par défaut au formulaire :

```php
public function configureOptions(OptionsResolver $resolver): void
{
    $resolver->setDefaults([
        'data' => array_fill_keys(array_keys(JournalEntry::SCREENS), 0),
    ]);
}
```

Subtilité utile : quand le contrôleur passe explicitement des données (les
valeurs de la session, au retour de l'étape 2), **elles gagnent** sur ce défaut.
Le défaut ne s'applique donc qu'à la première visite.

C'est un **piège connu du projet** : il est noté dans le tableau des pièges de
`CLAUDE.md`.

---

### Concept 4 — Une contrainte sur plusieurs champs

**Pourquoi ?** Chaque curseur est limité à 6 h, ce qui est raisonnable
individuellement. Mais six curseurs à 6 h font **36 h dans une journée** :
impossible.

**Comment ça fonctionne ?** Les contraintes vues jusqu'ici portent sur **un**
champ. Pour une règle qui en concerne plusieurs, on pose un `Assert\Callback` sur
le **formulaire entier**.

```php
public const TOTAL_MAX = 960;   // 16 h

$resolver->setDefaults([
    'constraints' => [new Assert\Callback([self::class, 'validateTotal'])],
]);

public static function validateTotal(?array $minutes, ExecutionContextInterface $context): void
{
    $total = array_sum(array_map('intval', $minutes ?? []));

    if ($total > self::TOTAL_MAX) {
        $context->buildViolation('En tout, cela fait {{ total }} d\'écran : c\'est impossible en une journée.')
            ->setParameter('{{ total }}', DurationExtension::formater($total))
            ->addViolation();
    }
}
```

L'erreur n'appartient à aucun champ : elle s'affiche via
`{{ form_errors(form) }}`, placé juste après `form_start()` — c'est la
convention du projet.

---

### Concept 5 — Une règle garantie par la base

**Pourquoi ?** « Un seul journal par enfant et par jour » doit rester vrai même
en cas de double clic, d'onglet dupliqué ou de bug futur. Une vérification PHP
seule laisse une fenêtre : deux requêtes simultanées peuvent passer toutes les
deux.

**Comment ça fonctionne ?** Un **index unique** sur deux colonnes, déclaré en
phase 02 sur l'entité (rappel) :

```php
#[ORM\UniqueConstraint(name: 'journal_unique_par_jour', columns: ['child_id', 'date'])]
class JournalEntry
```

MySQL refuse donc déjà physiquement le doublon. Ce qu'on ajoute dans cette
phase, c'est la vérification côté contrôleur (`findToday()`), qui redirige
proprement : le confort côté PHP, la garantie côté base.

---

### Concept 6 — Le fuseau horaire

**Pourquoi ?** Le journal est « celui du jour ». Si PHP pense qu'il est le 15 et
MySQL le 16 (conteneurs en UTC), les journaux se dédoublent autour de minuit.

**Comment ça fonctionne ?** Les deux conteneurs sont réglés sur
`Europe/Paris` : `TZ` dans `compose.yaml` pour MySQL, `date.timezone` dans
`docker/php.ini` pour PHP.

```php
new \DateTimeImmutable('today')     // PHP: today at midnight, Paris time
CURDATE()                           // MySQL: the same date
```

C'est un **piège connu** du projet, documenté dans `CLAUDE.md`.

---

### Concept 7 — Faire dialoguer JavaScript et Symfony

**Pourquoi ?** Le schéma corporel est interactif : on clique sur une zone, on
choisit une intensité. Aucun champ de formulaire classique ne fait cela.

**Comment ça fonctionne ?** Le JavaScript écrit le résultat dans un **champ
caché**, en JSON. Le formulaire Symfony transporte ce champ, avec la protection
CSRF qui va avec.

```php
class JournalPainsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('pains', HiddenType::class, ['required' => false]);
    }
}
```

```js
const pains = {};                       // { "neck": 3, "eyes": 2 }
hiddenField.value = JSON.stringify(pains);
```

Et dans l'autre sens, du PHP vers le JS :

```twig
const zones = {{ zones|json_encode|raw }};
```

⚠️ `|raw` est acceptable **ici** parce que `zones` est une **constante PHP**
(`PainZone::ZONES`), pas une saisie d'utilisateur. C'est la règle du projet.

---

### Concept 8 — Revalider côté serveur

**Pourquoi ?** Tout ce qui vient du navigateur est modifiable : outils de
développement, requête forgée, extension. Le champ caché peut contenir
n'importe quoi.

**Comment ça fonctionne ?** Le contrôleur **revérifie chaque valeur** :

```php
$pains = json_decode((string) $form->get('pains')->getData(), true);

if (\is_array($pains)) {
    foreach ($pains as $zone => $intensity) {
        $knownZone = isset(PainZone::ZONES[$zone]);
        $validIntensity = \is_int($intensity) && $intensity >= 1 && $intensity <= 5;

        if ($knownZone && $validIntensity) {
            $journal->addPain(new PainZone($zone, $intensity));
        }
    }
}
```

Une zone inconnue ou une intensité de 99 est **ignorée silencieusement** : pas
d'erreur affichée, pas de donnée fausse enregistrée.

Règle du projet : **toute donnée hors formulaire Symfony est revalidée côté
serveur.**

---

### Concept 9 — Insérer du texte sans créer de faille

**Pourquoi ?** En JavaScript, `innerHTML` **interprète** le HTML. Une donnée
piégée devient du code exécuté.

**Comment ça fonctionne ?**

```js
element.textContent = zones[zone].label;   // ✅ text, never interpreted
element.innerHTML   = zones[zone].label;   // ❌ never for data
```

Règle du projet : **insérer du texte avec `textContent`, jamais une donnée dans
`innerHTML`.**

---

## 4. Explications avec exemples

### Les trois gardes du parcours

Chaque action commence par vérifier où en est l'enfant :

```php
// Steps 1 and 2: does today's journal already exist?
if ($journalRepository->findToday($child)) {
    return $this->redirectToRoute('child_journal_advice');
}

// Step 2: has step 1 been done?
$screens = $session->get(self::SESSION_SCREENS);
if (null === $screens) {
    return $this->redirectToRoute('child_journal_step1');
}
```

Résultat : quel que soit l'ordre dans lequel on tape les URL à la main, on
aboutit toujours dans un état cohérent. C'est ce qu'on appelle rendre un
parcours **robuste**.

### L'enregistrement final

```php
$journal = new JournalEntry();
$journal->setChild($child);
$journal->setScreenTv((int) $screens['screenTv']);
// … the six screens

// then the pains, checked again (see above)

$entityManager->persist($journal);   // the pains follow: cascade persist
$entityManager->flush();

$session->remove(self::SESSION_SCREENS);   // the journey is over
```

Un seul `persist()`, un seul `flush()` : tout part ensemble. Si une erreur
survenait, rien ne serait à moitié enregistré.

### Le SVG cliquable

```twig
<svg class="body-map" viewBox="0 0 260 460" role="img"
     aria-label="Schéma du corps : clique sur la zone où tu as mal">
    <rect data-zone="neck" x="114" y="98" width="32" height="30" rx="12"/>
    <ellipse data-zone="eyes" cx="115" cy="60" rx="11" ry="8"/>
</svg>
```

L'attribut `data-zone` porte **exactement** la clé de `PainZone::ZONES`. Côté
JavaScript :

```js
document.querySelectorAll('.body-map [data-zone]').forEach(function (shape) {
    shape.addEventListener('click', function () {
        currentZone = shape.dataset.zone;   // "neck"
        modal.show();
    });
});
```

L'attribut `aria-label` n'est pas décoratif : il décrit l'image pour un lecteur
d'écran.

---

## 5. Commandes

### `docker compose exec app php bin/console dbal:run-sql "SHOW INDEX FROM journal_entry"`

- **Ce qu'elle fait** : liste les index de la table créée en phase 02.
- **À observer** : l'index `journal_unique_par_jour` sur `child_id` et `date`.
  Aucune migration n'est attendue dans cette phase : les entités ne changent pas.

### `docker compose exec app php bin/console dbal:run-sql "DELETE FROM journal_entry WHERE date = CURDATE()"`

- **Ce qu'elle fait** : supprime les journaux du jour pour pouvoir recommencer.
- **Quand** : indispensable pendant les essais, puisqu'un journal par jour est
  autorisé.
- **À observer** : le nombre de lignes affectées ; les douleurs partent avec,
  grâce au `ON DELETE CASCADE`.

### `docker compose exec app php bin/console dbal:run-sql "SELECT date, screen_tv, screen_smartphone FROM journal_entry ORDER BY date DESC LIMIT 5"`

- **Quand** : vérifier ce qui a réellement été enregistré, sans passer par
  l'interface.

### La barre de debug (profiler)

- **Quand** : après avoir soumis un formulaire invalide.
- **À observer** : l'onglet **Forms** montre chaque champ, sa valeur soumise et
  ses erreurs. C'est l'outil le plus efficace pour comprendre un formulaire qui
  refuse de se valider.

---

## 6. Concepts Symfony de la phase

| Composant | Rôle ici |
|---|---|
| **HttpFoundation (Session)** | mémoriser l'étape 1 |
| **Form** | formulaire sans `data_class`, `HiddenType`, `RangeType` |
| **Validator** | `Assert\Range` par champ, `Assert\Callback` sur le formulaire |
| **Doctrine** | entités et index unique de la phase 02, `findToday()`, cascade persist |
| **Twig** | `json_encode`, blocs `javascripts` |
| **Bootstrap (JS)** | la modale de choix d'intensité |

---

## 7. Architecture et organisation du code

```text
src/
├── Controller/Child/
│   └── JournalController.php        /child/journal, /step/1, /step/2, /advice
├── Form/
│   ├── JournalScreensType.php        6 curseurs, total plafonné
│   └── JournalPainsType.php      un champ caché + CSRF
└── Repository/
    └── JournalEntryRepository.php  + findToday() (fichier de la phase 02)

templates/child/journal/
├── _progression.html.twig           « étape 1 sur 2 »
├── step1.html.twig                  curseurs + total en direct
├── step2.html.twig                  SVG + modale
└── advice.html.twig                 récapitulatif (complété en phase 09)
```

Les entités `JournalEntry` et `PainZone` ne sont pas dans cette liste : elles
existent depuis la phase 02.

Le JavaScript reste dans le bloc `javascripts` de **sa** page : il n'est utilisé
nulle part ailleurs. Règle du projet : un fichier dans `public/js/` seulement
s'il sert à plusieurs pages (ce sera le cas en phase 10).

---

## 8. Flux de fonctionnement

```text
/child/journal
    ↓  journal du jour déjà là ?  ── oui ──► /child/journal/advice
    ↓ non
/child/journal/step/1   (GET)  curseurs à zéro
    ↓  POST
Validation : chaque curseur 0-360, total ≤ 16 h
    ↓  valide
Session ← { screenTv: 60, … }
    ↓
/child/journal/step/2   (GET)  SVG + modale
    ↓  POST  (champ caché JSON + jeton CSRF)
Contrôleur : revalide chaque zone et chaque intensité
    ↓
new JournalEntry + PainZone…  →  persist + flush
    ↓
Session vidée  →  /child/journal/advice
```

---

## 9. Application au projet

**Pourquoi cette phase ?** C'est le cœur du produit : sans journal, il n'y a ni
conseils, ni suivi, ni graphique. Tout ce qui suit s'appuie sur les journaux
enregistrés ici.

**Composants utilisés** : Session, Form, Validator, Doctrine, Twig, JavaScript
vanilla.

**Fichiers créés** : voir l'arborescence.

**Pourquoi cette architecture ?**

- **Deux étapes** plutôt qu'un long formulaire : l'objectif est « moins d'une
  minute » pour un enfant de 8 ans.
- **Session plutôt que base** entre les étapes : rien d'incomplet n'est
  enregistré.
- **Un seul journal par jour**, garanti en base : la règle métier ne dépend pas
  du code.
- **Constantes `SCREENS` et `ZONES` (phase 02) réutilisées partout** : la liste
  des écrans pilote à la fois le formulaire et l'affichage ; les clés de `ZONES`
  correspondent aux `data-zone` du SVG.
- **Le journal n'est pas modifiable** une fois enregistré : c'est un choix du
  projet (hors périmètre), qui évite toute une catégorie de complexité.

**Ce que la phase apporte aussi** : l'accueil de l'enfant est complété — jauge du
jour, alerte de dépassement, état « journal rempli ou non ».

---

## 10. Erreurs fréquentes

**Les curseurs s'ouvrent au milieu (3 h chacun)**
→ Aucune valeur de départ n'a été donnée au formulaire.
→ Signe : le total affiche « 18 h » à l'ouverture.
→ Solution : l'option `data` du formulaire (Concept 3).

**« This form should not contain extra fields »**
→ Un nom de champ HTML ne correspond pas au `*Type`.
→ Solution : laisser Symfony rendre les champs.

**Le journal du jour se dédouble**
→ Index unique absent (migration de la phase 02 non appliquée), ou décalage de
fuseau entre PHP et MySQL.
→ Vérification : `SHOW INDEX FROM journal_entry` et la variable `TZ` du
conteneur MySQL.

**`Integrity constraint violation: Duplicate entry`**
→ C'est l'index unique qui fait son travail : l'enfant a déjà un journal
aujourd'hui.
→ Solution : rediriger **avant**, comme dans les trois gardes.

**Les douleurs ne sont pas enregistrées**
→ Le champ caché est vide, ou le JSON est mal formé.
→ Vérification : onglet **Forms** du profiler, ou `console.log(champCache.value)`.

**Une intensité de 99 se retrouve en base**
→ La revalidation serveur a été oubliée.
→ C'est une faille, pas un détail : reprenez le Concept 8.

**Le total en direct ne correspond pas à la jauge du serveur**
→ Les seuils JavaScript et PHP divergent.
→ Solution : garder les mêmes bornes des deux côtés (vert < 2 h, orange 2-4 h,
rouge > 4 h).

---

## 11. Bonnes pratiques

- **N'écrivez rien en base tant que le parcours n'est pas terminé.**
- **Une règle métier forte se garantit en base** (index unique), pas seulement
  en PHP.
- **Toute donnée du navigateur est suspecte** : revalidez, et ignorez ce qui est
  invalide au lieu de faire échouer tout l'enregistrement.
- **`textContent`, jamais `innerHTML`** pour une donnée.
- **`|raw` seulement pour des données produites par le serveur** (constantes,
  tableaux de nombres).
- **Gardez le JavaScript dans la page qui l'utilise** tant qu'il n'est pas
  partagé.
- **Pensez à l'accessibilité** : `role`, `aria-label` sur le SVG et les jauges.
- **Écrivez les messages pour un enfant** : « Indique une durée avec le
  curseur », pas « Cette valeur n'est pas de type integer ».

---

## 12. Exercice pratique

1. Ouvrez l'étape 1 et vérifiez dans l'inspecteur que chaque
   `<input type="range">` porte bien `value="0"`. Retirez temporairement l'option
   `data` du formulaire, rechargez : observez les curseurs au milieu. Remettez-la.
2. Poussez trois curseurs à 6 h et soumettez : lisez le message d'erreur, et
   identifiez la méthode qui l'a produit.
3. Ouvrez les outils de développement, modifiez le champ caché en
   `{"genou": 3, "neck": 99}`, puis terminez le journal. Vérifiez en base : aucune
   des deux valeurs ne doit avoir été enregistrée. Expliquez pourquoi.
4. Remplissez un journal, puis rouvrez « Mon journal » : vous devez être redirigé
   vers les conseils. Quelle ligne de code a provoqué la redirection ?
5. Supprimez le journal du jour en SQL et recommencez, cette fois **sans** signaler
   de douleur : le parcours doit fonctionner.

---

## 13. Scénario de test manuel

1. Connecté en enfant, cliquer sur « Mon journal ».
2. Étape 1 : bouger les curseurs jusqu'à environ 2 h 30 au total, vérifier que le total se met à jour en direct, puis continuer.
3. Étape 2 : cliquer sur le cou, choisir l'intensité 4, puis terminer le journal.
4. Revenir sur « Mon journal » une seconde fois dans la même journée.
5. **Résultat attendu** : le journal est enregistré avec 2 h 30 et la douleur au cou, et la seconde visite ne propose plus le formulaire (un seul journal par jour).

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

⬅️ [Phase précédente](./phase-06.md)

➡️ [Phase suivante](./phase-08.md)

➡️ [Phase de développement](../README.md#phase-07--espace-enfant--journal-quotidien-en-2-étapes)

➡️ [Prompt Claude Code](../prompts/phase-07.md)
