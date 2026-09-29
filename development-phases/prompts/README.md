# Prompts Claude Code

Un fichier par phase. Chaque prompt est **autonome** : il rappelle le contexte
du projet, décrit ce qu'il faut implémenter, les contraintes à respecter, le
scénario de test manuel et les critères de validation.

## Comment s'en servir

1. Terminez la phase précédente et vérifiez son scénario de test.
2. Ouvrez le fichier de la phase et repérez sa section « Captures d'écran de
   référence ».
3. Dans Claude Code, joignez ces captures (dossier [`../captures/`](../captures/)) :
   glissez les fichiers dans la fenêtre ou collez les images avec Ctrl+V.
   Joignez **seulement** celles que le prompt liste.
4. Copiez **tout le contenu** du prompt, collez-le à la suite et envoyez, à la
   racine de votre projet.
5. Relisez le code produit avant de valider : c'est vous le développeur.
6. Jouez le scénario de test manuel dans le navigateur et comparez vos pages
   aux captures.

> Les prompts demandent explicitement à Claude Code d'**analyser l'existant
> avant de modifier quoi que ce soit**, de respecter l'architecture en place, et
> de **ne pas anticiper** les phases suivantes.

> Aucun prompt ne demande de tests automatisés : la validation se fait
> toujours au navigateur.

## Liste des prompts

| Phase | Description | Prompt |
|---|---|---|
| Phase 01 | Environnement Docker, squelette Symfony et paquets du projet | [Prompt](./phase-01.md) |
| Phase 02 | Base de données et entités | [Prompt](./phase-02.md) |
| Phase 03 | Gabarit de base, charte graphique et page d'accueil | [Prompt](./phase-03.md) |
| Phase 04 | Authentification, rôles, inscription et connexion parent | [Prompt](./phase-04.md) |
| Phase 05 | Espace parent : profils enfants | [Prompt](./phase-05.md) |
| Phase 06 | Espace enfant : connexion et accueil | [Prompt](./phase-06.md) |
| Phase 07 | Espace enfant : journal quotidien en 2 étapes | [Prompt](./phase-07.md) |
| Phase 08 | Bibliothèque de contenus (admin + enfant) | [Prompt](./phase-08.md) |
| Phase 09 | Moteur de conseils | [Prompt](./phase-09.md) |
| Phase 10 | Tableau de bord parent et enfant | [Prompt](./phase-10.md) |
| Phase 11 | Administration des comptes parents | [Prompt](./phase-11.md) |
| Phase 12 | Données de démonstration et qualité | [Prompt](./phase-12.md) |

⬅️ Retour au [parcours complet](../README.md)
