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
