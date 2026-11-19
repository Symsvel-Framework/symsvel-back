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

## Ordre des scripts Composer et inventaire de propriété

Lors d'un `composer require`, `update` ou `remove`, Composer modifie `vendor/` avant d'exécuter les scripts `post-install-cmd` / `post-update-cmd`. Le script autonome `scripts/symsvel-module-precache.php` doit donc précéder `@auto-scripts` : il vérifie les modules installés et republie `.symsvel/bundles.php` puis `.symsvel/modules.json` depuis les métadonnées Composer, sans démarrer le kernel Symfony. `cache:clear` peut ensuite compiler le conteneur Settings avec les chemins des modules réellement présents. Le script historique `symsvel-module-sync.php` reste exécuté après les auto-scripts pour la synchronisation complète et les builds Svelte.

Le contrôle de propriété de Settings reste strict : aucun chemin manquant ou fournisseur sans propriétaire vérifié n'est toléré. Le rollback global de Tooling couvre ces écritures dans la transaction. Le parcours réel de suppression Mails sur XT et les CI doivent encore être validés avant de déclarer le correctif terminé.

### Routes Symfony des modules locaux (19/11/2026)

Pour un module fourni par un repository Composer `path` avec symlink,
Flex ne déclenche pas automatiquement la recette contenue dans ce dépôt.
Le précache appelle donc `ComposerModuleRouteSynchronizer` de Tooling,
**avant** `cache:clear`, lorsque cette classe est disponible. Tooling
consulte la déclaration explicite `recipe/recipe.json` du module,
importe uniquement son dossier de contrôleurs si celui-ci contient
réellement des contrôleurs PHP, puis maintient un fichier
`config/routes/<slug>.yaml` par module. Aucun mécanisme de routage
central ou découverte implicite n'est ajouté à Back ou Core.

Le registre `.symsvel/composer-routes.json` stocke uniquement la
**propriété et l'empreinte** des fichiers générés. Les routes créées par
Symfony Flex ou personnalisées par le projet sont préservées. Les imports
générés sont retirés lors de `remove` seulement si leur empreinte est
restée identique. Cette étape précède `@auto-scripts` pour empêcher le
chargement de routes obsolètes après suppression de leur module.

Le garde `class_exists` maintient la compatibilité avec les versions
de Tooling antérieures actuellement présentes dans les projets en canal
stable, sans modifier les contraintes Composer. Sur le canal `dev`, le
Tooling récent est requis pour bénéficier de cette synchronisation.
La vérification de l'ordre du hook est automatisée ; les CT XT avec
`debug:router`, `require/update/remove` et restauration restent
obligatoires avant clôture du ticket Tooling #64.
