# Formation — Phase 09 : Services, injection de dépendances et moteur de conseils

## 1. Objectifs pédagogiques

À la fin de cette leçon, vous devez être capable de :

- expliquer ce qu'est un **service** et quand il est justifié d'en créer un ;
- expliquer l'**injection de dépendances** et le **conteneur de services** ;
- comprendre l'**autowiring** : pourquoi rien n'est à configurer ;
- écrire une règle métier lisible, avec des **seuils nommés** ;
- comprendre pourquoi un service renvoie ici des **tableaux simples** ;
- garder cohérents deux seuils utilisés à des endroits différents.

## 2. Prérequis

- Phases 01 à 08 terminées : journal enregistré, bibliothèque alimentée.
- Savoir lire une entité et un repository.
- Avoir déjà vu l'injection en argument d'action (phases 04 à 08) — on va enfin
  expliquer **pourquoi** elle fonctionne.

---

## 3. Concepts à apprendre

### Concept 1 — Le service

**Pourquoi ?** Certaines règles métier ne sont ni de l'affichage (gabarit), ni du
stockage (entité), ni de la recherche (repository), ni de l'aiguillage
(contrôleur). Les conseils en font partie : ils **calculent** quelque chose à
partir d'un journal.

**Comment ça fonctionne ?** Un service est une classe ordinaire, sans état
particulier, qui rend un service précis. En Symfony, **toute classe de `src/` est
automatiquement un service** utilisable ailleurs.

**Exemple.**

```php
namespace App\Service;

class ConseilService
{
    public function getConseils(JournalEntree $journal): array
    {
        // …les règles
    }
}
```

**Dans ce projet.** Règle explicite : « un service n'est créé **que si** le code
est partagé par plusieurs contrôleurs ». `ConseilService` est **le seul service
métier** du projet, parce que les conseils sont affichés :

- à l'enfant, à la fin de son journal (phase 09) ;
- au parent, sur son tableau de bord (phase 10).

S'il n'avait servi qu'à un endroit, le code serait resté dans le contrôleur.

---

### Concept 2 — L'injection de dépendances

**Pourquoi ?** `ConseilService` a besoin du repository des contenus. Il pourrait
l'instancier lui-même… mais il devrait alors connaître la connexion à la base,
la configuration Doctrine, etc. Chaque classe finirait par tout connaître.

**Comment ça fonctionne ?** La classe **déclare** ce dont elle a besoin ; on le
lui **donne**. C'est tout.

```php
class ConseilService
{
    public function __construct(private ContenuBienEtreRepository $contenuRepository)
    {
    }
}
```

Trois bénéfices immédiats :

- on lit les dépendances d'une classe dans sa signature, sans chercher ;
- on peut remplacer une dépendance (par un faux objet en test) sans toucher au
  code ;
- personne n'est responsable de construire les objets des autres.

---

### Concept 3 — Le conteneur de services

**Pourquoi ?** Si personne ne construit les objets… qui le fait ?

**Comment ça fonctionne ?** Symfony tient un **conteneur** : un annuaire qui sait
construire chaque service et ses dépendances, dans le bon ordre, une seule fois
par requête.

```text
Contrôleur a besoin de  ConseilService
                             ↓
Conteneur : ConseilService a besoin de ContenuBienEtreRepository
                             ↓
Conteneur : ce repository a besoin de ManagerRegistry
                             ↓
…et ainsi de suite, automatiquement
```

Vous n'écrivez **jamais** `new ConseilService(new ContenuBienEtreRepository(...))`.

---

### Concept 4 — L'autowiring

**Pourquoi ?** Déclarer chaque service à la main dans un fichier YAML serait
fastidieux.

**Comment ça fonctionne ?** Symfony lit les **types** des arguments et devine
quel service fournir. C'est l'autowiring, activé par défaut dans
`config/services.yaml`.

```yaml
services:
    _defaults:
        autowire: true        # devine les dépendances d'après les types
        autoconfigure: true   # reconnaît automatiquement voters, extensions Twig, commandes…

    App\:
        resource: '../src/'   # tout src/ devient candidat
```

C'est aussi ce qui explique tout le reste du projet :

- `EnfantVoter` (phase 05) a été reconnu comme voter **sans configuration** ;
- `DureeExtension` (phase 05) a été reconnue comme extension Twig ;
- `EntityManagerInterface $entityManager` en argument d'action est fourni
  automatiquement.

**Deux façons d'injecter**, toutes deux utilisées dans le projet :

| Où | Forme | Quand |
|---|---|---|
| Service | constructeur | la dépendance sert à toute la classe |
| Contrôleur | **argument de l'action** | la dépendance ne sert qu'à cette action |

La seconde est la convention du projet pour les contrôleurs :

```php
public function conseils(
    #[CurrentUser] User $user,
    JournalEntreeRepository $journalRepository,
    ConseilService $conseilService,          // injecté automatiquement
): Response
```

---

### Concept 5 — Une règle métier lisible

**Pourquoi ?** Une règle métier est relue par des non-développeurs (le porteur du
projet, un pédiatre, un enseignant). Elle doit se lire comme une phrase.

**Comment ça fonctionne ?** Des seuils nommés, des conditions courtes, un
commentaire pour le « pourquoi ».

```php
class ConseilService
{
    /**
     * Seuil du conseil 20-20-20, atteint dès 2 h d'écran : c'est aussi le
     * moment où la jauge passe à l'orange (JournalEntree::niveauPourMinutes).
     */
    private const SEUIL_ECRAN_MINUTES = 120;
    private const SEUIL_DOULEUR = 3;
```

Comparez :

```php
if ($total >= 120) { … }                      // 120 quoi ? pourquoi 120 ?
if ($total >= self::SEUIL_ECRAN_MINUTES) { … } // ✅ se lit tout seul
```

---

### Concept 6 — Deux seuils qui doivent rester cohérents

**Pourquoi ?** La jauge passe à l'orange à 2 h (`JournalEntree::niveauPourMinutes()`,
phase 07). Le conseil 20-20-20 utilise le même seuil. Si l'un teste `>` et
l'autre `>=`, un journal de **2 h pile** affiche une jauge orange… sans aucun
conseil. L'utilisateur voit une incohérence, sans comprendre pourquoi.

**Comment ça fonctionne ?** On choisit une convention et on la documente :

```php
} elseif ($total >= self::SEUIL_ECRAN_MINUTES) {   // >= et non >
```

**Dans ce projet.** C'est une correction réelle, notée dans l'historique de
`CLAUDE.md` : le conseil utilisait `>` et ne se déclenchait pas à 2 h pile.
Retenez la leçon générale : **une même règle exprimée à deux endroits finit
toujours par diverger** — commentez le lien entre les deux, ou factorisez.

---

### Concept 7 — Renvoyer des tableaux simples

**Pourquoi ?** On pourrait créer une classe `Conseil` avec six propriétés, des
getters, peut-être une interface…

**Comment ça fonctionne ?** Ici, le service renvoie une liste de tableaux :

```php
$conseils[] = [
    'titre' => 'Repose tes yeux avec le 20-20-20',
    'message' => sprintf('Tu as passé %s devant un écran. …', DureeExtension::formater($total)),
    'emoji' => '👁️',
    'couleur' => 'orange',
    'contenu' => $this->contenuRepository->findPremierPourDeclencheur('20-20-20'),
];
```

```twig
{% for conseil in conseils %}
    <h2>{{ conseil.emoji }} {{ conseil.titre }}</h2>
    <p>{{ conseil.message }}</p>
{% endfor %}
```

**Pourquoi ce choix ?** C'est une décision assumée du projet : « pas de DTO, pas
d'interface, pas de classe de valeur ». Ces données ne servent qu'à l'affichage,
ne sont pas réutilisées ailleurs, et ne portent aucun comportement. Une classe
ajouterait un fichier sans rien apporter.

⚠️ Ce raisonnement a des limites : dès qu'une structure est manipulée dans
plusieurs couches, transformée, ou porteuse de comportement, une vraie classe
devient préférable. Ici, ce n'est pas le cas.

---

## 4. Explications avec exemples

### Les règles, dans l'ordre

```php
public function getConseils(JournalEntree $journal): array
{
    $conseils = [];
    $total = $journal->getTotalEcran();
    $limite = $journal->getEnfant()->getMaxMinutesJour();

    // Règles 1 et 2 : un seul conseil sur les écrans, jamais deux.
    if ($total > $limite) {
        $conseils[] = [ /* dépassement de la limite */ ];
    } elseif ($total >= self::SEUIL_ECRAN_MINUTES) {
        $conseils[] = [ /* règle du 20-20-20 */ ];
    }

    // Règles 3 et 4 : les douleurs
    $douleurCouOuEpaule = false;
    $douleurYeux = false;

    foreach ($journal->getDouleurs() as $douleur) {
        if (\in_array($douleur->getZone(), ['cou', 'epaule'], true) && $douleur->getIntensite() >= self::SEUIL_DOULEUR) {
            $douleurCouOuEpaule = true;
        }
        if ('yeux' === $douleur->getZone()) {
            $douleurYeux = true;
        }
    }

    if ($douleurCouOuEpaule) { $conseils[] = [ /* étirements */ ]; }
    if ($douleurYeux)        { $conseils[] = [ /* yoga des yeux */ ]; }

    // Aucune règle déclenchée : on félicite l'enfant.
    if ([] === $conseils) {
        $conseils[] = [ /* Super journée ! */ ];
    }

    return $conseils;
}
```

Lisez la structure : `if / elseif` pour les écrans (**exclusifs**), deux `if`
indépendants pour les douleurs (**cumulables**), et un cas par défaut. Toute la
règle métier tient sur un écran.

Le `elseif` n'est pas un détail de style : il **encode** la règle « un seul
conseil sur les écrans ». Deux `if` séparés afficheraient deux messages
contradictoires.

### Le texte vient de la base

```php
'contenu' => $this->contenuRepository->findPremierPourDeclencheur('yoga_yeux'),
```

```php
public function findPremierPourDeclencheur(string $declencheur): ?ContenuBienEtre
{
    return $this->createQueryBuilder('c')
        ->where('c.declencheur = :declencheur')
        ->setParameter('declencheur', $declencheur)
        ->orderBy('c.id')
        ->setMaxResults(1)
        ->getQuery()
        ->getOneOrNullResult();
}
```

Le type de retour est `?ContenuBienEtre` : **nullable**. Si l'administrateur n'a
rattaché aucun contenu à cette règle, on renvoie `null` et le gabarit affiche le
conseil **sans** contenu associé :

```twig
{% if conseil.contenu %}
    <h3>{{ conseil.contenu.titre }}</h3>
{% endif %}
```

Jamais d'erreur 500 parce qu'une donnée optionnelle manque : c'est ce qu'on
appelle **dégrader proprement**.

### Le service utilisé par deux contrôleurs

```php
// Espace enfant (phase 09)
'conseils' => $conseilService->getConseils($journal),

// Tableau de bord parent (phase 10)
'conseils' => $journalDuJour ? $conseilService->getConseils($journalDuJour) : [],
```

Un seul endroit décide de ce qu'est un bon conseil. Le parent voit **exactement**
ce que son enfant a vu : c'est une exigence fonctionnelle, garantie par
l'architecture et non par la discipline.

---

## 5. Commandes

### `docker compose exec app php bin/console debug:autowiring | grep -i conseil`

- **Ce qu'elle fait** : liste les types injectables et vérifie que votre service
  est reconnu.
- **Quand** : « Cannot autowire … ».

### `docker compose exec app php bin/console debug:container ConseilService`

- **Ce qu'elle fait** : affiche la définition du service : sa classe, ses
  arguments, s'il est public ou privé.
- **À observer** : les arguments doivent correspondre à votre constructeur.

### `docker compose exec app php bin/console lint:container`

- **Ce qu'elle fait** : vérifie que **tous** les services peuvent être construits
  avec des arguments compatibles.
- **Pourquoi** : détecte une erreur d'injection **sans** ouvrir une page.
- **À observer** : c'est l'un des quatre contrôles de `make lint`.

### `docker compose exec app php bin/console dbal:run-sql "SELECT id, titre, declencheur FROM contenu_bien_etre WHERE declencheur IS NOT NULL"`

- **Quand** : « pourquoi ce conseil n'affiche-t-il aucun contenu ? ». Vérifiez
  qu'un contenu porte bien la clé attendue.

---

## 6. Concepts Symfony de la phase

| Composant | Rôle ici |
|---|---|
| **DependencyInjection** | conteneur, autowiring, autoconfigure |
| **Doctrine** | `findPremierPourDeclencheur()` avec le QueryBuilder |
| **Twig** | affichage de la liste de conseils |
| **HttpKernel** | injection des services dans les arguments d'action |

---

## 7. Architecture et organisation du code

```text
src/
├── Service/
│   └── ConseilService.php            LE seul service métier du projet
├── Repository/
│   └── ContenuBienEtreRepository.php + findPremierPourDeclencheur()
└── Controller/Enfant/
    └── JournalController.php         action « conseils » complétée

templates/enfant/journal/
└── conseils.html.twig                récapitulatif + conseils + contenu associé
```

Où va quel code, dans ce projet :

| Type de code | Emplacement |
|---|---|
| Aiguillage, lecture de la requête | Contrôleur |
| Donnée et règles qui la concernent seule | Entité |
| Recherche en base | Repository |
| Règle métier **partagée** entre contrôleurs | Service |
| Affichage | Gabarit Twig |

---

## 8. Flux de fonctionnement

```text
Enfant : GET /enfant/journal/conseils
    ↓
JournalController::conseils()
    ↓  Symfony injecte JournalEntreeRepository et ConseilService
JournalEntreeRepository::findAujourdhui($enfant)
    ↓  pas de journal ? → redirection vers le formulaire
ConseilService::getConseils($journal)
    ↓  applique les 5 règles
    ↓  pour chaque conseil : ContenuBienEtreRepository::findPremierPourDeclencheur()
tableau de conseils
    ↓
conseils.html.twig
    ↓
Page : récapitulatif + conseils + contenus de la bibliothèque
```

---

## 9. Application au projet

**Pourquoi cette phase ?** C'est la promesse du produit : l'enfant ne se contente
pas de déclarer ses écrans, il **apprend quelque chose** en retour. Les phases
précédentes ont collecté la donnée ; celle-ci lui donne du sens.

**Composants utilisés** : injection de dépendances, Doctrine, Twig.

**Fichiers créés** : `src/Service/ConseilService.php`, la méthode
`findPremierPourDeclencheur()`, et le gabarit des conseils complété.

**Pourquoi cette architecture ?**

- **Un service, parce que deux contrôleurs** en ont besoin. Pas par principe.
- **Des tableaux simples**, parce que ces données ne servent qu'à l'affichage.
- **Les textes en base**, parce qu'un administrateur doit pouvoir les corriger.
- **Un ton positif**, parce que le public a 8 à 14 ans : « Ce n'est pas grave,
  mais demain essaie de faire une pause plus tôt ! 💪 » plutôt que « Limite
  dépassée ».
- **Cinq règles, pas trente** : chaque règle doit rester explicable à un enfant.

---

## 10. Erreurs fréquentes

**`Cannot autowire service "App\Service\ConseilService"`**
→ Le type d'un argument du constructeur est absent ou ambigu.
→ Solution : typer avec une classe ou une interface connue du conteneur.

**`Call to a member function getTitre() on null`**
→ Aucun contenu n'est rattaché à la règle, et le gabarit ne teste pas `null`.
→ Solution : `{% if conseil.contenu %}`.

**Deux conseils sur les écrans s'affichent en même temps**
→ Deux `if` au lieu d'un `if / elseif`.

**Aucun conseil à 2 h pile, alors que la jauge est orange**
→ Le seuil utilise `>` au lieu de `>=`.
→ C'est le bug réel corrigé sur ce projet (Concept 6).

**Une douleur au cou d'intensité 2 déclenche les étirements**
→ Le seuil d'intensité n'est pas appliqué, ou la comparaison est `>=` là où il
faut `>` (ou l'inverse).
→ Solution : relire la règle métier avant de corriger le code.

**Le parent ne voit pas les mêmes conseils que son enfant**
→ Le tableau de bord réimplémente la logique au lieu d'appeler le service.
→ Solution : un seul endroit, toujours.

**Modifier un texte de la bibliothèque ne change rien dans les conseils**
→ Un autre contenu porte la même règle et arrive **avant** (tri par `id`).
→ Vérification : la requête SQL de la section Commandes.

---

## 11. Bonnes pratiques

- **Créez un service quand le code est partagé**, pas « pour bien faire ».
- **Injectez, n'instanciez pas** : pas de `new` sur un service.
- **Nommez vos seuils** en constantes, et commentez le pourquoi.
- **Documentez le lien entre deux règles liées** (jauge et conseil), sinon elles
  divergeront.
- **Dégradez proprement** : une donnée optionnelle absente ne doit jamais casser
  une page.
- **Gardez la logique métier hors des gabarits** : Twig affiche, il ne décide
  pas.
- **Écrivez les messages pour l'utilisateur final**, ici un enfant : encourager,
  jamais culpabiliser.

---

## 12. Exercice pratique

1. Ouvrez `ConseilService` et **listez les cinq règles** à voix haute, sans lire
   les commentaires. Si vous n'y arrivez pas, le code n'est pas assez lisible :
   dites-le.
2. Remplacez temporairement `elseif` par `if` pour la règle du 20-20-20, remplissez
   un journal au-dessus de la limite : deux conseils contradictoires apparaissent.
   Remettez `elseif`.
3. Supprimez (temporairement) le contenu rattaché à `yoga_yeux`, puis déclenchez
   la règle : le conseil doit s'afficher **sans** contenu associé, sans erreur.
   Recréez le contenu ensuite.
4. Lancez `debug:container ConseilService` et retrouvez, dans la sortie, la
   dépendance déclarée dans votre constructeur.
5. Ajoutez un `dump($conseils);` dans le contrôleur, ouvrez la page et observez
   la structure exacte du tableau dans la barre de debug. Retirez-le ensuite.

---

## 13. Scénario de test manuel

1. Supprimer le journal du jour de l'enfant pour pouvoir recommencer :
   `docker compose exec app php bin/console dbal:run-sql "DELETE FROM journal_entree WHERE date = CURDATE()"`.
2. Remplir un journal avec **plus de temps d'écran que la limite** du profil et une douleur aux yeux.
3. Lire la page de conseils affichée à la fin.
4. Revenir à l'accueil et cliquer sur « Revoir mes conseils ».
5. **Résultat attendu** : deux conseils apparaissent (dépassement de limite et yoga des yeux), avec le contenu de la bibliothèque associé, et ils sont identiques au retour depuis l'accueil.

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

➡️ [Phase suivante](./phase-10.md)

➡️ [Phase de développement](../README.md#phase-09--moteur-de-conseils)

➡️ [Prompt Claude Code](../prompts/phase-09.md)
