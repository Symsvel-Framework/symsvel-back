# ADR 0003 : Routage des modules via Symfony Flex

- Statut : acceptée
- Date : 2026-11-03

## Contexte

XCore utilisait un fichier central de routes généré. SymSvel utilise des fichiers de routes apportés par les recettes Symfony Flex des modules.

## Décision

Conserver le mécanisme SymSvel : chaque module apporte son propre fichier de routes via sa recette Flex. Ne pas réintroduire de registre central de routes généré.

## Conséquences

- L'installation et la suppression reposent sur les ressources propres au module.
- Le framework évite de réécrire un fichier de routes partagé à chaque opération.
- Le cycle de vie des fichiers issus des recettes, y compris leur suppression, doit être vérifié par des tests.

## Précision : dépôts locaux Composer `path` (19/11/2026)

La décision demeure inchangée. Un package distribué reçoit ses imports
Symfony via sa recette Flex. Le développement dans `modules/<slug>/`
avec un package Composer lié par symlink ne déclenche toutefois pas
l'application automatique de la recette. Tooling synchronise alors un
**fichier de routes distinct par module**, seulement lorsque la recette
locale fournit explicitement cette déclaration et qu'un contrôleur existe.
Ce fichier n'est jamais substitué à un fichier préexistant de Flex ou du
projet. L'inventaire `.symsvel/composer-routes.json` sert exclusivement
à protéger la propriété des fichiers générés, pas à résoudre des routes
au runtime. Les routes restent exécutées par le routeur Symfony natif.
Tests CI et crash-tests d'installation, actualisation, suppression et
récupération exigés pour valider l'implémentation.
