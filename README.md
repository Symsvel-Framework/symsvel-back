# SymSvel Backend

> [!IMPORTANT]
> **Vous souhaitez utiliser SymSvel ? Rendez-vous sur [SymSvel CLI](https://github.com/Symsvel-Framework/symsvel-cli).** Ce dépôt est un **composant du framework**, pas un point d'installation autonome. Création de projets, installation des modules et mises à jour passent par la CLI. Les éventuelles commandes Composer, npm ou Symfony décrites ci-dessous concernent le travail des mainteneurs, sauf indication explicite contraire.

> [!WARNING]
> **SymSvel est en cours de développement.** Les fonctionnalités et procédures peuvent évoluer ou présenter des anomalies. **Utilisation à vos risques et périls.**

Squelette applicatif Symfony 8.1 / PHP 8.4 de SymSvel Framework (`symsvel-framework/back`, package Composer de type `project`). Le Back contient la configuration et le runtime de l'application ; les fonctionnalités métier appartiennent aux modules. Les décisions sont conservées dans [les ADR](docs/adr/README.md).

## Distribution stable et documentation développeur

Cette promotion stable requiert **Core v1.2.1 ou compatible** et **Tooling v1.4.0 ou compatible**. Tooling fournit notamment le résolveur PHP CLI du bootstrap natif, la mise à jour du squelette Back et l'assemblage des guides développeur. Le `composer.lock` doit être régénéré par Composer avec ces versions stables, audité et validé par la CI avant publication.

## Configuration des environnements du projet

Le squelette inclut `.env.example` comme **référence non sensible** des variables usuelles. Chaque projet configure ses valeurs réelles dans `.env.local` ou l'environnement de déploiement ; ces fichiers et les secrets de production ne doivent pas être versionnés. Le Setup Wizard écrit la connexion à la base choisie et son contexte local. L'exemple n'est pas une configuration de production prête à l'emploi.

## Bilan CT XT multi-SGBD du 11 novembre 2026

Les crash-tests d'installation sur le workspace XT vierge ont abouti avec **PostgreSQL, MySQL, SQLite et MariaDB**. Le parcours validé comprend la création/configuration de la base, l'exécution des migrations natives Doctrine Schema (Admins, Roles, ACLs, Users), la création du premier administrateur, les builds Svelte et le routing. Pour MariaDB, le contrôle SQL a confirmé les tables `doctrine_migration_versions`, `symsvel_acl_rules`, `symsvel_admins`, `symsvel_roles` et `symsvel_users`, ainsi qu'un administrateur activé avec le rôle `ROLE_DEV`. Ne pas publier d'identifiants ni de hashes issus des CT.

**Incident de CT distinct du fonctionnement multi-SGBD :** une tentative a été perturbée par un terminal de Setup précédent resté ouvert pendant la recréation du workspace. Les états `running` et `recovering` observés dans le nouveau workspace et le refus du bootstrap ne constituent pas une preuve de défaut Doctrine ou MariaDB. L'interaction exacte entre processus n'a pas été tracée ; la protection contre deux installations concurrentes reste à auditer, sans modification architecturale décidée à ce stade. Pour les prochains CT, arrêter tous les processus de Setup avant de supprimer/recréer XT.

**Périmètre de validation :** ces résultats valident l'installation initiale et les contrôles fonctionnels mentionnés, pas les parcours `require/update/remove`, les rollbacks, le canal stable via Satis, la 2FA ou l'i18n. Avant publication : vérifier les CI sur les commits de release, les versions et dépendances inter-dépôts, les documents et les parcours de maintenance encore requis. Le suivi npm temporaire doit être traité explicitement avant release.

## Créer un projet

**Parcours utilisateur : la CLI SymSvel.**

```bash
symsvel doctor
symsvel create-project --components=all
```

Pour un environnement de développement ou de crash-test qui consomme les commits `dev-dev` :

```bash
symsvel create-project --components=all --dev
```

Le BO est obligatoire. `--components=bo|app|front|all` permet de sélectionner les interfaces. La CLI installe Symfony dans `backend/` et les composants SvelteKit dans les dossiers frères, puis démarre le Setup Wizard local. Le canal `stable` ou `dev` est enregistré dans `backend/.symsvel/project.json`, indépendamment de `APP_ENV`. Le parcours historique `composer create-project` suivi manuellement de `symsvel:install` n'est **plus la procédure utilisateur de référence**.

Prérequis : PHP 8.4+, Composer 2, Git et accès aux dépôts publics SymSvel et au catalogue Composer ; Node.js et npm pour les interfaces. La CLI Symfony facultative n'est pas nécessaire à la CLI SymSvel. Voir [l'installation](docs/demarrage/installation.md) et [le contrat de provenance](https://github.com/Symsvel-Framework/symsvel-cli/blob/main/docs/reference/provenance.md).

## Setup Wizard PHP natif (refonte 22/11/2026)

Le Setup **ne boote plus Symfony pour afficher les formulaires**.
Après `symsvel create-project` (ou `symsvel setup`), le serveur PHP local
sur `127.0.0.1` affiche `/setup`. Pour un premier déploiement, le développeur
clone le dépôt du backend dans le VPS, exécute `composer install` et ouvre
`/prod_settings` sur son domaine API. Les deux parcours utilisent le même
bootstrap PHP autonome, accessible avant toute configuration Symfony.

Le fichier `.env` fourni est minimal et sans secret. Le wizard génère
`backend/.env.local` avec un `APP_SECRET` unique, `APP_ENV`, la base,
les URL réelles Back/BO/App/Front et les CORS exacts. Les opérations techniques
continuent d'utiliser les commandes CLI/Tooling et modules :
migrations, Languages, premier Admin et builds statiques locaux.

En production, le second écran choisit migrations et admin ou import de dump SQL.
Il déclenche `deploy.yml` des interfaces du projet via un PAT GitHub temporaire.
Le Setup reste ouvert si GitHub refuse une demande de CD. Une fois les
déclenchements acceptés et l'installation terminée, le bootstrap est supprimé
**sur le VPS uniquement** et le marqueur d'état interdit toute réouverture.

Aucune autre route HTTP n'est accessible tant que l'état n'est pas `completed`.
Les tests CI du bootstrap n'équivalent pas au CT local et VPS, encore requis.

Voir [le guide Setup](docs/projets/setup.md),
[le premier déploiement](docs/projets/premier-deploiement.md) et
[l'ADR 0011](docs/adr/0011-bootstrap-php-natif.md).

## Développement, mises à jour et qualité

```bash
composer install
composer qa
```

Les modules sont installés et maintenus via la CLI `symsvel require/update/remove`. Les mises à jour des interfaces utilisent un mécanisme natif distinct de la mise à jour des packages Composer ; les fichiers locaux conflictuels ne doivent pas être écrasés. Voir [la mise à jour d'un projet](docs/projets/mise-a-jour.md).

La distribution stable dépend de releases cohérentes du Back, du Core, du Tooling et des interfaces. **Une CI verte sur une branche de développement ne publie pas automatiquement une nouvelle release ni ne met à jour le `composer.lock` d'un projet existant.** Les parcours complets GitHub/Satis, Setup, modules et rollback doivent être validés par les crash-tests XT avant de considérer la publication complètement validée.

Les mises à jour de maintenance sont locales et ne doivent pas être exécutées directement sur un serveur de production.

### Historique des corrections Setup antérieures à la refonte

Les anciens diagnostics du contrôleur Symfony (`SYMSVEL_SETUP_NPM`,
`.symsvel/npm-trace.log` et permissions `0644` de `.env.local`)
ne constituent plus le contrat du wizard : le bootstrap PHP natif utilise
le processus CLI sous le même utilisateur pour le Setup local et publie
`.env.local` en mode `0600`. Les décisions et correctifs historiques
restent consultables dans l'historique Git et les ADR.

La phase 1 propose PostgreSQL, MySQL, MariaDB et SQLite, avec détection
de versions côté serveurs. Les CT de chacun des quatre moteurs
restent nécessaires avant validation finale. En cas d'échec de migrations
après configuration, Tooling peut reprendre depuis l'état `running`.

## Intégration continue GitHub-hosted (22/11/2026)

La CI de ce dépôt public utilise désormais **Ubuntu 24.04 sur les runners GitHub**, sans dépendance au Docker du mainteneur. Le workflow actif est `.github/workflows/ci.yml` ; il installe PHP 8.4 via `shivammathur/setup-php@v2` et exécute Composer, les contrôles Symfony et PHPUnit. Le contrôle de santé Core reste testé sur `dev`.  L'ancienne version `ci.yml.stub` est inactive.

La publication par tag (`.github/workflows/release.yml`) utilise elle aussi GitHub-hosted. Les runners auto-hébergés sont réservés à `symsvel-packages` et `symsvel-recipes`, qui restent privés. Une CI verte ne remplace pas les crash-tests locaux.


### Visibilité des tests ignorés

La CI affiche les raisons des tests PHPUnit ignorés avec `--display-skipped` (directement ou via le script Composer QA). Un test ignoré ne devient pas bloquant pour autant : les motifs sont examinés pour distinguer prérequis absents et couverture manquante.

## Licence MIT et contributions

Ce dépôt est distribué sous [licence MIT](LICENSE). Le code peut être utilisé, modifié et redistribué, y compris dans des projets commerciaux ou propriétaires, dans les conditions de cette licence (notamment la conservation de la notice de copyright et de licence).

**Contribuer :** créer un fork, puis proposer une pull request vers `dev` lorsque cette branche existe, sinon vers la branche par défaut. Expliquer la modification et fournir les tests et la documentation concernés. Les mainteneurs conservent la maîtrise des revues, des validations (CI et crash-tests), de l'intégration et des releases officielles. Une PR ne garantit pas son acceptation.

**Versions officielles :** seules les versions diffusées par l'organisation [Symsvel-Framework](https://github.com/Symsvel-Framework) sont officielles. Les forks sont libres sous MIT, mais ne doivent pas être présentés comme officiellement approuvés par les mainteneurs. Cette distinction n'ajoute aucune restriction aux droits accordés par la licence MIT sur le code.

## Participer et soutenir le projet

SymSvel est un projet en cours de développement. Pour contribuer, proposer une amélioration ou échanger avec le mainteneur : **[xavier@xavier-tezza.fr](mailto:xavier@xavier-tezza.fr)**.

SymSvel vous est utile et vous souhaitez me remercier ? **Offrez-moi une bière ! 🍺** (lien PayPal à compléter avant l'ouverture publique).
