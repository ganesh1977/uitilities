<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20171030112744 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $schema->getTable('campaigns')->addColumn('created_at', 'datetime')->setNotnull(false);
        $schema->getTable('campaigns')->addColumn('updated_at', 'datetime')->setNotnull(false);
        $schema->getTable('campaigns')->addColumn('deleted_at', 'datetime')->setNotnull(false);

        $schema->getTable('campaigns')->addIndex(['created_at'], 'created_at_idx');
        $schema->getTable('campaigns')->addIndex(['updated_at'], 'updated_at_idx');
        $schema->getTable('campaigns')->addIndex(['deleted_at'], 'deleted_at_idx');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $schema->getTable('campaigns')->dropIndex('created_at_idx');
        $schema->getTable('campaigns')->dropIndex('updated_at_idx');
        $schema->getTable('campaigns')->dropIndex('deleted_at_idx');

        $schema->getTable('campaigns')->dropColumn('created_at');
        $schema->getTable('campaigns')->dropColumn('updated_at');
        $schema->getTable('campaigns')->dropColumn('deleted_at');
    }
}
