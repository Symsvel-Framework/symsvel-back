# Données et persistance

SymSvel s'appuie sur Doctrine DBAL et Doctrine Migrations pour les bases relationnelles prises en charge par Doctrine. Chaque module possède ses tables et ses migrations.

Le Tooling coordonne la compilation et l'exécution des migrations. La compatibilité Doctrine ne garantit pas que les outils de sauvegarde et de restauration prennent en charge chaque moteur.

Les checkpoints globaux de base sont implémentés pour PostgreSQL et SQLite.

Voir [migrations](migrations.md).
