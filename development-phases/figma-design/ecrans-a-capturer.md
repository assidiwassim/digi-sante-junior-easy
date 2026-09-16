# Écrans à capturer

Liste complète des écrans de Digi-Santé Junior, dans l'ordre où les capturer.
Cochez au fur et à mesure.

**Site** : <https://digisante.150.lebondeveloppeur.net>
**Comptes** : voir [`../../test-prod.txt`](../../test-prod.txt)

> **Priorités.** Le plan gratuit de html.to.design donne **10 imports par
> mois**. Les 5 écrans **P1** racontent déjà le produit entier ; les **P2**
> complètent les parcours ; les **P3** sont les variantes et les états rares.

---

## P1 — Les 5 écrans indispensables

| ✓ | Écran | URL | Compte | À préparer avant de capturer |
|---|---|---|---|---|
| ☐ | Accueil public | `/` | — | rien |
| ☐ | Connexion enfant | `/connexion-enfant` | — | rien |
| ☐ | Accueil enfant | `/enfant` | `lea` | rien — Léa dépasse sa limite, l'alerte est visible |
| ☐ | Journal, étape 2 (schéma du corps) | `/enfant/journal/etape/2` | `lea` | supprimer le journal du jour (voir plus bas), puis faire l'étape 1, **et ouvrir la modale d'intensité** |
| ☐ | Tableau de bord parent | `/parent` | `parent@digisante.local` | laisser le graphique finir de s'animer |

---

## P2 — Les parcours complets

| ✓ | Écran | URL | Compte | À préparer |
|---|---|---|---|---|
| ☐ | Connexion parent | `/login` | — | rien |
| ☐ | Inscription | `/inscription` | — | rien |
| ☐ | Journal, étape 1 (curseurs) | `/enfant/journal/etape/1` | `lea` | journal du jour supprimé ; bouger les curseurs pour ~2 h 30 |
| ☐ | Conseils | `/enfant/journal/conseils` | `lea` | plusieurs conseils déclenchés |
| ☐ | Bibliothèque | `/enfant/bibliotheque` | `lea` | rien |
| ☐ | Mes enfants | `/parent/enfants` | `parent@digisante.local` | rien |
| ☐ | Contenus (admin) | `/admin/contenus` | `admin@digisante.local` | rien |

---

## P3 — Variantes et états

| ✓ | Écran | URL | Compte | À préparer |
|---|---|---|---|---|
| ☐ | Accueil enfant, 30 jours | `/enfant?periode=30` | `lea` | — |
| ☐ | Conseils « Super journée ! » | `/enfant/journal/conseils` | `ines` | Inès a des journées équilibrées |
| ☐ | Profil enfant | `/enfant/profil` | `lea` | — |
| ☐ | Tableau de bord, 30 jours | `/parent?periode=30` | parent | — |
| ☐ | Ajouter un enfant | `/parent/enfants/nouveau` | parent | — |
| ☐ | Ajouter un enfant — **erreurs** | `/parent/enfants/nouveau` | parent | soumettre avec une date de naissance d'un enfant de 4 ans et un mot de passe de 3 caractères |
| ☐ | Modifier un enfant | `/parent/enfants/{id}/modifier` | parent | — |
| ☐ | Profil parent | `/parent/profil` | parent | — |
| ☐ | Nouveau contenu | `/admin/contenus/nouveau` | admin | — |
| ☐ | Parents (admin) | `/admin/parents` | admin | — |
| ☐ | Fiche parent (admin) | `/admin/parents/{id}` | admin | — |

---

## P3 bis — Les états vides

Ils font partie du design et sont presque toujours oubliés.

| ✓ | Écran | Comment l'obtenir |
|---|---|---|
| ☐ | Tableau de bord sans enfant | créer un compte parent neuf via `/inscription`, puis ouvrir `/parent` |
| ☐ | Liste d'enfants vide | même compte, `/parent/enfants` |
| ☐ | Journal non rempli | supprimer le journal du jour, puis ouvrir `/enfant` |

---

## Préparer l'état « journal non rempli »

Les quatre enfants de démonstration ont déjà rempli leur journal du jour : la
page `/enfant/journal` redirige donc directement vers les conseils. Pour
capturer les deux étapes du formulaire, il faut libérer la journée, en SSH sur
le serveur :

```bash
cd /home/user/digi-sante-junior-easy
alias dcp='docker compose --env-file .env.prod -f compose.prod.yaml'
dcp exec app php bin/console dbal:run-sql "DELETE FROM journal_entree WHERE date = CURDATE()"
```

Cette commande efface **uniquement** les journaux du jour, pour les quatre
enfants. Les semaines précédentes restent en place : les graphiques resteront
remplis.

Pour tout remettre en état après vos captures :

```bash
dcp exec app composer install
dcp exec app php bin/console --env=dev doctrine:fixtures:load --no-interaction
dcp exec app composer install --no-dev --optimize-autoloader
dcp exec app php bin/console cache:clear
```

---

## Largeurs à capturer

| Écran | 1440 px | 390 px |
|---|:---:|:---:|
| Les 5 écrans **P1** | ✅ | ✅ |
| Les écrans **P2** | ✅ | au besoin |
| Les écrans **P3** | ✅ | non |

Le mobile compte vraiment ici : la barre de navigation se replie, les cartes
s'empilent, et l'espace enfant est conçu pour être utilisé sur téléphone.

---

## Convention de nommage des frames

Renommez chaque frame **dès l'import**, sinon vous aurez quinze frames
appelées `https://digisante.150…` :

```text
<numéro> · <espace> · <écran> · <largeur>

01 · Public · Accueil · 1440
03 · Enfant · Accueil · 390
04 · Enfant · Journal étape 2 · 1440
05 · Parent · Tableau de bord · 1440
17 · Parent · Ajouter un enfant — erreurs · 1440
```

Le numéro en tête garde l'ordre de lecture dans le panneau des calques, quel
que soit l'ordre des imports.
