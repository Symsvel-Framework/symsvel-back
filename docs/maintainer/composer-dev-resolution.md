# Résolution Composer du canal dev

Les projets `--dev` sélectionnent explicitement `dev-dev` pour Core, Tooling et les modules natifs. Les contraintes transitives des modules restent SemVer (`^1.0`, `^1.1`, etc.). Chaque module publié déclare dans son composer.json un alias de branche `extra.branch-alias.dev-dev` correspondant à sa série de développement compatible : 1.1.x-dev pour Auth, Admins et Users ; 1.0.x-dev pour les autres modules natifs. Préserver ces alias lors des promotions et les réviser si la série compatible change. La stabilité globale et les contraintes de release ne sont pas assouplies.

La CI Back comporte un job native-dev-resolution : depuis les dépendances Back et son manifeste natif, il génère un composer.json temporaire avec les dépôts VCS officiels et les contraintes dev-dev, puis résout le graphe avec Composer sans installation, scripts ni plugins. Il vérifie les versions de Core, Tooling et de tous les modules, y compris ceux de l'App, dans le lock temporaire. Aucun changement du manifeste ou lock versionné. Ce test complète les QA isolées, qui peuvent résoudre des releases stables, mais ne remplace ni le CT XT complet ni la validation stable. Régression : l'absence d'alias dev-dev après promotion des manifests de release empêchait l'installation simultanée des modules.

## Distribution officielle

Catalogue stable : `https://packages.symsvel.com`. Catalogue de développement : `https://packages-dev.symsvel.com`. Recettes Flex : `https://recipes.symsvel.com/index.json`. La séparation des canaux et la résolution VCS de `--dev` restent inchangées. Les URL historiques ne sont plus prises en charge ; les chemins de déploiement restent inchangés. Les index distants et les CT dev/stable doivent être contrôlés avant clôture.
