<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/** Symfony Messenger Doctrine transport schema, independent of module migrations. */
final class Version20000000000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create the Symfony Messenger Doctrine transport table';
    }

    public function up(Schema $schema): void
    {
        $manager = $this->connection->createSchemaManager();
        $before = $manager->introspectSchema();
        $after = clone $before;
        $table = $after->createTable('messenger_messages');
        $table->addColumn('id', 'bigint', ['autoincrement' => true]);
        $table->addColumn('body', 'text');
        $table->addColumn('headers', 'text');
        $table->addColumn('queue_name', 'string', ['length' => 190]);
        $table->addColumn('created_at', 'datetime');
        $table->addColumn('available_at', 'datetime');
        $table->addColumn('delivered_at', 'datetime', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['queue_name', 'available_at', 'delivered_at', 'id'], 'IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750');

        $diff = $manager->createComparator()->compareSchemas($before, $after);
        foreach ($this->connection->getDatabasePlatform()->getAlterSchemaSQL($diff) as $sql) {
            $this->addSql($sql);
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE messenger_messages');
    }
}
