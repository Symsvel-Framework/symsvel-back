# Créer un projet

```bash
symsvel doctor
symsvel create-project --components=all
```

La CLI installe les composants demandés puis lance le Setup Wizard Symfony dans le navigateur local.

| Option | Composants |
| --- | --- |
| `--components=bo` | Back et BO |
| `--components=app` | Back, BO et App |
| `--components=front` | Back, BO et Front |
| `--components=all` | Back, BO, App et Front |

```bash
symsvel create-project --components=app --directory=backend --dev
```

`--directory` sélectionne le dossier backend ; `--dev` utilise les sources de développement selon la politique de versions.

Pour reprendre une initialisation interrompue : `symsvel setup`. Le Setup Wizard est local et ne demande pas de jeton d'installation.

Les modules natifs Admins, Roles et ACLs sont requis. Users est également requis lorsque App est installée.

Voir [le Setup](../projets/setup.md) et la [référence CLI](https://github.com/Symsvel-Framework/symsvel-cli/blob/main/docs/reference/commandes.md).
