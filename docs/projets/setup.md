# Setup Wizard

Le Setup Wizard est lancé après la création des composants par la CLI. Il est accessible **uniquement en local** pendant la phase d'installation ; son contrôleur vérifie l'adresse du client, l'état `.symsvel/setup.json`, les permissions d'écriture et le jeton CSRF des formulaires.

```bash
symsvel setup
```

Cette commande permet de reprendre le Wizard après une interruption. La finalisation technique est déléguée à `symsvel setup:finalize` (commande Symfony `symsvel:setup:finalize`). Une interruption du formulaire n'implique pas que les changements déjà effectués dans la base aient été annulés.

## Implémentation actuellement vérifiable

Le formulaire recueille le nom du projet, son domaine local, les paramètres de connexion PostgreSQL, l'adresse électronique et le mot de passe du premier administrateur. `DatabaseConnectionConfig` vérifie une connexion PostgreSQL et construit `DATABASE_URL` ; `SetupController` écrit la configuration dans `backend/.env.local`, prépare l'administrateur et exécute la finalisation.

Le Tooling initialise un état local et un jeton interne `.symsvel/setup-token` ; **l'utilisateur n'a pas à saisir un jeton de setup dans le formulaire**. Ce fichier technique doit rester privé et ne doit pas être versionné.

## Contrat cible et limites

L'[ADR 0005](../adr/0005-installation-operationnelle.md) exige une distribution utilisable après setup, avec modules natifs et accès BO/App sécurisés. L'[ADR 0008](../adr/0008-donnees-et-migrations.md) prévoit PostgreSQL, MySQL, MariaDB et SQLite. **À ce stade, le formulaire Setup est spécifique à PostgreSQL.** Ne pas présenter les trois autres moteurs ni les parcours 2FA comme validés.

Le parcours complet de création, configuration BDD, migrations, premier administrateur, authentification et reprise après erreur doit être testé sur XT. Ne jamais modifier artificiellement XT pour masquer un défaut du framework.
