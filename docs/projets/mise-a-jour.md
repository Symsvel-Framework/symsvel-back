# Mise à jour d'un projet

La CLI privilégie les opérations sélectives. Le canal `stable` ou `dev` est fixé lors de la création et enregistré dans `backend/.symsvel/project.json`.

```bash
# Package Composer (lanceur transactionnel Tooling)
symsvel update symsvel-framework/module-test-back
# Interface SvelteKit (mise à jour native)
symsvel update bo
```

`symsvel update bo|app|front` délègue à `symsvel:component:update` et utilise un manifeste de provenance pour comparer référence native, source amont et fichiers locaux. Les collisions sont refusées ; les modifications locales non conflictuelles sont préservées. Les dépôts Git applicatifs ne sont pas fusionnés ou réinitialisés par ce mécanisme.

`symsvel update <package>` délègue à Composer dans une transaction Tooling. Le parcours Symfony `php bin/console symsvel:update` utilise `WorkspaceUpdater` et **ne doit pas être confondu** avec les deux opérations précédentes.

Les checkpoints et rollbacks ont des limites : sauvegarde BDD globale seulement lorsqu'elle est effectivement disponible, PostgreSQL/SQLite couverts par le mécanisme de backup, effets externes non restaurés. Une récupération incomplète conserve ses checkpoints.

Voir [le contrat natif](https://github.com/Symsvel-Framework/symsvel-tooling/blob/feat/60-update-init-simplification/docs/component-updates-v1.md) et [les transactions](https://github.com/Symsvel-Framework/symsvel-tooling/blob/feat/60-update-init-simplification/docs/qualite/transactions.md). Les crash-tests XT restent requis.
