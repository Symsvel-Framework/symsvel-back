# Dépôts et responsabilités

Organisation GitHub : `Symsvel-Framework`.

| Dépôt | Responsabilité |
| --- | --- |
| `symsvel-back` | Squelette Symfony, configuration, Setup, ADR |
| `symsvel-core` | Bundle runtime, services techniques, réponses API |
| `symsvel-tooling` | Modules, migrations, mises à jour, transactions |
| `symsvel-cli` | Commandes de création et maintenance |
| `symsvel-bo` | Interface SvelteKit BO |
| `symsvel-app` | Interface SvelteKit App |
| `symsvel-front` | Interface SvelteKit Front |
| `symsvel-recipes` | Recettes Symfony Flex |
| `symsvel-packages` | Catalogue Composer Satis |

La CLI et le Tooling sont des outils de développement ; ils ne font pas partie du runtime déployé. Le Core et le Back restent légers. Les fonctionnalités appartiennent aux modules.
