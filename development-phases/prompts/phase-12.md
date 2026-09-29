# Prompt Claude Code — Phase 12 : Données de démonstration et qualité

> Copiez tout ce qui suit dans Claude Code, à la racine du projet.

---

## Contexte du projet

**Digi-Santé Junior** : application Symfony de suivi du bien-être numérique des
enfants de 8 à 14 ans. Toutes les fonctionnalités sont développées : comptes et
rôles, espace parent (profils enfants, tableau de bord, graphiques), espace
enfant (journal en 2 étapes, conseils, bibliothèque), espace admin (contenus,
comptes parents).

Stack : PHP 8.4, Symfony 7.4, Doctrine ORM 3, Twig, MySQL 8, Docker.

## Objectif de la phase

Rendre le projet **reprenable par quelqu'un d'autre** : un jeu de données de
démonstration en une commande, des vérifications automatiques de la
configuration (linters) et une documentation qui permet d'installer le projet
sans aide. C'est la **dernière phase** du parcours.

> Comme dans toutes les phases, **aucun test automatisé** : la validation se
> fait au navigateur, en rejouant les scénarios des phases précédentes sur les
> données de démonstration.

## Avant de coder

1. Parcours `src/Entity/` pour connaître les champs et les règles de validation
   à respecter dans les données.
2. Tous les paquets sont installés depuis la phase 01, dont
   `doctrine/doctrine-fixtures-bundle` ; s'il en manque un, signale-le.
3. Propose-moi la liste des fixtures **avant** de les écrire.

## À implémenter

### 1. Données de démonstration (`AppFixtures`)

Un jeu cohérent, rechargeable à volonté, et **exactement** celui du projet de
référence : mêmes comptes, mêmes enfants, mêmes journaux, mêmes contenus. Pour
que les nombres « aléatoires » soient identiques, respecte **l'ordre** des
opérations ci-dessous : la graine fixe ne donne les mêmes valeurs que si
`mt_rand()` est appelé dans le même ordre, avec les mêmes bornes.

**Ordre de chargement** (dans `load()`) :

1. `mt_srand(20240912)` ;
2. les **contenus** (liste ci-dessous, dans cet ordre) ;
3. les **comptes** (mot de passe haché avec `UserPasswordHasherInterface`) :

   | Email | Mot de passe | Rôle | Pays | Ville |
   |---|---|---|---|---|
   | `admin@digisante.local` | `admin123` | `ROLE_ADMIN` | France | Paris |
   | `parent@digisante.local` | `parent123` | `ROLE_PARENT` | France | Lyon |
   | `sofia@digisante.local` | `parent123` | `ROLE_PARENT` | Belgique | Bruxelles |

4. puis, **pour chaque enfant dans cet ordre**, son profil (compte `ROLE_CHILD`,
   mot de passe `enfant123`) **suivi aussitôt de ses 15 journaux** :

   | Prénom | Nom | Identifiant | Parent | Avatar | Naissance | Limite | Profil de journaux |
   |---|---|---|---|---|---|---|---|
   | Léa | Martin | `lea` | parent@ | `renard` | `today -12 years -4 months` | 120 | beaucoup d'écran |
   | Tom | Martin | `tom` | parent@ | `dragon` | `today -9 years -8 months` | 90 | poignets |
   | Noah | Dubois | `noah` | sofia@ | `pingouin` | `today -13 years -7 months` | 150 | épaules |
   | Inès | Dubois | `ines` | sofia@ | `licorne` | `today -11 years -2 months` | 120 | équilibré |

**Journaux** : 15 par enfant, du jour `today -14 days` jusqu'à **aujourd'hui**
(`$joursAvant` de 14 à 0). Pour chaque journal, les durées sont tirées **dans
cet ordre** (`ecranTv`, `ecranOrdinateur`, `ecranSmartphone`, `ecranTablette`,
`ecranConsole`, puis `ecranAutre` s'il est indiqué), puis les douleurs :

| Profil | TV | Ordinateur | Téléphone | Tablette | Console | Autre | Douleurs (après les durées) |
|---|---|---|---|---|---|---|---|
| beaucoup d'écran | `mt_rand(30, 75)` | `mt_rand(45, 110)` | `mt_rand(50, 120)` | `mt_rand(20, 60)` | `mt_rand(0, 45)` | `mt_rand(0, 20)` | si `$joursAvant % 2 === 0` : `cou`, `mt_rand(3, 5)` ; puis si `$joursAvant % 3 === 0` : `yeux`, `mt_rand(2, 4)` |
| poignets | `mt_rand(20, 60)` | `mt_rand(10, 40)` | `mt_rand(15, 50)` | `mt_rand(20, 70)` | `mt_rand(20, 80)` | — (0) | si `$joursAvant % 4 === 0` : `poignet`, `mt_rand(1, 3)` |
| épaules | `mt_rand(30, 70)` | `mt_rand(30, 90)` | `mt_rand(20, 70)` | `mt_rand(10, 50)` | `mt_rand(0, 40)` | — (0) | si `$joursAvant <= 1` : `epaule`, `mt_rand(3, 4)` ; puis si `$joursAvant % 5 === 0` : `dos`, `mt_rand(2, 4)` |
| équilibré | `mt_rand(15, 40)` | `mt_rand(10, 35)` | `mt_rand(10, 30)` | `mt_rand(0, 25)` | `mt_rand(0, 20)` | — (0) | si `$joursAvant === 7` : `main`, intensité 2 |

**Contenus** : 15 contenus, dans cet ordre, sous la forme
`[type, titre, texte, lien, règle]` (reprends les textes à l'identique) :

```php
    $contenus = [
        [
            'exercice',
            'La règle du 20-20-20',
            "Toutes les 20 minutes passées devant un écran :\n"
            ."1. Lève les yeux de ton écran.\n"
            ."2. Regarde un objet situé à environ 6 mètres (20 pieds) — la fenêtre, le fond du couloir…\n"
            ."3. Garde le regard dessus pendant 20 secondes en clignant tranquillement des yeux.\n\n"
            .'Tes yeux se détendent et la fatigue visuelle diminue nettement.',
            null,
            ContenuBienEtre::DECLENCHEUR_20_20_20,
        ],
        [
            'video',
            'Étirements du cou et des épaules en 3 minutes',
            "Une routine tout en douceur à faire assis sur ta chaise :\n"
            ."• Penche lentement la tête vers l'épaule droite, compte jusqu'à 10, puis change de côté.\n"
            ."• Fais rouler tes épaules vers l'arrière, 10 fois.\n"
            ."• Rentre le menton pour étirer la nuque, compte jusqu'à 10.\n\n"
            .'À faire chaque fois que tu sens ton cou tout raide.',
            'https://www.youtube.com/results?search_query=etirement+cou+epaules+enfant',
            ContenuBienEtre::DECLENCHEUR_ETIREMENT,
        ],
        [
            'exercice',
            'Le yoga des yeux',
            "Cinq mouvements pour réveiller tes yeux fatigués :\n"
            ."• Regarde en haut, puis en bas — 10 fois, sans bouger la tête.\n"
            ."• Regarde à gauche, puis à droite — 10 fois.\n"
            ."• Dessine un grand cercle avec ton regard, dans un sens puis dans l'autre.\n"
            ."• Cligne très vite des yeux pendant 15 secondes.\n"
            ."• Frotte tes mains l'une contre l'autre, puis pose-les en coupe sur tes paupières fermées pendant 30 secondes.",
            null,
            ContenuBienEtre::DECLENCHEUR_YOGA_YEUX,
        ],
        [
            'exercice',
            'Le défi des 7 minutes',
            "Sept mouvements, 30 secondes chacun, 10 secondes de pause entre chaque :\n"
            ."1. Sauts avec écart (jumping jacks)\n2. Chaise contre le mur\n3. Pompes sur les genoux\n"
            ."4. Abdominaux\n5. Montées sur une marche\n6. Squats\n7. Gainage sur les coudes\n\n"
            .'Aucun matériel nécessaire : juste toi et un peu de place !',
            'https://www.youtube.com/results?search_query=defi+7+minutes+enfant+sport',
            null,
        ],
        [
            'fiche',
            'Bien dormir quand on a beaucoup d\'écran',
            "La lumière des écrans envoie à ton cerveau le message « il fait encore jour ».\n\n"
            ."• Arrête les écrans au moins 1 heure avant de dormir.\n"
            ."• Laisse tablette et téléphone hors de ta chambre pour la nuit.\n"
            ."• Lis quelques pages ou écoute une histoire à la place.\n"
            .'• Couche-toi à peu près à la même heure chaque soir, même le week-end.',
            null,
            null,
        ],
        [
            'fiche',
            'Bien s\'installer devant un écran',
            "Ta position compte autant que la durée !\n\n"
            ."• Le haut de l'écran doit être au niveau de tes yeux.\n"
            ."• Garde le dos bien droit contre le dossier, les pieds à plat au sol.\n"
            ."• Laisse une distance d'environ un bras entre tes yeux et l'écran.\n"
            .'• Évite de jouer allongé ou avec la tête penchée : c\'est ce qui fait mal au cou.',
            null,
            null,
        ],
        [
            'fiche',
            'Combien de temps d\'écran par jour ?',
            "Les spécialistes recommandent, pour les 8-14 ans, environ 2 heures d'écran "
            ."de loisir par jour au maximum — les devoirs sur ordinateur ne comptent pas.\n\n"
            .'L\'idée n\'est pas de tout supprimer, mais de garder du temps pour bouger, '
            .'voir tes amis, dormir et t\'ennuyer un peu (oui, c\'est utile !).',
            null,
            null,
        ],
        [
            'quiz',
            'Quiz : es-tu un pro des écrans ?',
            "1. Après combien de minutes d'écran faut-il faire une pause pour les yeux ?\n"
            ."   a) 20 min · b) 60 min · c) 2 h\n\n"
            ."2. À quelle distance regarder pendant une pause visuelle ?\n"
            ."   a) 20 cm · b) 6 mètres · c) le plus près possible\n\n"
            ."3. Combien de minutes d'activité physique par jour sont conseillées ?\n"
            ."   a) 15 min · b) 30 min · c) 60 min\n\n"
            .'Réponses : 1-a, 2-b, 3-c. Trois bonnes réponses ? Tu es incollable ! 🏆',
            null,
            null,
        ],
        [
            'quiz',
            'Quiz : mon corps et les écrans',
            "1. Pourquoi le cou fait-il mal après une longue session ?\n"
            ."   a) Parce qu'on penche la tête en avant · b) Parce que l'écran est trop lumineux\n\n"
            ."2. Que veut dire « fatigue visuelle » ?\n"
            ."   a) Avoir sommeil · b) Avoir les yeux secs, qui piquent ou qui voient flou\n\n"
            ."3. Que faire quand les poignets tirent ?\n"
            ."   a) Continuer · b) Faire des petits cercles avec les poignets et faire une pause\n\n"
            .'Réponses : 1-a, 2-b, 3-b.',
            null,
            null,
        ],
        [
            'glossaire',
            'Fatigue visuelle',
            'Ce sont tes yeux qui te disent « stop ». Les signes : yeux qui piquent, qui pleurent '
            .'ou qui sont secs, vision floue, mal de tête. C\'est très fréquent après une longue '
            .'période devant un écran, parce qu\'on cligne trois fois moins des yeux ! '
            .'Bonne nouvelle : ça disparaît avec des pauses régulières.',
            null,
            null,
        ],
        [
            'glossaire',
            'Lumière bleue',
            'Une lumière émise par les écrans. Le soir, elle trompe ton cerveau qui croit '
            .'qu\'il fait encore jour et retarde l\'endormissement. C\'est pour ça qu\'on '
            .'conseille d\'éteindre les écrans une heure avant le coucher.',
            null,
            null,
        ],
        [
            'glossaire',
            'Sédentarité',
            'C\'est le fait de rester assis ou allongé très longtemps sans bouger. '
            .'Bouger au moins 60 minutes par jour aide ton cœur, tes muscles, ton sommeil '
            .'et même ton humeur. Marcher, danser dans ta chambre ou jouer dehors, tout compte !',
            null,
            null,
        ],
        [
            'video',
            'Réveil en douceur : 5 minutes pour bien démarrer',
            "Une routine du matin pour se réveiller sans écran :\n"
            ."• Étire-toi comme un chat, bras au-dessus de la tête.\n"
            ."• Fais 10 petits sauts sur place.\n"
            ."• Bois un grand verre d'eau.\n"
            .'• Ouvre les rideaux : la lumière du jour aide ton corps à se réveiller.',
            'https://www.youtube.com/results?search_query=reveil+musculaire+enfant',
            null,
        ],
        [
            'fiche',
            'Les écrans et les émotions',
            "Parfois on va sur un écran parce qu'on s'ennuie, qu'on est triste ou énervé.\n\n"
            ."C'est normal ! Mais l'écran ne fait pas disparaître l'émotion : il la met en pause.\n\n"
            .'La prochaine fois, essaie d\'abord d\'en parler à quelqu\'un en qui tu as confiance, '
            .'de dessiner, de sortir ou d\'écouter de la musique. Puis compare comment tu te sens.',
            null,
            null,
        ],
        [
            'exercice',
            'La pause « secoue-toi »',
            "Toutes les heures d'écran, lance un chrono d'une minute et enchaîne :\n"
            ."• 10 secondes à secouer les mains et les bras\n"
            ."• 10 secondes de rotation des épaules\n"
            ."• 20 secondes à marcher dans la pièce\n"
            ."• 20 secondes à regarder par la fenêtre\n\n"
            .'Une minute suffit à relancer la machine !',
            null,
            null,
        ],
    ];
```

Contraintes : mots de passe hachés avec `UserPasswordHasherInterface` ; aucune
donnée personnelle réelle ; dates **relatives** à aujourd'hui pour que les âges
restent entre 8 et 14 ans et que chaque enfant ait un journal du jour, quel que
soit le jour du chargement.

### 2. Confort et vérifications (`Makefile`)

Ajoute ou complète les cibles (`migrate` existe déjà depuis la phase 02) :

| Cible | Rôle |
|---|---|
| `install` | conteneurs, dépendances, base, migrations, fixtures |
| `fixtures` | recharge les données de démonstration |
| `reset-db` | supprime, recrée, migre et recharge |
| `lint` | `lint:twig`, `lint:yaml`, `lint:container`, `doctrine:schema:validate` |

Chaque cible affiche la commande qu'elle exécute. Les fixtures **purgent** la
base : rappelle-le dans l'aide du `Makefile`.

### 3. Documentation

Mets à jour le `README.md` : installation, comptes de démonstration, commandes
utiles, commande pour supprimer le journal du jour (afin de rejouer le parcours
en 2 étapes), problèmes fréquents (port déjà utilisé, journal déjà rempli,
erreur après un `git pull` → `make migrate`).

## Contraintes techniques et architecturales

- Les données de démonstration vont dans `AppFixtures`, **jamais** en dur dans
  le code applicatif ou les gabarits.
- Pas de nouvelle fonctionnalité, pas de refonte de l'existant « au passage ».
- Si une vérification échoue (linter, parcours au navigateur), dis-le-moi et
  propose la correction **séparément**.

## Commandes attendues

```bash
make fixtures
make reset-db
make lint
```

## Ce qui n'est PAS dans cette phase

- Pas de tests automatisés (PHPUnit) : **hors périmètre du parcours**.
- Pas de nouvelle fonctionnalité métier.
- Pas de mise en production.

## Scénario de test manuel

1. Lancer `make reset-db` pour repartir d'une base propre remplie par les fixtures.
2. Se connecter successivement avec les trois comptes de démonstration : administrateur, parent, enfant.
3. Ouvrir le tableau de bord parent : la courbe des 7 derniers jours est remplie, celle des 30 derniers jours l'est sur ses 15 derniers jours.
4. Supprimer le journal du jour (commande du README), puis remplir un journal complet en tant qu'enfant et lire les conseils.
5. Lancer `make lint`.
6. **Résultat attendu** : les trois connexions fonctionnent, le graphique contient l'historique des fixtures, le journal se rejoue sans erreur et les quatre linters affichent `[OK]`.

## Critères de validation

- [ ] `make install` remonte un environnement complet sur une machine vierge.
- [ ] Les fixtures créent au moins un contenu par règle déclencheuse.
- [ ] Les enfants de démonstration ont un journal **du jour** ; la commande pour
      le supprimer est documentée.
- [ ] Recharger deux fois les fixtures donne les mêmes données.
- [ ] `make lint` affiche quatre `[OK]`.
- [ ] Le README permet à quelqu'un d'autre d'installer le projet sans aide.

## Enfin

- N'écris **aucun test automatisé** : je valide au navigateur.
- Termine en me donnant une **checklist de vérification manuelle** (10 points
  maximum) à rejouer au navigateur avant chaque livraison, en reprenant les
  scénarios les plus importants des phases précédentes.
