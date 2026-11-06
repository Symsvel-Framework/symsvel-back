# Migrations des modules

Chaque module possède ses migrations. Le Tooling maintient leur registre et compile les migrations des modules installés dans le backend pour leur exécution par Doctrine Migrations.

Compilation, exécution, retrait et récupération sont des étapes distinctes.

```bash
cd backend
php bin/console list symsvel
php bin/console symsvel:module:migrations:compile --help
```

La désinstallation vérifie les dépendances, prépare les sauvegardes applicables et exige confirmation pour les opérations destructives. Un journal de récupération peut être conservé après échec.

Voir [transactions](https://github.com/Symsvel-Framework/symsvel-tooling/blob/main/docs/qualite/transactions.md).
