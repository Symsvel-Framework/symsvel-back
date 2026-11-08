# SymSvel Backend

Squelette applicatif Symfony 8.1 / PHP 8.4 de SymSvel Framework (`symsvel-framework/back`, package Composer de type `project`). Le Back contient la configuration et le runtime de l'application ; les fonctionnalités métier appartiennent aux modules. Les décisions sont conservées dans [les ADR](docs/adr/README.md).

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

Prérequis : PHP 8.4+, Composer 2, Git et accès aux sources privées selon le canal ; Node.js et npm pour les interfaces. La CLI Symfony facultative n'est pas nécessaire à la CLI SymSvel. Voir [l'installation](docs/demarrage/installation.md) et [les fichiers et registres de provenance](https://github.com/Symsvel-Framework/symsvel-cli/blob/feat/persist-project-channel/docs/reference/fichiers.md).

## Setup Wizard et modules natifs

```bash
symsvel setup
```

Le Wizard local collecte les informations du projet, les paramètres PostgreSQL et les identifiants du premier administrateur. La finalisation applique les migrations et prépare l'administrateur. La configuration de connexion est écrite dans `backend/.env.local`. La création initiale ne doit pas exiger une base déjà configurée.

Le manifeste `config/symsvel-native-modules.json` exige Admins, Roles et ACLs ; Users est requis lorsque App est installée. **Le Setup actuel vérifie une connexion PostgreSQL** : la prise en charge MySQL/MariaDB/SQLite dans le Wizard relève de l'objectif architectural, pas d'une fonctionnalité déjà démontrée. L'accès BO/App avec 2FA est un contrat cible à valider séparément ; ne pas le déduire de la seule réussite du formulaire Setup.

Voir [le guide Setup](docs/projets/setup.md), [le cycle de vie](docs/architecture/cycle-de-vie.md) et [les ADR](docs/adr/README.md).

## Développement, mises à jour et qualité

```bash
composer install
composer qa
```

Les modules sont installés et maintenus via la CLI `symsvel require/update/remove`. Les mises à jour des interfaces utilisent un mécanisme natif distinct de la mise à jour des packages Composer ; les fichiers locaux conflictuels ne doivent pas être écrasés. Voir [la mise à jour d'un projet](docs/projets/mise-a-jour.md).

La distribution stable dépend de releases cohérentes du Back, du Core, du Tooling et des interfaces. **Une CI verte sur une branche de développement ne publie pas automatiquement une nouvelle release ni ne met à jour le `composer.lock` d'un projet existant.** Les parcours complets GitHub/Satis, Setup, modules et rollback doivent être validés par les crash-tests XT avant merge des PR CLI #10 et Tooling #62.

Les mises à jour de maintenance sont locales et ne doivent pas être exécutées directement sur un serveur de production.
