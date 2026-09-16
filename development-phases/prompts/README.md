# Prompts Claude Code

Un fichier par phase. Chaque prompt est **autonome** : il rappelle le contexte
du projet, décrit ce qu'il faut implémenter, les contraintes à respecter, le
scénario de test manuel et les critères de validation.

## Comment s'en servir

1. Terminez la phase précédente et vérifiez son scénario de test.
2. Ouvrez le fichier de la phase, copiez **tout son contenu**.
3. Collez-le dans Claude Code, à la racine de votre projet.
4. Relisez le code produit avant de valider : c'est vous le développeur.
5. Jouez le scénario de test manuel dans le navigateur.

> Les prompts demandent explicitement à Claude Code d'**analyser l'existant
> avant de modifier quoi que ce soit**, de respecter l'architecture en place, et
> de **ne pas anticiper** les phases suivantes.

> Aucun test automatisé n'est demandé, **sauf à la phase 12** qui leur est
> consacrée. Partout ailleurs, la validation se fait au navigateur.

## Liste des prompts

| Phase | Description | Prompt |
|---|---|---|
| Phase 01 | Environnement Docker et squelette Symfony | [Prompt](./phase-01.md) |
| Phase 02 | Gabarit de base, charte graphique et page d'accueil | [Prompt](./phase-02.md) |
| Phase 03 | Base de données, Doctrine et entité `User` | [Prompt](./phase-03.md) |
| Phase 04 | Inscription, connexion et rôles | [Prompt](./phase-04.md) |
| Phase 05 | Espace parent : profils enfants et comptes de connexion | [Prompt](./phase-05.md) |
| Phase 06 | Espace enfant : connexion par identifiant, accueil et profil | [Prompt](./phase-06.md) |
| Phase 07 | Journal quotidien en 2 étapes | [Prompt](./phase-07.md) |
| Phase 08 | Bibliothèque de contenus (administration et page enfant) | [Prompt](./phase-08.md) |
| Phase 09 | Moteur de conseils | [Prompt](./phase-09.md) |
| Phase 10 | Tableau de bord parent et graphiques | [Prompt](./phase-10.md) |
| Phase 11 | Administration des comptes parents | [Prompt](./phase-11.md) |
| Phase 12 | Données de démonstration, qualité et tests | [Prompt](./phase-12.md) |
| Phase 13 | Performance, robustesse et mise en production | [Prompt](./phase-13.md) |

⬅️ Retour au [parcours complet](../README.md)
