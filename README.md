# SymSvel Backend

> [!IMPORTANT]
> **Vous souhaitez utiliser SymSvel ? Rendez-vous sur [SymSvel CLI](https://github.com/Symsvel-Framework/symsvel-cli).** Ce dépôt est un **composant du framework**, pas un point d'installation autonome. Création de projets, installation des modules et mises à jour passent par la CLI. Les éventuelles commandes Composer, npm ou Symfony décrites ci-dessous concernent le travail des mainteneurs, sauf indication explicite contraire.

> [!WARNING]
> **SymSvel est en cours de développement.** Les fonctionnalités et procédures peuvent évoluer ou présenter des anomalies. **Utilisation à vos risques et périls.**

Squelette applicatif Symfony 8.1 / PHP 8.4 de SymSvel Framework (`symsvel-framework/back`, package Composer de type `project`). Le Back contient la configuration et le runtime de l'application ; les fonctionnalités métier appartiennent aux modules. Les décisions sont conservées dans [les ADR](docs/adr/README.md).

## Distribution stable et documentation développeur

Le Back conserve **Core v1.2.0** et verrouille **Tooling v1.3.0**
dans `composer.lock`. Cette version Tooling fournit l'assemblage des
véritables guides développeur au bootstrap et leur reconstruction locale
après ajout, mise à jour et suppression des modules. Ne pas publier une
release Back tant que le tag et la GitHub Release Tooling correspondants
ne sont pas disponibles et que la CI du nouveau lock n'est pas verte.

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

Pour un environnement de développement ou de crash-test qui consomme les commits `dev-main` :

```bash
symsvel create-project --components=all --dev
```

Le BO est obligatoire. `--components=bo|app|front|all` permet de sélectionner les interfaces. La CLI installe Symfony dans `backend/` et les composants SvelteKit dans les dossiers frères, puis démarre le Setup Wizard local. Le canal `stable` ou `dev` est enregistré dans `backend/.symsvel/project.json`, indépendamment de `APP_ENV`. Le parcours historique `composer create-project` suivi manuellement de `symsvel:install` n'est **plus la procédure utilisateur de référence**.

Prérequis : PHP 8.4+, Composer 2, Git et accès aux sources privées selon le canal ; Node.js et npm pour les interfaces. La CLI Symfony facultative n'est pas nécessaire à la CLI SymSvel. Voir [l'installation](docs/demarrage/installation.md) et [le contrat de provenance](https://github.com/Symsvel-Framework/symsvel-cli/blob/feat/persist-project-channel/docs/reference/provenance.md).

## Setup Wizard et modules natifs

```bash
symsvel setup
```

Le Wizard local collecte les informations du projet, les paramètres PostgreSQL et les identifiants du premier administrateur. La finalisation applique les migrations et prépare l'administrateur. La configuration de connexion est écrite dans `backend/.env.local`. La création initiale ne doit pas exiger une base déjà configurée.

Le manifeste `config/symsvel-native-modules.json` exige Admins, Roles, ACLs et Settings ; Users est requis lorsque App est installée. **Le Setup actuel vérifie une connexion PostgreSQL** : la prise en charge MySQL/MariaDB/SQLite dans le Wizard relève de l'objectif architectural, pas d'une fonctionnalité déjà démontrée. L'accès BO/App avec 2FA est un contrat cible à valider séparément ; ne pas le déduire de la seule réussite du formulaire Setup.

Voir [le guide Setup](docs/projets/setup.md), [le cycle de vie](docs/architecture/cycle-de-vie.md) et [les ADR](docs/adr/README.md).

## Développement, mises à jour et qualité

```bash
composer install
composer qa
```

Les modules sont installés et maintenus via la CLI `symsvel require/update/remove`. Les mises à jour des interfaces utilisent un mécanisme natif distinct de la mise à jour des packages Composer ; les fichiers locaux conflictuels ne doivent pas être écrasés. Voir [la mise à jour d'un projet](docs/projets/mise-a-jour.md).

La distribution stable dépend de releases cohérentes du Back, du Core, du Tooling et des interfaces. **Une CI verte sur une branche de développement ne publie pas automatiquement une nouvelle release ni ne met à jour le `composer.lock` d'un projet existant.** Les parcours complets GitHub/Satis, Setup, modules et rollback doivent être validés par les crash-tests XT avant merge des PR CLI #10 et Tooling #62.

Les mises à jour de maintenance sont locales et ne doivent pas être exécutées directement sur un serveur de production.

### Diagnostic temporaire npm du Setup (09/11/2026)

Pendant le crash-test local, le parcours Setup ajoute des traces dans `.symsvel/npm-trace.log` à la racine du workspace. Les étapes CLI, contrôleur et Tooling journalisent l'horodatage, le PID, le chemin npm et le PATH utiles au diagnostic ; Tooling journalise également la commande de build choisie et son code de sortie. Aucun secret ni environnement complet n'est journalisé. Ces traces sont temporaires, réservées au diagnostic local et doivent être retirées après identification de la cause. Ne pas publier ce journal.

### Transmission de npm au Setup (09/11/2026)

Le contrôleur local du Wizard transmet explicitement `SYMSVEL_SETUP_NPM` et `PATH` à son processus enfant `symsvel:setup:complete` via l'argument d'environnement de `Symfony\\Component\\Process\\Process`. Le Tooling utilise alors le chemin absolu de npm déjà détecté par la CLI. Cette transmission corrige le cas observé où `getenv()` renvoyait `false` dans le processus console alors que les valeurs étaient présentes dans le contrôleur. Aucun chemin npm propre à macOS/Homebrew n'est codé en dur. Le tracking temporaire `.symsvel/npm-trace.log` est conservé pour vérifier le prochain crash-test. L'état `database_ready` permet de relancer la finalisation sans recréer l'administrateur ni la base.

## Permissions du Setup local

Le Setup Wizard local écrit `backend/.env.local` de manière atomique avec le mode `0644`, afin que PHP-FPM puisse le lire lorsque le serveur HTTP utilise un autre compte que la CLI (ex. `_www` sur macOS). Ce fichier peut contenir des identifiants de base de données : réserver ce comportement à une machine de développement maîtrisée et éviter d'y placer des secrets de production. En production, le vrai `.env` est déposé dans `secrets/` puis copié vers `backend/` par la CD ; ses permissions relèvent du déploiement et non du Setup local.

Régression CT : après la phase 1 du Setup, vérifier le mode de `.env.local` et un accès HTTP via PHP-FPM sous un compte distinct. Une CI verte ne valide pas à elle seule cette configuration locale.

## Setup multi-SGBD (chantier en cours)

La phase 1 du Wizard propose PostgreSQL, MySQL, MariaDB et SQLite. PostgreSQL détecte la version via `SHOW server_version_num`. MySQL/MariaDB détectent `SELECT VERSION()`, vérifient que le moteur correspond au choix et produisent un paramètre `serverVersion` Doctrine adapté (préfixe `mariadb-` pour MariaDB). SQLite utilise un fichier relatif au backend (`var/app.sqlite` par défaut), sans version serveur. Le fichier `backend/.env.local` est écrit atomiquement en mode `0644` dans l'environnement local.

**Limite actuelle :** le Setup déclenche encore `symsvel:setup:database` après la configuration. Le compilateur et les migrations multi-SGBD ne sont pas validés et font l'objet d'un chantier séparé. Le parcours complet n'est donc pas encore certifié sur MySQL, MariaDB ou SQLite. Les pilotes PDO nécessaires doivent être installés.

### Diagnostics d'échec du Setup

Le Wizard conserve le parcours continu : écriture atomique de `backend/.env.local`, puis commande `symsvel:setup:database` (connexion, création de base et migrations). Si cette commande échoue, le formulaire présente son code de sortie et sa sortie d'erreur réelle, limitée à 4 000 caractères, au lieu du message générique « Vérifiez la connexion et les migrations ». Les URL de connexion et le mot de passe configuré sont masqués avant affichage. La commande Tooling doit elle-même identifier l'étape fautive (connexion, création ou migration) pour que le diagnostic soit parfaitement catégorisé. Le Setup reste relançable après correction ; aucune modification du mécanisme de migrations n'est effectuée par ce patch.

## CI Docker locale (10/11/2026)

La CI active `.github/workflows/ci.yml` exécute les tests du Back sur le runner Docker ARM64 d'organisation (`self-hosted`, `linux`, `ARM64`, `symsvel`). PHP 8.4, Composer et les extensions nécessaires sont préinstallés dans l'image ; leur présence est vérifiée avant les étapes de test. Le workflow GitHub-hosted original est conservé, désactivé, dans `.github/workflows/ci.yml.stub`. Pour revenir à GitHub-hosted, restaurer ce fichier sous `ci.yml`. Le smoke-test d'installation reste désactivé comme auparavant. La CI locale dépend de la disponibilité du Mac et de Docker ; n'exécuter que des contributions de confiance sur le runner persistant. La configuration d'accès aux dépôts privés doit éviter toute persistance de jetons entre jobs. Validation finale : CI verte et crash-tests XT requis.

### Visibilité des tests ignorés

La CI affiche les raisons des tests PHPUnit ignorés avec `--display-skipped` (directement ou via le script Composer QA). Un test ignoré ne devient pas bloquant pour autant : les motifs sont examinés pour distinguer prérequis absents et couverture manquante.

---

## Participer et soutenir le projet

SymSvel est un projet en cours de développement. Pour contribuer, proposer une amélioration ou échanger avec le mainteneur : **[xavier@xavier-tezza.fr](mailto:xavier@xavier-tezza.fr)**.

SymSvel vous est utile et vous souhaitez me remercier ? **Offrez-moi une bière ! 🍺** (lien PayPal à compléter avant l'ouverture publique).
