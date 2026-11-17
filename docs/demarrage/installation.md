# Créer un projet SymSvel

```bash
symsvel doctor
symsvel create-project --components=all
```

Le BO est obligatoire. `--components=bo` installe Back et BO, `app` ajoute App, `front` ajoute Front et `all` installe les trois interfaces. Le Setup Wizard est démarré localement après la préparation du workspace.

```bash
# Crash-test et développement : derniers commits de la branche de distribution
symsvel create-project --components=all --dev
```

Le canal `stable` (par défaut) ou `dev` est conservé dans `backend/.symsvel/project.json`, sans dépendance à `APP_ENV`. Un ancien projet sans provenance valide n'est pas automatiquement migré. `--directory` permet de définir le nom du dossier backend. La CLI orchestre Composer et le Tooling, qui gère les transactions et l'installation.

## Setup

```bash
symsvel setup
```

Le Wizard actuel configure PostgreSQL, le projet et le premier administrateur. Les modules natifs requis par le manifeste du Back sont Admins, Roles, ACLs, Settings, Mails, Auth, Languages et **TwoFactor**, ainsi que Users lorsque App est présente. Auth et TwoFactor sont installés automatiquement par la CLI pendant `create-project` pour toutes les variantes, y compris `--components=bo`. Le premier administrateur configure et valide son TOTP à sa première connexion BO, avant l'émission de la session. Côté App, la 2FA reste configurable via `two-factor.app_required`. Aucun réglage BO ne permet de désactiver cette protection. Les moteurs MySQL, MariaDB et SQLite sont prévus par l'architecture mais **ne sont pas proposés par l'implémentation actuelle du formulaire**.

Le parcours utilisateur de référence est la CLI : ne pas utiliser l'ancien `APP_ENV=core` ni considérer `php bin/console symsvel:install` comme le remplacement de `create-project`. Le code du Tooling conserve cette commande Symfony pour des usages internes.

Voir [le Setup](../projets/setup.md) et [la référence CLI](https://github.com/Symsvel-Framework/symsvel-cli/blob/feat/persist-project-channel/docs/reference/commandes.md).

**Validation en attente :** GitHub/Satis, modules (y compris résolution du package TwoFactor dans les canaux `dev` et `stable`), migrations, builds, authentification obligatoire BO/TOTP et rollbacks sur XT. Un package présent sur `dev` ne prouve pas qu'une release stable soit disponible.

**Distribution stable et verrouillage Composer**

Le squelette Back est distribué avec un composer.lock qui fixe les versions de Core et Tooling utilisées lors de la création d'un projet. Avant chaque release de Back, vérifier que ce verrouillage référence les releases stables validées. Une nouvelle release de Core ou Tooling ne met pas automatiquement à jour le lock de Back.

La validation du canal stable nécessite une installation vierge via symsvel create-project, sans --dev.
