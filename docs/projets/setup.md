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

## Chantier en cours : setup en deux phases et compatibilité multibase

Le parcours cible validé remplace le formulaire unique actuel :

1. **Phase 1, configuration** : nom du projet, domaine local, choix PostgreSQL/MySQL/MariaDB/SQLite et paramètres adaptés au moteur. Après validation : écriture sécurisée de `backend/.env.local`, création de la base si nécessaire et exécution des migrations. Aucun identifiant administrateur n'est demandé ni conservé à cette étape.
2. **Phase 2, administrateur** : nouvel écran accessible seulement après réussite de la phase 1 ; saisie et création immédiate du premier administrateur, sans `pending-admin.json` ni autre persistance temporaire de ses identifiants.
3. **Finalisation** : génération des builds BO/App/Front après les migrations et création de l'administrateur, puis passage de l'état du setup à `completed`. La génération des fichiers de traduction est différée à un chantier dédié.

Les reprises après erreur doivent respecter l'étape déjà validée, sans redemander ni stocker les secrets administrateur. Les permissions de `.env.local` doivent permettre la lecture par le processus PHP tout en protégeant les secrets. Les quatre moteurs nécessitent des tests de connexion, création et migrations. **Ce contrat décrit la cible du chantier, pas une fonctionnalité déjà livrée.**

La sauvegarde des métadonnées du projet conserve désormais le champ `channel` (`dev` ou `stable`) initialisé par la CLI, au lieu de le supprimer lors de l'enregistrement du nom et du domaine. Un test de non-régression couvre ce cas.

## Implémentation intermédiaire de la séparation des écrans

La branche de chantier expose maintenant deux routes locales : `/setup` (configuration PostgreSQL et appel de `symsvel:setup:database`) puis `/setup/admin` (état `database_ready` requis, CSRF indépendant et transmission JSON des identifiants par entrée standard à `symsvel:admins:bootstrap:stdin`). La première phase ne demande plus les identifiants administrateur et ne crée plus `pending-admin.json`. La reprise depuis `database_ready` redirige vers le second écran.

**Ce parcours n'est pas encore terminé** : après création de l'administrateur, le contrôleur affiche un accusé de réception temporaire ; la finalisation des builds, la transition `completed`, la prise en charge multibase et les tests fonctionnels restent à implémenter et valider. Le mécanisme historique de finalisation existe encore pour compatibilité. Ne pas déployer cette branche comme une installation finalisée.

Correctif CI : les classes d'exception pleinement qualifiées du nouveau contrôleur utilisent un seul antislash PHP (`\\JsonException`, `\\InvalidArgumentException`) ; un échappement doublé empêchait le chargement de Symfony pendant `cache:clear`.

## Raccordement de la finalisation à la phase administrateur

Après réussite de `symsvel:admins:bootstrap:stdin`, le contrôleur appelle désormais `symsvel:setup:complete` (Tooling), qui vérifie le compte initial, exécute les builds statiques des interfaces présentes et passe à `completed` seulement si tous réussissent. Un échec conserve `database_ready` ; le second écran propose une action distincte `setup_action=complete` pour relancer uniquement la finalisation, sans mot de passe ni recréation de compte. Les deux actions exigent le CSRF et l'accès local. L'ancien accusé de réception provisoire est supprimé.

**Statut : code poussé, CI Back et tests fonctionnels de reprise encore à valider.** La configuration PostgreSQL reste la seule implémentée ; le support multibase, le parcours d'authentification/2FA et les crash-tests XT restent hors du périmètre validé. Les paragraphes précédents décrivant l'accusé provisoire retracent l'état antérieur du chantier.
