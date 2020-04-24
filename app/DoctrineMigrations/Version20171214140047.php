<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20171214140047 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSql('ALTER TABLE `campaign_offers` DROP INDEX unique_entry_idx');

    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $schema->getTable('campaign_offers')->addUniqueIndex([
            'campaign', 'st_dt', 'end_dt', 'stay', 'dep_cd', 'arr_cd', 'stc_stk_cd', 'rm_cd', 'bb'
        ], 'unique_entry_idx');

    }
}
