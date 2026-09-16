# Quels écrans photographier

La liste complète des écrans de l'application, dans l'ordre où les prendre.
Cochez au fur et à mesure : `- [ ]` devient `- [x]`.

**Adresse de l'application** : <http://localhost:8081>
(l'application doit tourner : `make start`)

> 💡 **Commencez par les 5 écrans prioritaires.** Le plan gratuit donne
> **10 imports par mois** et le projet compte 22 écrans. Les cinq premiers
> racontent déjà tout le produit.

---

## Comment lire ce tableau

- **Écran** : le nom à donner à la frame dans Figma.
- **Adresse** : à coller après `http://localhost:8081` dans la barre du
  navigateur.
- **Compte** : avec qui être connecté. Déconnectez-vous entre deux rôles
  (menu en haut à droite → *Déconnexion*).
- **À préparer** : ce qu'il faut faire **avant** d'appuyer sur ⌥⇧E.

---

## Priorité 1 — Les 5 écrans indispensables

| ✓ | Écran | Adresse | Compte | À préparer |
|---|---|---|---|---|
| ☐ | Accueil public | `/` | aucun (déconnecté) | rien |
| ☐ | Connexion enfant | `/connexion-enfant` | aucun | rien |
| ☐ | Accueil enfant | `/enfant` | `lea` | rien : Léa dépasse sa limite, l'alerte est visible |
| ☐ | Journal — schéma du corps | `/enfant/journal/etape/2` | `lea` | libérer la journée (voir plus bas), faire l'étape 1, cliquer sur le cou **et laisser la fenêtre d'intensité ouverte** |
| ☐ | Tableau de bord parent | `/parent` | `parent@digisante.local` | attendre que le graphique finisse de s'animer |

---

## Priorité 2 — Les parcours complets

| ✓ | Écran | Adresse | Compte | À préparer |
|---|---|---|---|---|
| ☐ | Connexion parent | `/login` | aucun | rien |
| ☐ | Inscription | `/inscription` | aucun | rien |
| ☐ | Journal — curseurs | `/enfant/journal/etape/1` | `lea` | journée libérée ; bouger les curseurs jusqu'à environ 2 h 30 |
| ☐ | Conseils | `/enfant/journal/conseils` | `lea` | rien |
| ☐ | Bibliothèque | `/enfant/bibliotheque` | `lea` | rien |
| ☐ | Mes enfants | `/parent/enfants` | parent | rien |
| ☐ | Contenus (admin) | `/admin/contenus` | `admin@digisante.local` | rien |

---

## Priorité 3 — Les variantes

| ✓ | Écran | Adresse | Compte | À préparer |
|---|---|---|---|---|
| ☐ | Accueil enfant sur 30 jours | `/enfant?periode=30` | `lea` | rien |
| ☐ | Conseils « Super journée ! » | `/enfant/journal/conseils` | `ines` | Inès a des journées équilibrées |
| ☐ | Profil enfant | `/enfant/profil` | `lea` | rien |
| ☐ | Tableau de bord sur 30 jours | `/parent?periode=30` | parent | rien |
| ☐ | Ajouter un enfant | `/parent/enfants/nouveau` | parent | rien |
| ☐ | Ajouter un enfant — **erreurs** | `/parent/enfants/nouveau` | parent | remplir avec une date de naissance d'un enfant de 4 ans et un mot de passe de 3 lettres, puis valider : les messages rouges apparaissent |
| ☐ | Modifier un enfant | `/parent/enfants` puis bouton ✏️ | parent | rien |
| ☐ | Profil parent | `/parent/profil` | parent | rien |
| ☐ | Nouveau contenu | `/admin/contenus/nouveau` | admin | rien |
| ☐ | Parents (admin) | `/admin/parents` | admin | rien |
| ☐ | Fiche d'un parent | `/admin/parents` puis bouton 👁️ | admin | rien |

---

## Priorité 3 bis — Les écrans « vides »

On les oublie toujours, et ce sont pourtant les premiers que verra un nouvel
utilisateur.

| ✓ | Écran | Comment l'obtenir |
|---|---|---|
| ☐ | Tableau de bord sans enfant | créer un compte parent neuf sur `/inscription`, se connecter, ouvrir `/parent` |
| ☐ | Liste d'enfants vide | avec ce même compte neuf, ouvrir `/parent/enfants` |
| ☐ | Journal pas encore rempli | libérer la journée (ci-dessous), puis ouvrir `/enfant` avec `lea` |

---

## Libérer la journée pour photographier le journal

Les quatre enfants de démonstration ont **déjà rempli leur journal
aujourd'hui**. Si vous cliquez sur « Mon journal », vous arrivez donc
directement sur les conseils, et vous ne verrez jamais le formulaire.

Pour le débloquer, dans un terminal, à la racine du projet :

```bash
docker compose exec app php bin/console dbal:run-sql "DELETE FROM journal_entree WHERE date = CURDATE()"
```

Cette commande efface **seulement** les journaux d'aujourd'hui, pour les quatre
enfants. Les semaines précédentes restent en place, donc les graphiques
resteront bien remplis.

Ensuite, connecté avec `lea`, cliquez sur **📔 Mon journal** : vous arrivez sur
l'étape 1.

**Si vous avez créé vos propres données** (option B du guide), vous n'avez rien
à faire : votre enfant n'a pas encore de journal aujourd'hui.

**Pour tout remettre comme avant**, quand vos photos sont prises :

```bash
make fixtures
```

---

## Quelles largeurs pour quels écrans

| Écrans | 1440 px (ordinateur) | 390 px (téléphone) |
|---|:---:|:---:|
| Priorité 1 | ✅ | ✅ |
| Priorité 2 | ✅ | si vous avez encore des imports |
| Priorité 3 | ✅ | non |

Le téléphone compte vraiment pour ce projet : l'espace enfant est conçu pour
être utilisé dessus, le menu se replie derrière un bouton et les cartes
s'empilent les unes sous les autres.

---

## Comment nommer les frames dans Figma

Renommez **au moment de l'import**, jamais « plus tard » :

```text
<numéro> · <espace> · <écran> · <largeur>

01 · Public · Accueil · 1440
02 · Public · Connexion enfant · 1440
03 · Enfant · Accueil · 1440
03 · Enfant · Accueil · 390
04 · Enfant · Journal étape 2 · 1440
05 · Parent · Tableau de bord · 1440
```

Le numéro au début garde les écrans dans l'ordre de lecture, même si vous les
importez dans le désordre.
