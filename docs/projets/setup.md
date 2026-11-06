# Setup Wizard

Le Setup Wizard configure localement le projet après la création de ses composants par la CLI.

Il prend en charge la configuration initiale du projet, la connexion à la base et la création du premier administrateur selon les mécanismes effectivement disponibles. Les migrations et fixtures interviennent après la configuration de la base.

```bash
symsvel setup
```

Cette commande reprend le Wizard local si l'initialisation a été interrompue. Aucun jeton de setup n'est à fournir.

La finalisation technique relève de `symsvel setup:finalize`. Voir la [référence CLI](https://github.com/Symsvel-Framework/symsvel-cli/blob/main/docs/reference/commandes.md).
