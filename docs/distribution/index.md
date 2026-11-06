# Distribution des packages et recettes

## Composer et Satis

Le dépôt `symsvel-packages` porte la configuration du catalogue Satis publié sur `https://packages.xavier-tezza.fr`. La CLI pilote les installations et mises à jour Composer.

## Symfony Flex

Le dépôt `symsvel-recipes` contient les recettes distribuées via `https://recipes.xavier-tezza.fr`. Le service est opérationnel et alimenté progressivement au fil de la publication des modules.

L'index référence notamment Admins, Roles, ACLs, Users et TestBack. Chaque recette décrit les fichiers d'intégration Symfony nécessaires.

**Satis distribue les packages ; Flex applique les recettes d'intégration.**

## Déploiement

Le déploiement porte sur le backend Symfony et les interfaces SvelteKit compilées statiquement. Aucun serveur Node.js n'est nécessaire pour servir ces interfaces.
