# Mise à jour d'un projet

La CLI privilégie les mises à jour sélectives :

```bash
symsvel update symsvel-framework/module-exemple
symsvel update bo
```

Le Tooling contrôle les préconditions, les fichiers gérés et les modifications locales. Selon l'opération, il crée des checkpoints et tente un rollback en cas d'échec.

Les mises à jour de composants et les mises à jour de modules ne reposent pas exactement sur les mêmes mécanismes. Un fichier local modifié ne doit pas être écrasé silencieusement.

Voir [transactions et restauration](https://github.com/Symsvel-Framework/symsvel-tooling/blob/main/docs/qualite/transactions.md).
