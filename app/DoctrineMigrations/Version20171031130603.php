<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20171031130603 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $schema->getTable('campaign_offers')->addColumn('created_at', 'datetime')->setNotnull(false);
        $schema->getTable('campaign_offers')->addColumn('updated_at', 'datetime')->setNotnull(false);
        $schema->getTable('campaign_offers')->addColumn('deleted_at', 'datetime')->setNotnull(false);

        $schema->getTable('campaign_offers')->addIndex(['created_at'], 'created_at_idx');
        $schema->getTable('campaign_offers')->addIndex(['updated_at'], 'updated_at_idx');
        $schema->getTable('campaign_offers')->addIndex(['deleted_at'], 'deleted_at_idx');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $schema->getTable('campaign_offers')->dropIndex('created_at_idx');
        $schema->getTable('campaign_offers')->dropIndex('updated_at_idx');
        $schema->getTable('campaign_offers')->dropIndex('deleted_at_idx');

        $schema->getTable('campaign_offers')->dropColumn('created_at');
        $schema->getTable('campaign_offers')->dropColumn('updated_at');
        $schema->getTable('campaign_offers')->dropColumn('deleted_at');
    }
}
