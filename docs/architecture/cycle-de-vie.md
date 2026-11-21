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

## Vérification HTTP du Healthcheck Core en canal dev (19/11/2026)

La route Core `GET /api/health` est publiée par un contrôleur Symfony
étiqueté `routing.controller`. Le Back utilise déjà le chargement natif
`routing.controllers`, sans import supplémentaire dans `config/routes/`.
Pour vérifier la compatibilité sans modifier les contraintes Composer
actuelles (`symsvel-framework/core: ^1.1`), la CI Back lance d'abord ses
tests verrouillés, puis, sur `dev` seulement, récupère Core `dev` dans
un répertoire temporaire, le résout comme package Composer `path`
à version `dev-dev` **uniquement dans le workspace du runner**, vide
le cache de test, vérifie `debug:router symsvel_core_liveness` et appelle
réellement `GET /api/health` avec le client HTTP Symfony.

Le test `CoreHealthHttpTest` est ignoré dans la suite verrouillée si le
Core stable ne contient pas encore le contrôleur, puis exécuté avec Core
de développement dans l'étape dédiée. La CI n'enregistre aucun changement
de contrainte dans Git et ne publie aucune release. L'endpoint garantit
uniquement la liveness du routage PHP : il ne contrôle ni SGBD ni mail.
Le crash-test XT HTTP reste un critère distinct avant clôture.

## Cohérence des releases stables et du `composer.lock` (20/11/2026)

Le Back est un projet Composer **distribué avec son `composer.lock`**. Lors de
`symsvel create-project` sans `--dev`, Composer installe les versions verrouillées
avant de charger les modules natifs. Le fait de disposer de tags récents dans
Satis ne suffit donc pas à forcer leur sélection : le verrou doit également
référencer des versions compatibles avec le contrat publié.

Le premier CT stable a échoué lors de la synchronisation des dictionnaires
Svelte (collision sur `bo/src/lib/translations/en.ts`) : le Back `v1.2.0`
verrouillait encore Core et Tooling en `v1.1.0`. Or l'isolation des
dictionnaires par module est disponible dans Tooling `v1.2.0`, pas dans
`v1.1.0`. Le squelette stable requiert désormais `^1.2` pour Core et
Tooling et verrouille leurs tags `v1.2.0`. Les autres dépendances ne changent
pas dans ce correctif.

`ComposerStableLockTest` vérifie que le lock correspond au manifeste,
qu'il fixe des versions stables Core/Tooling assez récentes et que les
références Git/dist sont cohérentes. Toute publication future du Back doit
mettre à jour **ensemble** `composer.json`, `composer.lock` et cette
documentation, et vérifier la résolution Composer en installation vierge.

Le warning de suppression `.svelte-kit/generated/dev` pendant le rollback
du CT initial est un incident distinct. Le snapshot transactionnel conservé
ne doit pas être effacé sans diagnostic ; une réussite de l'installation
après correction ne constituerait pas une validation du rollback exhaustif.

Les URL `dist` des deux packages dans le lock doivent cibler les dépôts GitHub
`Symsvel-Framework/symsvel-core` et `Symsvel-Framework/symsvel-tooling`
(et non les noms abrégés des packages Composer). Le test vérifie le chemin
intégral des archives pour détecter cette erreur avant une publication.
