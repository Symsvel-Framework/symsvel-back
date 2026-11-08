# Distribution des packages et recettes

## Composer et Satis

Le dépôt `symsvel-packages` porte la configuration du catalogue Satis à l'adresse `https://packages.xavier-tezza.fr` et son workflow de génération/déploiement. La présence de cette configuration ne constitue pas, à elle seule, une vérification de disponibilité réseau. La CLI pilote les installations et mises à jour Composer.

## Symfony Flex

Le dépôt `symsvel-recipes` contient les recettes distribuées via `https://recipes.xavier-tezza.fr`. Le dépôt contient un index et des manifests de recettes ; son workflow de déploiement est déclenché manuellement. L'accessibilité du service et l'installation réelle par Flex doivent être vérifiées lors des crash-tests XT.

L'index référence notamment Admins, Roles, ACLs, Users et TestBack. Chaque recette décrit les fichiers d'intégration Symfony nécessaires.

**Satis distribue les packages ; Flex applique les recettes d'intégration.**

## Déploiement

Le déploiement porte sur le backend Symfony et les interfaces SvelteKit compilées statiquement. Aucun serveur Node.js n'est nécessaire pour servir ces interfaces.
