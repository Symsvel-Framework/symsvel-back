# Cycle de vie d'un projet

1. **Créer** : `symsvel create-project` prépare Back, BO et éventuellement App/Front sans exiger de base préexistante.
2. **Initialiser** : `symsvel setup` configure le projet, la connexion PostgreSQL et le premier administrateur ; la finalisation traite les migrations et l'initialisation applicative.
3. **Utiliser** : le contrat architectural vise un BO administrateur avec 2FA, une App avec inscription et authentification 2FA si installée, et un Front initial accessible.
4. **Étendre** : les modules apportent leurs données, routes, interfaces, tests et documentation selon leurs scopes.
5. **Maintenir** : `symsvel require/update/remove` et les mises à jour natives des interfaces passent par les outils prévus, avec préconditions et tentatives de récupération.
6. **Déployer** : livrer le runtime et les builds statiques nécessaires ; ne pas exposer l'outillage local en production.

## Statut du contrat

**Ce cycle décrit le résultat attendu, pas une certification de bout en bout.** Le contrôleur Setup actuel vérifie PostgreSQL ; l'ADR prévoit également MySQL, MariaDB et SQLite, dont la prise en charge par le Wizard reste à implémenter ou valider. L'authentification et la 2FA exigent des vérifications distinctes du Setup.

La provenance des packages et des composants est indépendante de `APP_ENV` ; elle est enregistrée à la création du projet. Les opérations Composer et les mises à jour natives des composants suivent des mécanismes différents. Les crash-tests XT valideront l'ensemble avant clôture des PR en cours.

Voir [l'ADR d'installation](../adr/0005-installation-operationnelle.md), [l'ADR des données](../adr/0008-donnees-et-migrations.md) et [le Setup](../projets/setup.md).
