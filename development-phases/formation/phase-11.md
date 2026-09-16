# Formation — Phase 11 : Administration des comptes et suppressions en cascade

## 1. Objectifs pédagogiques

À la fin de cette leçon, vous devez être capable de :

- filtrer sur une colonne **JSON** et comprendre les limites de ce stockage ;
- lever une **404 volontaire** et savoir quand c'est le bon choix ;
- vérifier **concrètement** qu'une cascade de suppression fonctionne ;
- distinguer, pour de bon, `cascade: ['remove']` et `onDelete: 'CASCADE'` ;
- concevoir une page de **consultation** sans y glisser des actions ;
- décider ce qui relève d'un administrateur et ce qui n'en relève pas.

## 2. Prérequis

- Phases 01 à 10 terminées : les trois espaces fonctionnent.
- Avoir vu les relations et les cascades (phase 05).
- Savoir écrire un CRUD (phase 08).

---

## 3. Concepts à apprendre

### Concept 1 — Interroger une colonne JSON

**Pourquoi ?** Les rôles sont stockés en JSON : `["ROLE_PARENT"]`. Pratique pour
en mettre plusieurs, mais on ne peut pas écrire `WHERE roles = 'ROLE_PARENT'`.

**Comment ça fonctionne ?** Doctrine ORM ne propose pas d'opérateur JSON portable.
Pour un volume modeste, une recherche textuelle suffit :

```php
public function findParents(): array
{
    // Les rôles sont enregistrés en JSON, par exemple ["ROLE_PARENT"].
    return $this->createQueryBuilder('u')
        ->where('u.roles LIKE :role')
        ->setParameter('role', '%"'.User::ROLE_PARENT.'"%')
        ->orderBy('u.createdAt', \SortDirection::Descending)
        ->getQuery()
        ->getResult();
}
```

Les **guillemets** dans le motif (`%"ROLE_PARENT"%`) ne sont pas décoratifs : ils
évitent qu'un futur `ROLE_PARENT_ADMIN` corresponde par accident.

**Ce qu'il faut retenir.** Un champ JSON est confortable à écrire, inconfortable
à interroger. À grande échelle, on préférerait une table de rôles ou une colonne
dédiée. Ici, avec quelques centaines de comptes, `LIKE` est un compromis
assumé — et **commenté dans le code**, pour que le lecteur suivant comprenne.

⚠️ Rappel : le tri utilise `\SortDirection::Descending`. La chaîne `'DESC'` est
dépréciée dans Doctrine ORM 3, et la suite de tests du projet échoue sur les
dépréciations.

---

### Concept 2 — La 404 volontaire

**Pourquoi ?** `/admin/parents/7` charge un `User`. Mais l'identifiant 7 peut
être celui d'un **enfant** : afficher sa fiche dans l'écran « parents » n'aurait
aucun sens.

**Comment ça fonctionne ?** On vérifie, et on refuse explicitement :

```php
public function voir(User $parent): Response
{
    // L'id peut désigner n'importe quel compte : on n'affiche que les parents.
    if (!$parent->isParent()) {
        throw $this->createNotFoundException('Ce compte n\'est pas un compte parent.');
    }

    return $this->render('admin/parents/voir.html.twig', ['parent' => $parent]);
}
```

**404 ou 403 ?**

| Code | Sens | Ici |
|---|---|---|
| **403** | « vous n'avez pas le droit » | un parent qui ouvre `/admin` |
| **404** | « cette ressource n'existe pas » | cet identifiant n'est pas un parent |

L'administrateur **a** le droit d'être sur cette page ; c'est la ressource
demandée qui n'existe pas **dans ce contexte**. D'où la 404.

Le message passé à `createNotFoundException()` est destiné aux développeurs : il
apparaît dans les journaux et sur la page d'erreur de développement, pas au
visiteur en production.

---

### Concept 3 — Vérifier une cascade plutôt que la supposer

**Pourquoi ?** Supprimer un parent doit supprimer ses enfants, leurs comptes,
leurs journaux, leurs douleurs. Si une seule cascade manque, il reste en base des
données personnelles **d'enfants** qui auraient dû disparaître. Ce n'est pas un
bug esthétique.

**Comment ça fonctionne ?** On lit le mapping, et surtout **on vérifie** :

```sql
-- après avoir supprimé le parent d'identifiant 5
SELECT COUNT(*) FROM enfant WHERE parent_id = 5;
SELECT COUNT(*) FROM users WHERE username = 'lea_test';
SELECT COUNT(*) FROM journal_entree je LEFT JOIN enfant e ON e.id = je.enfant_id WHERE e.id IS NULL;
```

La troisième requête cherche les **orphelins** : des journaux dont l'enfant
n'existe plus. Le résultat doit être `0`.

**Dans ce projet.** La chaîne complète :

```text
Parent ──► Enfants ──► Compte de connexion de l'enfant
                  └──► Journaux ──► Douleurs
```

---

### Concept 4 — `cascade: ['remove']` et `onDelete: 'CASCADE'`

On les a croisés en phase 05 ; c'est ici qu'on les départage pour de bon.

| | `cascade: ['remove']` | `onDelete: 'CASCADE'` |
|---|---|---|
| Écrit où | dans le mapping de la relation | sur la `JoinColumn` |
| Exécuté par | **PHP** (Doctrine) | **MySQL** |
| Déclenché par | `$entityManager->remove($objet)` | la suppression de la ligne parente, **par n'importe quel moyen** |
| Charge les objets ? | **oui**, en mémoire | non, tout se passe en base |
| Événements Doctrine | déclenchés | **non** déclenchés |

**Quand utiliser lequel ?**

- **Doctrine** quand la suppression doit passer par PHP : peu d'objets, ou des
  traitements associés. C'est le cas du `OneToOne` enfant → compte.
- **Base de données** quand le volume peut être important et qu'aucun traitement
  PHP n'est nécessaire : journaux et douleurs, potentiellement des centaines de
  lignes.

Utiliser les deux est cohérent, à condition de savoir **pourquoi** à chaque fois.

⚠️ Règle du projet : **aucune classe « manager » de suppression**, aucune boucle
`foreach` qui supprime à la main. Si une cascade manque, on corrige le mapping et
on génère une migration.

---

### Concept 5 — Une page de consultation

**Pourquoi ?** L'administrateur voit les enfants d'un parent, mais **ne les gère
pas** : ces profils relèvent de leur parent. C'est une décision de conception du
projet, pas un oubli.

**Comment ça fonctionne ?** Les lignes ne sont ni des liens, ni des boutons.

```twig
{# Les profils enfants sont gérés par leur parent : ici, simple consultation. #}
<div class="list-group-item d-flex align-items-center gap-3">
    <span class="avatar-bulle petit">{{ enfant.avatarEmoji }}</span>
    <span class="flex-fill">
        <strong>{{ enfant.nomComplet }}</strong>
        <span class="d-block small texte-doux">{{ enfant.age }} ans · {{ enfant.compte.username }}</span>
    </span>
    <span class="pastille">⏱️ {{ enfant.maxMinutesJour|duree }}</span>
</div>
```

Un `<div>`, pas un `<a>` : rien à cliquer, donc aucune promesse non tenue.

**Le principe général.** Chaque interface répond à la question « de quoi cette
personne a-t-elle besoin ? », pas « que pourrait-on afficher ? ». Moins de
pouvoir donné à l'administrateur = moins de risques sur des données de santé
d'enfants.

---

### Concept 6 — Informer après une action destructrice

**Pourquoi ?** Supprimer un parent supprime bien plus que lui. L'administrateur
doit **savoir** ce qu'il vient de faire.

**Comment ça fonctionne ?** On compte **avant**, on informe **après** :

```php
$nombreEnfants = $parent->getEnfants()->count();

$entityManager->remove($parent);
$entityManager->flush();

$this->addFlash('success', sprintf(
    'Le compte %s a été supprimé, avec %d profil(s) enfant.',
    $parent->getEmail(),
    $nombreEnfants,
));
```

L'ordre compte : après `flush()`, la collection est vide et le message afficherait
`0`. Détail subtil, conséquence visible.

Trois protections, comme partout dans le projet : `methods: ['POST']`, jeton
CSRF, et un `confirm()` explicite sur le caractère définitif.

---

## 4. Explications avec exemples

### La liste des parents

```php
#[Route('', name: 'admin_parents', methods: ['GET'])]
public function index(UserRepository $userRepository): Response
{
    return $this->render('admin/parents/index.html.twig', [
        'parents' => $userRepository->findParents(),
    ]);
}
```

Trois lignes. Toute l'intelligence est dans le repository : c'est exactement ce
que la convention du projet demande d'un contrôleur.

```twig
<td>{{ [parent.ville, parent.pays]|filter(v => v)|join(', ')|default('—') }}</td>
```

Cette ligne Twig mérite une lecture : on met les deux valeurs dans un tableau, on
retire les vides avec `filter`, on joint avec une virgule, et si tout est vide on
affiche un tiret. C'est de la **mise en forme**, pas de la logique métier : sa
place est bien dans le gabarit.

### La suppression, action complète

```php
#[Route('/{id}/supprimer', name: 'admin_parent_supprimer', requirements: ['id' => '\d+'], methods: ['POST'])]
public function supprimer(User $parent, Request $request, EntityManagerInterface $entityManager): Response
{
    if (!$parent->isParent()) {
        throw $this->createNotFoundException('Ce compte n\'est pas un compte parent.');
    }

    if (!$this->isCsrfTokenValid('supprimer-parent-'.$parent->getId(), $request->getPayload()->getString('_token'))) {
        throw $this->createAccessDeniedException('Jeton CSRF invalide.');
    }

    $nombreEnfants = $parent->getEnfants()->count();

    $entityManager->remove($parent);   // les cascades font le reste
    $entityManager->flush();

    $this->addFlash('success', sprintf('Le compte %s a été supprimé, avec %d profil(s) enfant.', $parent->getEmail(), $nombreEnfants));

    return $this->redirectToRoute('admin_parents');
}
```

Remarquez ce qui **n'est pas** dans ce code : aucune boucle sur les enfants,
aucune suppression manuelle des journaux. Une seule ligne de suppression, et le
mapping fait le travail. C'est tout l'intérêt d'avoir configuré les cascades
correctement en phase 05.

Notez aussi l'ordre des vérifications : d'abord « est-ce bien un parent »
(404), ensuite le jeton (403). On refuse le plus tôt possible.

---

## 5. Commandes

### `docker compose exec app php bin/console dbal:run-sql "SELECT id, email, roles FROM users"`

- **Ce qu'elle fait** : montre le contenu réel de la colonne JSON.
- **À observer** : la forme exacte (`["ROLE_PARENT"]`) — c'est elle que votre
  `LIKE` doit reconnaître.

### `docker compose exec app php bin/console dbal:run-sql "SELECT COUNT(*) FROM journal_entree je LEFT JOIN enfant e ON e.id = je.enfant_id WHERE e.id IS NULL"`

- **Ce qu'elle fait** : compte les journaux orphelins.
- **Quand** : après chaque test de suppression.
- **À observer** : `0`. Toute autre valeur signale une cascade manquante.

### `docker compose exec app php bin/console doctrine:schema:validate`

- **Quand** : après avoir ajusté une cascade.
- **Rappel** : un `ON DELETE CASCADE` modifie la base et exige donc une
  migration.

### `docker compose exec app php bin/console debug:router | grep admin`

- **À observer** : la suppression en **POST** uniquement, et les `requirements`
  sur `{id}`.

---

## 6. Concepts Symfony de la phase

| Composant | Rôle ici |
|---|---|
| **Doctrine** | requête sur colonne JSON, cascades, `remove()` |
| **Routing** | résolution d'entité, `requirements`, `methods` |
| **Security** | `access_control` sur `^/admin`, jeton CSRF |
| **HttpKernel** | `createNotFoundException()` (404), `createAccessDeniedException()` (403) |
| **Twig** | filtres de mise en forme, partiel de suppression réutilisé |

---

## 7. Architecture et organisation du code

```text
src/
├── Controller/Admin/
│   ├── ContenuController.php       (phase 08)
│   └── ParentController.php        liste, fiche, suppression
└── Repository/
    └── UserRepository.php          + findParents()

templates/admin/
├── layout.html.twig                + entrée de menu « Parents »
└── parents/
    ├── index.html.twig             tableau des comptes
    └── voir.html.twig              fiche + enfants en lecture seule
```

L'espace admin ne contient que **deux** sections : Contenus et Parents. Il n'y a
pas de section « Enfants » — c'est un choix documenté dans l'historique du
projet : les profils enfants relèvent de leur parent.

Cette sobriété a une vertu : moins de pages, moins de chemins d'accès aux données
sensibles, moins de code à maintenir et à auditer.

---

## 8. Flux de fonctionnement

```text
Admin : POST /admin/parents/5/supprimer (avec jeton CSRF)
    ↓
access_control : ROLE_ADMIN
    ↓
Résolution d'entité : charge le User 5
    ↓  n'est pas un parent → 404
    ↓  jeton CSRF invalide → 403
Comptage des enfants (avant suppression)
    ↓
$entityManager->remove($parent) + flush()
    ↓
Doctrine : cascade remove sur les enfants
    ↓          puis sur le compte de chaque enfant
MySQL : ON DELETE CASCADE sur les journaux
    ↓          puis sur les douleurs
    ↓
flash « … avec 2 profil(s) enfant » → redirection vers la liste
```

---

## 9. Application au projet

**Pourquoi cette phase ?** Elle termine l'espace d'administration et donne le
moyen de **supprimer réellement** les données d'une famille. Sur une application
qui traite des données de santé d'enfants, c'est une exigence, pas un confort :
un parent doit pouvoir demander l'effacement complet.

**Composants utilisés** : Doctrine, Routing, Security, Twig.

**Fichiers créés** : voir l'arborescence.

**Pourquoi cette architecture ?**

- **Les cascades plutôt que du code** : une suppression écrite à la main oublie
  toujours une table, un jour.
- **Une fiche en lecture seule** : l'administrateur observe, le parent gère.
- **Un message qui annonce l'ampleur** de la suppression : la transparence fait
  partie de l'interface.
- **404 pour un identifiant qui n'est pas un parent** : les sections de
  l'administration restent étanches.

**Ce que l'administrateur ne peut pas faire, volontairement** : créer ou modifier
un compte parent (le parent s'inscrit lui-même), gérer un profil enfant, créer un
autre administrateur (cela se fait en base ou en fixtures).

---

## 10. Erreurs fréquentes

**`findParents()` renvoie aussi des enfants ou l'administrateur**
→ Motif `LIKE` trop large, sans guillemets.
→ Solution : `'%"'.User::ROLE_PARENT.'"%'`.

**Une dépréciation Doctrine apparaît dans les tests**
→ `'DESC'` passé en chaîne.
→ Solution : `\SortDirection::Descending`.

**Après suppression, des lignes subsistent dans `journal_entree`**
→ Une cascade manque dans la chaîne.
→ Diagnostic : la requête d'orphelins de la section Commandes.

**`Integrity constraint violation: Cannot delete or update a parent row`**
→ MySQL refuse de supprimer une ligne encore référencée : il manque un
`ON DELETE CASCADE` (ou une cascade Doctrine).

**Le message annonce « 0 profil(s) enfant »**
→ Le comptage a été fait **après** `flush()`.

**La fiche parent affiche un enfant cliquable qui mène à une 404**
→ Un lien pointe vers une route supprimée.
→ Solution : un `<div>` de consultation, comme au Concept 5.

**403 sur la suppression alors que le bouton vient du site**
→ Le nom du jeton CSRF diffère entre le gabarit et le contrôleur.
→ Rappel : la **même** chaîne des deux côtés, identifiant compris.

---

## 11. Bonnes pratiques

- **Vérifiez vos cascades avec de vraies requêtes** : les supposer, c'est laisser
  des données personnelles derrière soi.
- **Commentez les requêtes inhabituelles** (le `LIKE` sur du JSON) : le lecteur
  suivant doit comprendre pourquoi ce n'est pas plus propre.
- **Choisissez 404 ou 403 en connaissance de cause.**
- **Annoncez l'ampleur d'une suppression** avant (confirmation) et après (message).
- **N'offrez pas d'action que vous ne voulez pas autoriser** : une interface de
  consultation reste une interface de consultation.
- **Refusez le plus tôt possible** dans une action : le contrôle le moins coûteux
  d'abord.
- **Trois protections pour une suppression** : POST, CSRF, confirmation.

---

## 12. Exercice pratique

1. Créez un compte parent de test, ajoutez-lui un enfant, faites remplir un
   journal avec une douleur. Notez les identifiants concernés dans les quatre
   tables (`users`, `enfant`, `journal_entree`, `douleur_zone`).
2. Supprimez le compte depuis l'administration, puis vérifiez les quatre tables :
   plus aucune ligne ne doit subsister. Lancez aussi la requête d'orphelins.
3. Retirez temporairement `cascade: ['remove']` de la relation enfant → compte,
   recommencez l'opération : le compte de l'enfant reste en base. **Remettez la
   cascade**, et expliquez ce que vous venez d'observer.
4. Ouvrez `/admin/parents/{id}` avec l'identifiant d'un **enfant** : vous devez
   obtenir une 404. Où est écrite cette vérification ?
5. Modifiez le motif `LIKE` en `'%ROLE_PARENT%'` (sans guillemets) et créez un
   compte fictif portant le rôle `ROLE_PARENT_TEST` : il apparaît à tort dans la
   liste. Remettez le motif correct.

---

## 13. Scénario de test manuel

1. Créer un compte parent de test via `/inscription`, puis lui ajouter un enfant.
2. Se connecter en administrateur et ouvrir `/admin/parents`.
3. Ouvrir la fiche de ce parent et vérifier que son enfant y figure.
4. Supprimer le compte, confirmer, puis vérifier dans phpMyAdmin les tables `users`, `enfant` et `journal_entree`.
5. **Résultat attendu** : le message indique le nombre de profils enfants supprimés, et plus aucune ligne liée à ce parent ne subsiste en base.

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

➡️ [Phase suivante](./phase-12.md)

➡️ [Phase de développement](../README.md#phase-11--administration-des-comptes-parents)

➡️ [Prompt Claude Code](../prompts/phase-11.md)
