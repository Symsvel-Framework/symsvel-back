# Composants et responsabilités

| Composant | Responsabilité | N'assume pas |
| --- | --- | --- |
| **CLI** | Point d'entrée des opérations de développement | Fonctionnalités métier |
| **Tooling** | Installation, maintenance, synchronisation, migrations et récupération | Traitement des requêtes de production |
| **Back** | Projet Symfony et configuration de l'application | Logique métier générique centralisée |
| **Core** | Services techniques nécessaires au runtime | Gestion métier des modules |
| **Modules** | Données, règles, routes, pages et processus fonctionnels | Orchestration générale du framework |
| **BO / App / Front** | Interfaces SvelteKit compilées en fichiers statiques ; seuls les builds sont déployés | Exécution de Node.js en production |

**Frontière fondamentale :** CLI et Tooling servent au développement et à la maintenance. Back, Core et les modules participent au code exécuté en production. Le BO, l'App et le Front sont des interfaces distinctes.

Le BO est présent dans toute installation. App et Front sont optionnels. Un module apporte ses éléments selon ses scopes, sans transformer Core en application métier.

**Déploiement des interfaces :** Node.js est utilisé pour le développement et la compilation. En production, les fichiers statiques générés sont servis par le serveur web, sans processus Node.js.
