# Prompt Claude Code — Phase 06 : Espace enfant, connexion par identifiant, accueil et profil

> Copiez tout ce qui suit dans Claude Code, à la racine du projet.

---

## Contexte du projet

**Digi-Santé Junior** : application Symfony de suivi du bien-être numérique des
enfants de 8 à 14 ans. Trois rôles sans hiérarchie : `ROLE_ADMIN`,
`ROLE_PARENT`, `ROLE_CHILD`.

Déjà en place : entité `User` (email **ou** identifiant), entité `Enfant` avec
son compte de connexion créé par le parent, espace parent fonctionnel,
`EnfantVoter`, filtre Twig `duree`, charte Bootstrap.

Particularité : les parents et l'administrateur se connectent avec leur
**email** sur `/login`, les enfants avec un **identifiant** (ex. `lea`) sur une
page qui leur est propre.

Stack : PHP 8.4, Symfony 7.4, Doctrine ORM 3, Twig, Bootstrap 5.3 par CDN,
MySQL 8, Docker. Code simple, en français.

## Objectif de la phase

Ouvrir l'espace enfant : une page de connexion adaptée à son âge, un accueil
personnalisé et une page de profil où il peut changer son mot de passe.

## Avant de coder

1. Lis `src/Repository/UserRepository.php`, `config/packages/security.yaml`,
   `src/Entity/Enfant.php` et `templates/security/login.html.twig`.
2. Vérifie comment le provider de sécurité charge actuellement l'utilisateur.
3. Dis-moi ce que tu vas modifier dans la sécurité existante **sans casser** la
   connexion des parents.

## À implémenter

### 1. Charger l'utilisateur par email **ou** identifiant

`UserRepository` implémente `UserLoaderInterface` :

```php
public function loadUserByIdentifier(string $identifier): ?User
```

La requête cherche dans `u.email` **ou** `u.username`, sur la valeur en
minuscules et sans espaces autour. C'est pour cela que le provider de
`security.yaml` n'a **pas** d'option `property` — vérifie que c'est bien le cas.

### 2. Page de connexion enfant

- Route `/connexion-enfant` (GET, nom `app_enfant_login`), dans
  `SecurityController`, avec `AuthenticationUtils` pour l'erreur et le dernier
  identifiant saisi.
- Le formulaire est **envoyé à `/login`**, comme celui des parents : c'est
  Symfony qui traite l'authentification.
- Il contient un champ caché `_failure_path` pour revenir sur cette page en cas
  d'erreur.
  ⚠️ `_failure_path` attend un **chemin** (`path('app_enfant_login')`), pas un
  nom de route.
- Champs `_username` et `_password`, plus le jeton `csrf_token('authenticate')`.
- Ton adapté à l'enfant : pas de barre de navigation, mascotte 🦊, titre
  « Coucou, c'est toi ? », gros champs centrés, bouton « C'est parti ! 🚀 ».
- En cas d'échec : message **bienveillant**, jamais technique — « Oups !
  L'identifiant ou le mot de passe n'est pas le bon. Essaie encore. »
- Un lien discret « Je suis un parent → » vers `/login`, et l'inverse sur la
  page parent.

### 3. Layout de l'espace enfant

`templates/enfant/layout.html.twig` (étend `base.html.twig`) :

- fond coloré propre à l'espace enfant (via le bloc `body_class`) ;
- menu : **🏠 Accueil**, **📔 Mon journal** (la route arrivera en phase 07,
  prévois-la ou mets le lien en place au dernier moment), **📚 Découvrir**
  (phase 08) ;
- menu utilisateur : avatar + prénom, lien « Mon profil », déconnexion ;
- lien actif mis en évidence selon la route courante.

Ne crée que les entrées de menu dont les routes existent à la fin de cette
phase : les autres seront ajoutées par leur propre phase.

### 4. `Enfant\AccueilController`

Préfixe `/enfant`, réservé à `ROLE_CHILD` par `access_control`.

- `/enfant` (route `enfant_accueil`) : salutation « Salut [prénom] ! 👋 »,
  avatar en grand, date du jour en toutes lettres et en français, et un rappel
  de sa limite quotidienne avec le filtre `duree`.
- `/enfant/profil` (route `enfant_profil`) : carte d'identité en lecture seule
  (avatar et son nom, prénom, identifiant de connexion, âge, limite d'écran) et
  un formulaire de **changement de mot de passe** réutilisant `MotDePasseType`.
- Le profil enfant se récupère avec `#[CurrentUser] User $user` puis
  `$user->getProfilEnfant()` ; si aucun profil n'est rattaché, lève une 404
  explicite.
- Textes au **tutoiement**, encourageants : « Ton nouveau mot de passe est
  enregistré. Pense à bien le retenir ! »

## Contraintes techniques et architecturales

- **Aucun authenticator maison** : la connexion passe par `form_login`.
- Injection des dépendances en argument de l'action, utilisateur connecté via
  `#[CurrentUser]`.
- Les mots de passe sont hachés avec `UserPasswordHasherInterface`, jamais
  affichés ni générés automatiquement.
- Chaque page étend le layout de son espace, qui étend `base.html.twig`.
- Réutilise les classes maison de `app.css` (`avatar-bulle`, `pastille`,
  `profil-ligne`, `texte-doux`…) plutôt que d'en inventer.
- Toujours `{{ form_errors(form) }}` juste après `form_start()`.

## Commandes attendues

```bash
docker compose exec app php bin/console debug:router
docker compose exec app php bin/console lint:twig templates
docker compose exec app php bin/console cache:clear
```

## Ce qui n'est PAS dans cette phase

- Pas de journal, pas de schéma corporel, pas de jauge du jour (phase 07).
- Pas de bibliothèque de contenus (phase 08).
- Pas de conseils (phase 09), pas de graphique (phase 10).
- L'accueil enfant reste volontairement simple : il sera complété par les
  phases suivantes.

## Scénario de test manuel

1. Se déconnecter, puis ouvrir `/connexion-enfant`.
2. Saisir volontairement un mauvais mot de passe et valider.
3. Vérifier qu'on **revient sur `/connexion-enfant`** (et non sur `/login`) avec le message bienveillant.
4. Saisir le bon identifiant et le bon mot de passe, puis ouvrir « Mon profil », changer le mot de passe, se déconnecter et se reconnecter avec le nouveau.
5. **Résultat attendu** : la connexion par identifiant fonctionne, l'échec ramène sur la page enfant, et le nouveau mot de passe est accepté.

## Critères de validation

- [ ] La connexion **parent** par email fonctionne toujours.
- [ ] Un enfant connecté qui ouvre `/parent` ou `/admin` reçoit **403**.
- [ ] L'identifiant est insensible à la casse (`LEA` fonctionne).
- [ ] La page de connexion enfant n'affiche aucun message technique.
- [ ] `lint:twig templates` est au vert.

## Enfin

- N'écris **aucun test automatisé** : je valide au navigateur.
- Termine en m'expliquant en quelques lignes pourquoi les deux pages de
  connexion envoient leur formulaire à la **même** adresse.
