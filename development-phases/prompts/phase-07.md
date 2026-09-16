# Prompt Claude Code — Phase 07 : Journal quotidien en 2 étapes

> Copiez tout ce qui suit dans Claude Code, à la racine du projet.

---

## Contexte du projet

**Digi-Santé Junior** : application Symfony de suivi du bien-être numérique des
enfants de 8 à 14 ans. L'enfant se connecte avec un identifiant, le parent avec
son email.

Déjà en place : entités `User` et `Enfant` (avec avatar et limite quotidienne
d'écran), espace parent (CRUD des enfants), espace enfant (connexion, accueil,
profil), filtre Twig `duree`, charte Bootstrap 5.3 par CDN.

Stack : PHP 8.4, Symfony 7.4, Doctrine ORM 3, Twig, MySQL 8, Docker.
**JavaScript vanilla uniquement**, pas de bundler, pas de npm.

## Objectif de la phase

Le cœur du produit : l'enfant déclare **chaque jour**, en moins d'une minute,
son temps d'écran (étape 1) puis les endroits où il a mal sur un schéma du corps
(étape 2). Le journal complet est enregistré **en une seule fois**, à la fin.

## Avant de coder

1. Lis `src/Entity/Enfant.php`, `src/Controller/Enfant/AccueilController.php`,
   `src/Twig/DureeExtension.php` et `templates/enfant/layout.html.twig`.
2. Vérifie le fuseau horaire : PHP et MySQL doivent tous deux être en
   `Europe/Paris` (variable `TZ` du service `database`).
3. Présente-moi le découpage des fichiers avant de les créer.

## À implémenter

### 1. Entités

**`JournalEntree`**

| Propriété | Type | Règles |
|---|---|---|
| `enfant` | `ManyToOne` vers `Enfant` | non nullable, `onDelete: 'CASCADE'` |
| `date` | `date_immutable` | jour seul (minuit), initialisé à « today » |
| `ecranTv`, `ecranOrdinateur`, `ecranSmartphone`, `ecranTablette`, `ecranConsole`, `ecranAutre` | `int` | minutes, défaut 0 |
| `douleurs` | `OneToMany` vers `DouleurZone` | `cascade: ['persist', 'remove']` |

- **Index unique sur `(enfant_id, date)`** : un seul journal par enfant et par
  jour, garanti **en base**.
- Constante `JournalEntree::ECRANS` : nom de propriété → libellé affiché
  (`'ecranTv' => '📺 Télévision'`, etc.), utilisée par le formulaire.
- `getTotalEcran()` : somme des six durées.
- `niveauPourMinutes(int $minutes): string` (statique) : `vert` sous 2 h,
  `orange` de 2 h à 4 h, `rouge` au-delà — plus `getNiveauEcran()`.

**`DouleurZone`**

- `journalEntree` (`ManyToOne`, `onDelete: 'CASCADE'`), `zone` (string),
  `intensite` (int, 1 à 5), constructeur `__construct(string $zone, int $intensite)`.
- Constante `DouleurZone::ZONES` : 6 zones avec leur libellé et leur emoji —
  `yeux`, `cou`, `epaule`, `dos`, `poignet`, `main`. Les clés correspondent à
  l'attribut `data-zone` du SVG.
- Getters d'affichage `getZoneLabel()` et `getZoneEmoji()`.

Génère la migration, fais-la-moi relire, applique-la, puis
`doctrine:schema:validate`.

### 2. Contrôleur `Enfant\JournalController` (préfixe `/enfant/journal`)

- `enfant_journal` : point d'entrée. Si le journal du jour existe déjà →
  rediriger vers l'écran de fin ; sinon vider la session et aller à l'étape 1.
- `enfant_journal_etape1` (GET + POST) : formulaire des écrans. À la validation,
  ranger les valeurs **en session** et rediriger vers l'étape 2.
- `enfant_journal_etape2` (GET + POST) : schéma corporel. Sans données d'étape 1
  en session → rediriger vers l'étape 1. À la validation, créer le
  `JournalEntree` complet, ajouter les douleurs, enregistrer, vider la session.
- Un écran de fin `enfant_journal_conseils` : pour l'instant, un récapitulatif
  (temps total et douleurs signalées) et un bouton de retour à l'accueil. Les
  vrais conseils arrivent en phase 09.

⚠️ **Rien ne doit être écrit en base avant la fin de l'étape 2** : si l'enfant
abandonne, aucun journal à moitié rempli ne reste enregistré.

### 3. Formulaires

**`JournalEcransType`** — un `RangeType` par écran, **non lié à une entité** (il
renvoie un simple tableau `['ecranTv' => '30', …]`) :

- plage 0 à 360 minutes, pas de 15, avec une contrainte `Assert\Range` par champ ;
- ⚠️ **valeurs de départ à zéro** : sans valeur, le navigateur place un curseur
  **au milieu** de sa plage. Fixe-les via l'option `data` du formulaire, en
  veillant à ce que les valeurs de la session restent prioritaires au retour de
  l'étape 2 ;
- un plafond sur le **total de la journée** (16 h, tous écrans confondus) : sans
  lui, six curseurs à 6 h donneraient 36 h. Utilise `Assert\Callback` dans
  l'option `constraints` du formulaire (règle portant sur plusieurs champs) ;
  message écrit pour l'enfant, affiché par `{{ form_errors(form) }}`.

**`JournalDouleursType`** — un seul champ caché `douleurs`, rempli en JSON par
le JavaScript (`{"cou": 3, "yeux": 2}`). Passer par un formulaire Symfony
apporte la protection **CSRF**.

### 4. Étape 1 — l'écran des curseurs

- Un curseur par type d'écran, avec son libellé et son emoji.
- **Total mis à jour en direct** en JavaScript : durée formatée (« 2 h 30 »),
  jauge colorée selon le même barème que le PHP (vert / orange / rouge), et un
  message d'encouragement. Prévenir aussi si le total dépasse le plafond.
- Un indicateur de progression « étape 1 sur 2 ».
- Boutons « ← Annuler » et « Suivant : mon corps → ».

### 5. Étape 2 — le schéma corporel

- Un **SVG** dessinant une silhouette, avec 6 zones cliquables portant un
  attribut `data-zone` correspondant aux clés de `DouleurZone::ZONES`.
- Au clic sur une zone : une **modale Bootstrap** propose l'intensité de 1 à 5
  (« 1 = un tout petit peu, 5 = très très mal ») et un bouton « Enlever ».
- La zone choisie se colore selon l'intensité ; la liste « Ce que tu as
  signalé » se met à jour ; le champ caché est réécrit en JSON à chaque
  changement.
- L'étape est **facultative** : on peut terminer sans aucune douleur.
- Boutons « ← Retour » et « 🎉 Terminer mon journal ».

⚠️ Sécurité côté JavaScript : insérer les textes avec `textContent`, **jamais**
une donnée dans `innerHTML`. Les libellés des zones sont passés au JS avec
`{{ zones|json_encode|raw }}` (constante PHP, pas une saisie utilisateur).

### 6. Revalidation côté serveur

Les douleurs arrivent du navigateur : le contrôleur **revérifie chaque valeur**.
Toute zone inconnue ou intensité hors 1-5 est **ignorée silencieusement**, sans
erreur. Ce n'est pas une option : c'est la règle du projet pour toute donnée
hors formulaire Symfony.

### 7. Compléter l'accueil enfant

Sur `/enfant`, ajouter :

- la **jauge du jour** : temps d'écran total, pourcentage de la limite, couleur
  selon le niveau, et une alerte si la limite est dépassée ;
- si le journal n'est pas rempli : « Raconte-moi ta journée ! » + bouton
  **Remplir mon journal** ;
- s'il est rempli : félicitations et rappel des douleurs signalées.

Ajoute l'entrée **📔 Mon journal** au menu du layout enfant.

## Contraintes techniques et architecturales

- Les requêtes Doctrine vivent dans `JournalEntreeRepository`
  (`findAujourdhui(Enfant $enfant)`), jamais dans le contrôleur.
- Pas d'enum PHP : des constantes d'entité avec des getters d'affichage.
- Pas de logique métier dans Twig.
- JavaScript vanilla, dans le bloc `javascripts` de la page concernée (un
  fichier dans `public/js/` seulement s'il sert à plusieurs pages).
- Textes au tutoiement, positifs, jamais culpabilisants.
- ⚠️ Un formulaire Symfony refuse les champs inconnus : le nom des champs HTML
  doit correspondre exactement au `*Type`.

## Commandes attendues

```bash
docker compose exec app php bin/console make:entity JournalEntree
docker compose exec app php bin/console make:entity DouleurZone
docker compose exec app php bin/console make:migration
docker compose exec app php bin/console doctrine:migrations:migrate --no-interaction
docker compose exec app php bin/console doctrine:schema:validate
```

Pour recommencer un journal pendant les essais :

```bash
docker compose exec app php bin/console dbal:run-sql "DELETE FROM journal_entree WHERE date = CURDATE()"
```

## Ce qui n'est PAS dans cette phase

- Pas de moteur de conseils : l'écran de fin n'affiche qu'un récapitulatif
  (phase 09).
- Pas de bibliothèque de contenus (phase 08).
- Pas de graphique d'historique (phase 10).
- Pas de modification d'un journal déjà enregistré : **hors périmètre**.

## Scénario de test manuel

1. Connecté en enfant, cliquer sur « 📔 Mon journal » : vérifier que les six curseurs sont **à zéro**.
2. Étape 1 : régler les curseurs pour atteindre environ 2 h 30 au total, vérifier que le total et la jauge se mettent à jour en direct, puis continuer.
3. Étape 2 : cliquer sur le cou, choisir l'intensité 4, cliquer sur les yeux, choisir 2, puis terminer le journal.
4. Revenir sur « Mon journal » une seconde fois dans la même journée, puis ouvrir l'accueil.
5. **Résultat attendu** : le récapitulatif affiche « 2 h 30 » et les deux douleurs ; la seconde visite ne propose plus le formulaire ; l'accueil affiche la jauge du jour et l'état « journal rempli ».

## Critères de validation

- [ ] Un seul journal par enfant et par jour, garanti par l'index unique.
- [ ] Abandonner après l'étape 1 ne laisse **rien** en base.
- [ ] Un total supérieur à 16 h est refusé avec un message compréhensible.
- [ ] Une zone inconnue envoyée à la main (outils de développement) est ignorée,
      sans erreur 500.
- [ ] `doctrine:schema:validate` et `lint:twig templates` sont au vert.

## Enfin

- N'écris **aucun test automatisé** : je valide au navigateur.
- Termine en m'expliquant en quelques lignes pourquoi l'étape 1 est gardée en
  session plutôt qu'enregistrée immédiatement en base.
