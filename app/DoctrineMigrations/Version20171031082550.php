<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20171031082550 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // Doctrine doesn't have a method of adding column X after y so we have do it with raw SQL.
        $this->addSql('ALTER TABLE `campaign_offers` ADD `end_dt` DATE NULL AFTER `st_dt`');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $schema->getTable('campaign_offers')->dropColumn('end_dt');
    }
}
