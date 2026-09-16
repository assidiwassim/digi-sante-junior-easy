# Formation — Phase 13 : Performance, robustesse et mise en production

## 1. Objectifs pédagogiques

À la fin de cette leçon, vous devez être capable de :

- expliquer ce qui **change** entre les environnements `dev` et `prod` ;
- repérer et corriger une **requête N+1** avec le profiler ;
- préparer une installation de production (dépendances, cache, secrets) ;
- conduire une **revue de sécurité** méthodique sur une application existante ;
- documenter l'**exploitation** : sauvegarde, restauration, journaux ;
- raisonner sur la **conservation de données sensibles**.

## 2. Prérequis

- Phases 01 à 12 terminées : l'application est complète, testée, avec fixtures.
- Savoir lire l'onglet Doctrine du profiler (phase 10).

---

## 3. Concepts à apprendre

### Concept 1 — Les environnements

**Pourquoi ?** En développement, on veut des erreurs détaillées et un rechargement
immédiat. En production, on veut de la vitesse et **aucune fuite d'information**.

**Comment ça fonctionne ?** Une variable, `APP_ENV`, change le comportement de
toute l'application.

| | `dev` | `prod` |
|---|---|---|
| Barre de debug et profiler | oui | **non** |
| Détail des erreurs à l'écran | oui | **non** (page sobre) |
| Cache | reconstruit à chaque changement | construit une fois |
| Journaux | tout | erreurs et avertissements |

```dotenv
APP_ENV=prod
APP_DEBUG=0
```

⚠️ Une page d'erreur de développement affiche le code source, les variables
d'environnement et parfois des morceaux de requêtes SQL. **En production, c'est
une fuite de données.**

**Dans ce projet.** Le profiler, le debug bundle et le maker sont installés en
`--dev` : ils ne sont **pas** présents en production, ce qui supprime le risque à
la racine.

---

### Concept 2 — Le problème N+1

**Pourquoi ?** C'est la cause n°1 de lenteur dans une application Doctrine, et
elle est invisible en développement avec dix lignes de données.

**Comment ça fonctionne ?** Vous chargez une liste (1 requête), puis, pour chaque
élément, vous accédez à une relation non chargée (N requêtes).

```php
$enfants = $enfantRepository->findAll();      // 1 requête

foreach ($enfants as $enfant) {
    echo $enfant->getParent()->getEmail();    // 1 requête PAR enfant → N requêtes
}
```

Avec 200 enfants : **201 requêtes** pour une seule page.

**La correction** : demander les données liées dès le départ, avec une jointure.

```php
return $this->createQueryBuilder('e')
    ->addSelect('p', 'c')          // on récupère AUSSI le parent et le compte
    ->join('e.parent', 'p')
    ->join('e.compte', 'c')
    ->getQuery()
    ->getResult();                 // → 1 seule requête
```

⚠️ `join()` **sans** `addSelect()` ne change rien au problème : la jointure sert
alors seulement à filtrer. C'est `addSelect()` qui charge réellement les objets
liés.

**Comment le détecter ?** L'onglet **Doctrine** du profiler affiche le nombre de
requêtes. Une page avec des dizaines de requêtes quasi identiques = N+1.

---

### Concept 3 — Mesurer avant d'optimiser

**Pourquoi ?** Optimiser au hasard complique le code sans le rendre plus rapide.

**Comment ça fonctionne ?** Toujours dans cet ordre :

```text
1. mesurer (profiler : nombre de requêtes, temps)
2. identifier la page la plus coûteuse
3. corriger UNE chose
4. re-mesurer
5. garder si le gain est réel, annuler sinon
```

**Dans ce projet.** Le prompt de la phase demande explicitement de montrer le
nombre de requêtes **avant / après**. Une optimisation non mesurée n'est pas une
optimisation, c'est une supposition.

---

### Concept 4 — L'installation de production

**Pourquoi ?** Le code qui tourne chez vous n'est pas celui qu'on déploie : ni
les outils de développement, ni le cache de développement.

**Comment ça fonctionne ?**

```bash
composer install --no-dev --optimize-autoloader
php bin/console cache:clear --env=prod
php bin/console doctrine:migrations:migrate --no-interaction --env=prod
```

- `--no-dev` : n'installe pas PHPUnit, le maker, le profiler… Moins de code =
  moins de surface d'attaque.
- `--optimize-autoloader` : construit une table de correspondance classe →
  fichier, au lieu de chercher à chaque appel.
- `cache:clear --env=prod` : compile la configuration, les routes et les gabarits
  une fois pour toutes.

⚠️ **Jamais** de `doctrine:fixtures:load` en production : la commande **purge**
la base.

**OPcache** garde le PHP compilé en mémoire ; le fichier `config/preload.php`
charge en plus les classes du framework au démarrage. C'est un gain important, et
il est déjà en place dans l'image du projet.

---

### Concept 5 — Les secrets

**Pourquoi ?** Un `APP_SECRET` ou un mot de passe de base présent dans Git reste
dans l'historique **pour toujours**, même après suppression.

**Comment ça fonctionne ?**

| Fichier | Contenu | Versionné ? |
|---|---|---|
| `.env` | valeurs par défaut, non sensibles | oui |
| `.env.local` | vos réglages locaux | **non** |
| `.env.prod.local` | les secrets de production | **non** |
| `.env.prod.local.dist` | un **modèle** sans valeur réelle | oui |

Le fichier `.dist` documente ce qu'il faut renseigner, sans rien révéler.

---

### Concept 6 — La revue de sécurité

**Pourquoi ?** Chaque phase a ajouté des protections. Avant de publier, on
vérifie qu'aucune n'a été oubliée.

**Comment ça fonctionne ?** Une liste, parcourue méthodiquement :

| À vérifier | Où c'est traité dans ce projet |
|---|---|
| Chaque route sensible est protégée | `access_control` (phase 04) + `EnfantVoter` (phase 05) |
| Chaque écriture est en POST + CSRF | formulaires Symfony, partiel de suppression |
| Les données du navigateur sont revalidées | JSON des douleurs (phase 07) |
| Aucune donnée dans `innerHTML` | JavaScript du journal (phase 07) |
| `rel="noopener"` sur les liens externes | bibliothèque et conseils (phases 08-09) |
| Mots de passe hachés, jamais journalisés | phases 04, 05, 06 |
| Pas d'outil de développement exposé | phpMyAdmin retiré de la production |

**Le test le plus utile** : essayez d'accéder à ce que vous ne devriez pas voir.
Une protection jamais mise en échec n'est pas une protection vérifiée.

---

### Concept 7 — Journaux et données sensibles

**Pourquoi ?** Un journal applicatif est lu par des développeurs, copié dans des
tickets, parfois envoyé à un service externe. Il ne doit contenir **ni mot de
passe, ni donnée de santé**.

**Comment ça fonctionne ?** En production, Monolog n'écrit que les erreurs et les
avertissements. À vérifier : ne jamais journaliser le contenu d'un formulaire,
d'un journal d'enfant, ou un mot de passe — même « temporairement pour
déboguer ».

---

### Concept 8 — Sauvegarde et conservation

**Pourquoi ?** Ce projet stocke des **données de santé d'enfants**. Deux
obligations distinctes : pouvoir les **restaurer** en cas d'incident, et ne pas
les garder indéfiniment.

**Comment ça fonctionne ?**

```bash
# sauvegarde
docker compose exec database mysqldump -u root -proot digisante_junior > sauvegarde.sql

# restauration
docker compose exec -T database mysql -u root -proot digisante_junior < sauvegarde.sql
```

Une sauvegarde jamais restaurée n'est pas une sauvegarde : **testez la
restauration**.

Côté conservation, les questions à trancher et à écrire dans le README :

- combien de temps garde-t-on les journaux ?
- que se passe-t-il quand un parent supprime son compte ? (réponse dans ce
  projet : tout disparaît en cascade — phase 11) ;
- qui a accès aux sauvegardes, et où sont-elles stockées ?

---

## 4. Explications avec exemples

### Avant / après sur une requête

```php
// Avant : le tableau affiche l'email du parent de chaque enfant
public function findAllAvecParent(): array
{
    return $this->createQueryBuilder('e')->getQuery()->getResult();
    // → 1 + N requêtes dès que le gabarit affiche enfant.parent.email
}

// Après : tout est chargé d'un coup
public function findAllAvecParent(): array
{
    return $this->createQueryBuilder('e')
        ->addSelect('p', 'c')
        ->join('e.parent', 'p')
        ->join('e.compte', 'c')
        ->orderBy('e.prenom')
        ->getQuery()
        ->getResult();
    // → 1 requête
}
```

Mesure attendue dans le profiler : passer d'une trentaine de requêtes à une
seule, sur une liste de trente lignes.

### Supprimer ce qui ne sert plus

En arrivant à cette phase, du code est devenu inutile : une méthode de repository
qui n'est plus appelée, un gabarit orphelin. Chaque ligne morte est une ligne à
lire, à maintenir et à tester pour rien.

```bash
# Vérifier AVANT de supprimer
grep -rn "findAllAvecParent" src templates tests
```

Si la seule occurrence est la définition elle-même, la méthode peut partir.

**Dans ce projet.** C'est exactement ce qui est arrivé à `findAllAvecParent()` et
`findDerniers()` lorsque la section « Enfants » a été retirée de
l'administration : deux requêtes devenues inutiles, supprimées avec elle.

### Une page d'erreur sobre

En production, un visiteur qui demande une page inexistante doit voir une page
neutre : un message, un lien de retour. Pas de trace, pas de nom de classe, pas
de chemin de fichier.

Les détails, eux, sont dans `var/log/prod.log` :

```bash
docker compose exec app tail -50 var/log/prod.log
```

C'est la même information, mais réservée à ceux qui exploitent l'application.

---

## 5. Commandes

### `docker compose exec app php bin/console cache:clear --env=prod`

- **Ce qu'elle fait** : construit le cache de production.
- **À observer** : la première page ensuite peut être un peu lente, les suivantes
  rapides.

### `docker compose exec app composer install --no-dev --optimize-autoloader`

- **Ce qu'elle fait** : installe uniquement les dépendances nécessaires à
  l'exécution.
- **À observer** : le profiler disparaît.
- ⚠️ **Sur votre machine de développement**, relancez ensuite un
  `composer install` simple pour retrouver vos outils.

### `docker compose exec app php bin/console debug:router --env=prod`

- **Ce qu'elle fait** : liste les routes réellement exposées en production.
- **À observer** : `_profiler` et `_wdt` ne doivent **pas** y figurer.

### `docker compose exec app php bin/console debug:container --env=prod`

- **Quand** : vérifier qu'aucun service de développement ne subsiste.

### `docker compose exec database mysqldump -u root -proot digisante_junior > sauvegarde.sql`

- **Ce qu'elle fait** : exporte toute la base dans un fichier.
- **À observer** : la taille du fichier, et son contenu (c'est du SQL lisible).

### `docker compose exec app tail -50 var/log/prod.log`

- **Quand** : après avoir provoqué une erreur volontaire.
- **À observer** : l'erreur détaillée est **ici**, et pas à l'écran du visiteur.

### `make lint && make tests`

- **Quand** : à la fin de la phase. Aucune optimisation ne doit avoir cassé une
  règle.

---

## 6. Concepts Symfony de la phase

| Composant | Rôle ici |
|---|---|
| **Kernel / environnements** | `APP_ENV`, `APP_DEBUG`, cache par environnement |
| **Doctrine** | jointures explicites, requêtes N+1 |
| **Profiler** (dev) | mesure des requêtes et du temps |
| **Monolog** | journalisation adaptée à la production |
| **Dotenv** | `.env`, `.env.local`, fichiers `.dist` |
| **Runtime / OPcache** | préchargement, autoloader optimisé |

---

## 7. Architecture et organisation du code

```text
.env                      valeurs par défaut (versionné)
.env.prod.local.dist      modèle des secrets de production (versionné, SANS valeur)
config/
├── packages/
│   ├── monolog.yaml      ce qui est journalisé, par environnement
│   └── web_profiler.yaml chargé uniquement en dev
└── preload.php           préchargement OPcache

var/
├── cache/prod/           cache de production
└── log/prod.log          journaux de production
```

Remarquez la structure `when@dev:` / `when@test:` dans les fichiers de
configuration : c'est ainsi que Symfony charge un réglage **seulement** dans
certains environnements.

---

## 8. Flux de fonctionnement

### Une requête en développement

```text
Navigateur → index.php → Kernel (dev)
    ↓ cache reconstruit si un fichier a changé
    ↓ profiler : collecte requêtes, temps, sécurité
Réponse + barre de debug
```

### La même requête en production

```text
Navigateur → index.php → Kernel (prod)
    ↓ cache déjà compilé, chargé depuis OPcache
    ↓ aucun collecteur
Réponse
    ↓ en cas d'erreur : page sobre à l'écran, détail dans var/log/prod.log
```

---

## 9. Application au projet

**Pourquoi cette phase ?** Une application qui « marche sur ma machine » n'est pas
terminée. Celle-ci traite des données de santé d'enfants : la rigueur de la mise
en production fait partie du produit.

**Composants utilisés** : configuration par environnement, Doctrine, Monolog,
Dotenv.

**Fichiers créés ou modifiés** : `.env.prod.local.dist`, la documentation de
déploiement dans le README, et les repositories dont les requêtes ont été
optimisées.

**Pourquoi ces choix ?**

- **Aucune nouvelle fonctionnalité** dans cette phase : on consolide, on ne
  construit plus.
- **phpMyAdmin retiré de la production** : c'est un outil de développement, et un
  point d'entrée supplémentaire vers la base.
- **Mesurer avant d'optimiser** : le projet est petit ; la plupart des pages
  n'ont besoin de rien.
- **Documenter l'exploitation** : la personne qui restaurera une sauvegarde à 2 h
  du matin ne sera peut-être pas vous.

**Ce qui reste hors périmètre**, volontairement : API REST, emails,
notifications, CDN, cache HTTP avancé. Le projet est un site Twig classique, et
le rester est un choix de maintenabilité.

---

## 10. Erreurs fréquentes

**Page blanche après le passage en `prod`**
→ Le cache n'a pas été construit, ou `var/` n'est pas accessible en écriture.
→ Solution : `cache:clear --env=prod`, puis lire `var/log/prod.log`.

**La barre de debug s'affiche encore en production**
→ `APP_ENV` n'est pas réellement à `prod`, ou les paquets de développement sont
installés.
→ Vérification : `php bin/console about`.

**Une erreur 500 sans aucun détail**
→ C'est le comportement **attendu** en production.
→ Le détail est dans `var/log/prod.log`.

**`Class not found` seulement en production**
→ Autoloader non régénéré après `--no-dev`, ou classe qui vivait dans un paquet
de développement.
→ Solution : `composer dump-autoload --optimize`.

**Le site est plus lent après optimisation**
→ Une jointure `addSelect()` sur une relation à forte cardinalité peut charger
des milliers d'objets.
→ Solution : re-mesurer et annuler.

**Un `dump()` oublié dans un gabarit**
→ En production, `dump()` n'existe plus : erreur fatale.
→ Solution : chercher `dump(` et `dd(` dans tout le projet avant de déployer.

**Les tests échouent après le passage en `--no-dev`**
→ PHPUnit n'est plus installé : c'est normal.
→ Solution : tester **avant** de préparer l'installation de production.

---

## 11. Bonnes pratiques

- **Mesurez, corrigez une chose, re-mesurez.** Jamais l'inverse.
- **Aucun secret dans Git**, jamais — même « temporairement ».
- **Aucune trace technique à l'écran** en production.
- **Supprimez le code mort** après avoir vérifié qu'il n'est plus appelé.
- **Testez votre restauration de sauvegarde**, pas seulement la sauvegarde.
- **Documentez le déploiement** comme si vous deviez le faire sans avoir écrit le
  projet.
- **Gardez `make lint` et `make tests` verts** : une optimisation qui casse une
  règle n'est pas une optimisation.
- **Sur des données de santé d'enfants** : le moins de données possible, le moins
  longtemps possible, accessibles au moins de monde possible.

---

## 12. Exercice pratique

1. En `dev`, ouvrez le tableau de bord parent, la liste des enfants et la
   bibliothèque. Notez pour chacune le **nombre de requêtes SQL** affiché par le
   profiler.
2. Créez volontairement un N+1 : dans un gabarit de liste, affichez une donnée
   d'une relation non chargée. Re-mesurez : le nombre de requêtes doit bondir.
   Corrigez avec `addSelect()` + `join()`, re-mesurez, puis remettez le gabarit
   en état.
3. Passez en `APP_ENV=prod`, videz le cache, ouvrez une URL inexistante : la page
   doit être sobre. Retrouvez la trace complète dans `var/log/prod.log`.
4. Cherchez dans tout le projet les appels de débogage oubliés :

```bash
grep -rn "dump(\|dd(\|console.log(" src templates public/js
```

5. Faites une sauvegarde de la base, supprimez un contenu depuis
   l'administration, restaurez la sauvegarde, et vérifiez qu'il est revenu.
   Revenez ensuite en `APP_ENV=dev` pour continuer à travailler.

---

## 13. Scénario de test manuel

1. Passer `APP_ENV=prod` et `APP_DEBUG=0`, puis vider le cache en prod.
2. Ouvrir l'application : la barre de debug ne doit plus apparaître.
3. Provoquer une erreur volontaire (URL inexistante, puis une suppression sans jeton CSRF).
4. Se connecter en parent et ouvrir le tableau de bord, puis la liste des enfants.
5. **Résultat attendu** : aucune trace technique n'est affichée à l'utilisateur (page 404 et 403 sobres), les pages s'affichent rapidement, et les erreurs détaillées ne sont visibles que dans `var/log/`.

---

## Checklist

- [ ] J'ai compris les concepts principaux
- [ ] Je comprends le rôle des fichiers créés
- [ ] Je comprends les commandes utilisées
- [ ] Je peux expliquer le fonctionnement de cette phase
- [ ] J'ai réalisé l'exercice pratique
- [ ] J'ai exécuté le scénario de test manuel
- [ ] Le résultat attendu est obtenu

---

## 🎓 Fin du parcours

Vous avez construit une application Symfony complète, de l'environnement Docker
jusqu'à la mise en production, en passant par l'ORM, la sécurité, les
formulaires, les services et les tests.

**Pour continuer :**

- relisez `CLAUDE.md` : conventions, pièges connus et historique des décisions ;
- comparez votre code au dépôt de référence — les écarts sont des occasions
  d'apprendre ;
- reprenez la section « Points d'attention » du cahier des charges : récupération
  de mot de passe, historique des douleurs côté parent, export des données… De
  quoi pratiquer sur un projet que vous connaissez désormais par cœur.

### Aller plus loin

➡️ [Revenir au sommaire de la formation](./README.md)

➡️ [Phase de développement](../README.md#phase-13--performance-robustesse-et-mise-en-production)

➡️ [Prompt Claude Code](../prompts/phase-13.md)
