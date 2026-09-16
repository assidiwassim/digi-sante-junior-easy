# Prompt Claude Code — Phase 13 : Performance, robustesse et mise en production

> Copiez tout ce qui suit dans Claude Code, à la racine du projet.

---

## Contexte du projet

**Digi-Santé Junior** : application Symfony de suivi du bien-être numérique des
enfants de 8 à 14 ans. L'application est complète et testée : comptes et rôles,
espace parent (profils enfants, tableau de bord, graphiques), espace enfant
(journal en 2 étapes, conseils, bibliothèque), espace admin (contenus, comptes
parents), fixtures, linters et suite PHPUnit.

Stack : PHP 8.4, Symfony 7.4, Doctrine ORM 3, Twig, Bootstrap et Chart.js par
CDN, MySQL 8, Docker (FrankenPHP + MySQL + phpMyAdmin).

⚠️ L'application manipule des **données de santé d'enfants** : la prudence prime
sur la performance.

## Objectif de la phase

Passer d'un projet qui marche sur ma machine à un projet **prêt à être déployé** :
environnement de production propre, aucune fuite d'information, requêtes SQL
maîtrisées, sauvegardes documentées.

## Avant de coder

1. Parcours `config/packages/`, `.env`, `compose.yaml`, `Dockerfile` et les
   repositories.
2. Lance l'application en environnement de développement et ouvre le **profiler**
   sur les pages les plus lourdes (tableau de bord parent, liste des enfants,
   bibliothèque, administration des parents). Relève le **nombre de requêtes
   SQL** de chacune et montre-le-moi.
3. Propose-moi un plan d'action classé par impact **avant** de modifier quoi que
   ce soit. Ne change rien qui ne soit justifié par une mesure ou par une règle
   de sécurité.

## À implémenter

### 1. Requêtes SQL

- Repère les **requêtes N+1** (une requête par ligne affichée) et corrige-les
  avec des jointures explicites (`->addSelect(…)->join(…)`) dans les
  repositories concernés.
- Vérifie que chaque liste est **triée** et, quand c'est pertinent, **limitée**.
- Supprime toute méthode de repository qui n'est plus utilisée nulle part
  (vérifie par recherche avant de supprimer).

### 2. Environnement de production

- Documenter les variables : `APP_ENV=prod`, `APP_DEBUG=0`, un `APP_SECRET`
  propre à la production, `DATABASE_URL` de production.
- Créer un fichier d'exemple versionné **sans aucun secret réel**
  (`.env.prod.local.dist`), et rappeler que `.env.local` n'est jamais commité.
- Vérifier que le profiler et la barre de debug ne sont **pas** chargés en prod
  (`when@dev` / `when@test` dans la configuration, paquets en `require-dev`).
- Pages d'erreur **sobres** : un visiteur ne doit jamais voir de trace
  technique, ni en 404, ni en 403, ni en 500.
- Vérifier ce que Monolog écrit en prod : les erreurs dans `var/log/`, **jamais**
  de mot de passe ni de donnée de santé dans les journaux.

### 3. Installation de production

Documenter, et vérifier dans le conteneur, la séquence :

```bash
composer install --no-dev --optimize-autoloader
php bin/console cache:clear --env=prod
php bin/console doctrine:migrations:migrate --no-interaction --env=prod
```

- Pas de `doctrine:fixtures:load` en production : les fixtures **purgent** la
  base.
- Vérifier qu'OPcache est actif et que le préchargement (`config/preload.php`)
  est bien pris en compte.

### 4. Revue de sécurité finale

Passe en revue et corrige si besoin :

- chaque route sensible est couverte par `access_control` **ou** par un voter ;
- chaque action qui modifie des données est en **POST** et protégée par CSRF ;
- les données venant du navigateur hors formulaire Symfony sont **revalidées**
  côté serveur ;
- aucune donnée utilisateur n'est insérée via `innerHTML` côté JavaScript ;
- les liens externes s'ouvrent avec `rel="noopener"` ;
- les mots de passe ne sont ni journalisés, ni affichés, ni transmis en clair.

### 5. Exploitation

- Documenter la **sauvegarde** et la **restauration** de la base
  (`mysqldump` / restauration), avec la commande exacte.
- Documenter le **redémarrage** de l'application et la consultation des logs.
- Rappeler que phpMyAdmin est un outil de **développement** : il ne doit pas
  être exposé en production (retire-le de la configuration de prod ou explique
  comment le désactiver).
- Ajouter au README une section « Déploiement » et une section « Conservation
  des données » (données de santé d'enfants : durée de conservation, suppression
  à la demande du parent).

## Contraintes techniques et architecturales

- **Aucune nouvelle fonctionnalité** : uniquement de la robustesse, de la
  configuration et de la documentation.
- Ne casse aucun test existant : `make lint` et `make tests` doivent rester
  verts à la fin.
- Reste dans l'esprit du projet : Symfony classique, pas de cache applicatif
  exotique, pas de service supplémentaire sans besoin démontré.
- Chaque optimisation doit être **mesurée** (nombre de requêtes avant / après),
  pas supposée.

## Commandes attendues

```bash
docker compose exec app php bin/console cache:clear --env=prod
docker compose exec app php bin/console debug:container --env=prod
docker compose exec app php bin/console debug:router --env=prod
docker compose exec app composer install --no-dev --optimize-autoloader
make lint && make tests
```

## Ce qui n'est PAS dans cette phase

- Pas d'API REST, pas d'application mobile : **hors périmètre du projet**.
- Pas d'emails, pas de notifications.
- Pas de refonte graphique.
- Pas de mise en place d'un hébergeur précis : le but est que le projet **puisse**
  être déployé, la cible reste à choisir.

## Scénario de test manuel

1. Passer l'application en `APP_ENV=prod` et `APP_DEBUG=0`, puis vider le cache en prod.
2. Ouvrir l'application et parcourir les trois espaces : la barre de debug ne doit plus apparaître nulle part.
3. Provoquer trois erreurs : une URL inexistante, l'accès à `/admin` avec un compte parent, et une suppression envoyée sans jeton CSRF.
4. Ouvrir le tableau de bord parent sur 30 jours, puis la liste des enfants, et relever le temps d'affichage ressenti.
5. **Résultat attendu** : aucune trace technique visible (404 et 403 sobres), aucune barre de debug, les pages s'affichent rapidement, et les détails des erreurs ne se trouvent que dans `var/log/`.

## Critères de validation

- [ ] Tu m'as montré le nombre de requêtes SQL **avant / après** pour les pages
      travaillées.
- [ ] Aucun paquet de développement n'est nécessaire au fonctionnement en prod.
- [ ] `make lint` et `make tests` restent verts.
- [ ] Le README documente : déploiement, sauvegarde, restauration, logs,
      conservation des données.
- [ ] phpMyAdmin n'est pas exposé dans la configuration de production.
- [ ] Aucun secret réel n'est versionné.

## Enfin

- N'écris **aucun nouveau test automatisé** : la suite de la phase 12 suffit ;
  contente-toi de la garder verte.
- Termine par une **checklist de mise en production** en 10 points maximum, que
  je pourrai suivre à chaque déploiement.
