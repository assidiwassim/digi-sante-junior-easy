# Prompt Claude Code — Phase 09 : Moteur de conseils

> Copiez tout ce qui suit dans Claude Code, à la racine du projet.
>
> 📸 **Joignez aussi les 2 captures** listées dans la section « Captures
> d'écran de référence » : elles sont dans le dossier [`captures/`](../captures/).
> Glissez chaque fichier dans la fenêtre de Claude Code (ou copiez l'image puis
> collez-la avec Ctrl+V) avant d'envoyer le prompt.

---

## Contexte du projet

**Digi-Santé Junior** : application Symfony de suivi du bien-être numérique des
enfants de 8 à 14 ans.

Déjà en place : comptes et rôles, espace parent (profils enfants avec une limite
quotidienne d'écran), espace enfant (accueil, profil, **journal quotidien en 2
étapes** : temps d'écran + douleurs sur un schéma du corps), et une
**bibliothèque de contenus** administrable dont chaque contenu peut porter une
règle déclencheuse (`20-20-20`, `neck_stretching`, `eye_yoga`).

Stack : PHP 8.4, Symfony 7.4, Doctrine ORM 3, Twig, MySQL 8, Docker. Code
simple, sans sur-ingénierie.

**Langue du projet** : tout le **code est en anglais** — classes, méthodes,
propriétés, variables, routes et URLs, tables et colonnes, classes CSS,
fonctions JavaScript et **commentaires** (ex. `Child`, `JournalEntry`,
`getTotalScreenTime()`, `/parent/children`, `child_home`). Tout ce que voit
l'utilisateur reste en **français** : libellés, boutons, messages flash,
messages de validation, titres de pages, contenus.

## Objectif de la phase

Transformer le journal du jour en **conseils personnalisés**, affichés à la fin
du parcours et consultables toute la journée. Les conseils doivent encourager,
jamais culpabiliser.

## Avant de coder

1. Lis `src/Entity/JournalEntry.php`, `src/Entity/PainZone.php`,
   `src/Entity/WellnessContent.php`, `src/Controller/Child/JournalController.php`
   et `src/Twig/DurationExtension.php`.
2. Repère l'écran de fin provisoire créé en phase 07 : tu vas le compléter, pas
   le réécrire entièrement.
3. Explique-moi pourquoi ce code mérite un **service** avant de le créer.

## Captures d'écran de référence

Je joins à ce prompt des captures de l'application terminée, qui montrent le
rendu attendu pour cette phase :

- `09-enfant-conseils.png`
- `09-enfant-conseils-super-journee.png`

Reproduis la mise en page, les textes, les emojis et les couleurs visibles, avec
les classes Bootstrap et la charte de `public/css/app.css`. Les captures ne
remplacent pas ce prompt : en cas de doute, le texte du prompt fait foi.

`09-enfant-conseils` correspond à Tom : 2 h 30 d'écran pour une limite de 1 h
30, cou à 4/5 et yeux à 2/5 ; `09-enfant-conseils-super-journee`, à une journée
où aucune règle ne se déclenche.

## À implémenter

### 1. Le service `AdviceService`

Une seule classe dans `src/Service/`, avec une méthode publique :

```php
public function getAdvice(JournalEntry $journal): array
```

Elle renvoie une **liste de tableaux simples** — surtout pas de DTO, pas
d'interface, pas de classe de valeur :

```php
['title' => …, 'message' => …, 'emoji' => …, 'color' => 'orange'|'green', 'content' => ?WellnessContent]
```

**Les règles**, dans cet ordre :

| # | Condition | `title` | `message` (exact) | `emoji` | Contenu associé |
|---|---|---|---|---|---|
| 1 | temps d'écran total **≥ 2 h** (et limite non dépassée) | « Repose tes yeux avec le 20-20-20 » | « Tu as passé [total] devant un écran. Toutes les 20 minutes, regarde quelque chose à 20 pieds (environ 6 mètres) pendant 20 secondes. » | 👁️ | déclencheur `20-20-20` |
| 2 | temps d'écran total **> limite du parent** | « Tu as dépassé ta limite d'écran » | « Aujourd'hui : [total] d'écran pour une limite de [limite]. Ce n'est pas grave, mais demain essaie de faire une pause plus tôt ! 💪 » | 📵 | déclencheur `20-20-20` |
| 3 | douleur au **cou** ou aux **épaules** d'intensité **≥ 3** | « Détends ton cou et tes épaules » | « Tu as signalé une douleur au niveau du haut du corps. Voici une vidéo d'étirements tout doux à faire assis ou debout. » | 🧘 | `neck_stretching` |
| 4 | douleur aux **yeux**, quelle que soit l'intensité | « Un peu de yoga des yeux » | « Tes yeux sont fatigués. Fais-les bouger doucement de haut en bas, de gauche à droite, puis frotte tes mains et pose-les sur tes paupières fermées. » | 👀 | `eye_yoga` |
| 5 | aucune règle déclenchée | « Super journée ! » | « Tes écrans sont bien maîtrisés et ton corps va bien. Continue comme ça ! 🌈 » | 🎉 | aucun (`null`) |

Couleur : `orange` pour les règles 1 à 4, `green` pour la règle 5.

- Les règles 1 et 2 sont **exclusives** : un seul conseil sur les écrans, jamais
  deux ; la règle 2 est prioritaire.
- Les règles 3 et 4 peuvent s'ajouter au conseil sur les écrans.
- Le seuil de 2 h est celui où la jauge passe à l'orange
  (`JournalEntry::levelForMinutes()`) : les deux doivent rester **cohérents**,
  d'où le `≥`.
- Les seuils (120 minutes, intensité 3) sont des **constantes privées** de la
  classe, commentées.
- Les durées affichées dans les messages passent par le formatage existant
  (« 2 h 30 »), pas par une concaténation maison.

### 2. Le texte des exercices vient de la base

`WellnessContentRepository::findFirstForTrigger(string $triggerRule)` :
renvoie le **premier** contenu (par id) rattaché à cette règle, ou `null`.

Ainsi l'administrateur peut modifier le texte d'un exercice sans toucher au
code. Si aucun contenu n'est rattaché, le conseil s'affiche **sans** contenu
associé — jamais d'erreur.

### 3. L'écran de conseils

Compléter `/child/journal/advice` :

- félicitations et récapitulatif : date du journal, temps d'écran total,
  pastilles des douleurs signalées (zone + intensité sur 5) ou message
  « 🌟 Tu n'avais mal nulle part » ;
- la liste des conseils : emoji, titre, message, et — quand il existe — le
  contenu de la bibliothèque associé (type, titre, texte) avec un bouton vers sa
  ressource externe (nouvel onglet, `rel="noopener"`) ;
- deux boutons : « 🏠 Retour à l'accueil » et « 📚 Découvrir d'autres conseils » ;
- sans journal du jour, rediriger vers le formulaire.

Sur l'accueil enfant, **ajoute**, quand le journal est rempli, un bouton
« 💡 Revoir mes conseils » vers `child_journal_advice`.

## Contraintes techniques et architecturales

- **Un seul service métier** dans ce projet : `AdviceService`. On le crée
  uniquement parce que le code sera partagé par l'espace enfant **et** par le
  tableau de bord parent (phase 10).
- Injection par le constructeur pour le service, en argument de l'action pour
  les contrôleurs (autowiring).
- Aucune logique métier dans Twig : le gabarit se contente d'afficher le tableau
  renvoyé par le service.
- Les requêtes Doctrine restent dans les repositories.
- Textes au **tutoiement**, positifs, jamais culpabilisants.
- Méthodes courtes, une responsabilité chacune ; commentaires qui expliquent le
  **pourquoi**.

## Commandes attendues

```bash
docker compose exec app php bin/console debug:autowiring Conseil --all
docker compose exec app php bin/console cache:clear
docker compose exec app php bin/console lint:container
```

Pour rejouer un journal pendant les essais :

```bash
docker compose exec app php bin/console dbal:run-sql "DELETE FROM journal_entry WHERE date = CURDATE()"
```

## Ce qui n'est PAS dans cette phase

- Pas de tableau de bord parent ni de graphique (phase 10) — mais conçois le
  service pour qu'il soit réutilisable tel quel.
- Pas de nouvelle règle métier : s'en tenir aux cinq ci-dessus.
- Pas de notification, pas d'email, pas de badge : **hors périmètre du projet**.

## Scénario de test manuel

1. Supprimer le journal du jour de l'enfant (commande ci-dessus). Dans l'administration, créer si besoin un contenu rattaché à chaque règle (« Règle du 20-20-20 », « Étirements du cou », « Yoga des yeux »).
2. Remplir un nouveau journal avec un temps d'écran **supérieur à la limite** du profil, et signaler une douleur aux yeux.
3. Lire la page de conseils affichée à la fin du parcours.
4. Revenir à l'accueil et cliquer sur « 💡 Revoir mes conseils ».
5. **Résultat attendu** : deux conseils s'affichent — le dépassement de limite (avec le temps et la limite rappelés) et le yoga des yeux, accompagné du contenu de la bibliothèque — et la page est identique au retour depuis l'accueil.

## Critères de validation

- [ ] Un journal sous les 2 h, sous la limite et sans douleur affiche
      **uniquement** « Super journée ! » en vert.
- [ ] Un journal à **exactement 2 h** affiche le conseil 20-20-20 (cohérence
      avec la jauge orange). ⚠️ Teste-le avec un enfant dont la limite est
      **d'au moins 2 h** (monte-la à 3 h au besoin) : avec la limite de 1 h 30
      de la phase 05, c'est le dépassement qui s'affiche, et c'est normal.
- [ ] Un dépassement de limite **remplace** le conseil 20-20-20 au lieu de
      s'ajouter.
- [ ] Une douleur au cou d'intensité 2 ne déclenche **pas** les étirements ;
      à 3, oui.
- [ ] Supprimer le contenu rattaché à une règle n'entraîne **aucune erreur** :
      le conseil s'affiche seul.
- [ ] `lint:container` est au vert.

## Enfin

- N'écris **aucun test automatisé** : je valide au navigateur.
- Termine en m'expliquant en quelques lignes la règle du projet : quand
  crée-t-on un service, et quand garde-t-on le code dans le contrôleur ou
  l'entité ?
