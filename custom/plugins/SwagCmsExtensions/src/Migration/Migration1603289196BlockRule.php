<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\CmsExtensions\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Content\Cms\Aggregate\CmsBlock\CmsBlockDefinition;
use Shopware\Core\Framework\Migration\MigrationStep;
use Swag\CmsExtensions\BlockRule\BlockRuleDefinition;

class Migration1603289196BlockRule extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1_603_289_196;
    }

    public function update(Connection $connection): void
    {
        $this->createBlockRuleTable($connection);
        $this->cmsBlockAddBlockRuleColumn($connection);
    }

    public function updateDestructive(Connection $connection): void
    {
    }

    private function createBlockRuleTable(Connection $connection): void
    {
        $sql = <<<'SQL'
            CREATE TABLE IF NOT EXISTS `#table#` (
                `id`                 BINARY(16)  NOT NULL,
                `cms_block_id`       BINARY(16)  NOT NULL,
                `visibility_rule_id` BINARY(16)  NULL,
                `inverted`           TINYINT(1)  NOT NULL DEFAULT 0,
                `created_at`         DATETIME(3) NOT NULL,
                `updated_at`         DATETIME(3) NULL,
                PRIMARY KEY (`id`),
                CONSTRAINT `fk.swag_cms_extensions_block_rule_cms_block`
                    FOREIGN KEY (`cms_block_id`)
                    REFERENCES `cms_block` (`id`)
                    ON DELETE CASCADE
                    ON UPDATE CASCADE,
                CONSTRAINT `fk.swag_cms_extensions_block_rule_rule`
                    FOREIGN KEY (`visibility_rule_id`)
                    REFERENCES `rule` (`id`)
                    ON DELETE SET NULL
                    ON UPDATE CASCADE
            )
            ENGINE=InnoDB
            DEFAULT CHARSET=utf8mb4
            COLLATE=utf8mb4_unicode_ci;
SQL;

        $connection->executeStatement(\str_replace(
            ['#table#'],
            [BlockRuleDefinition::ENTITY_NAME],
            $sql
        ));
    }

    private function cmsBlockAddBlockRuleColumn(Connection $connection): void
    {
        $sql = \str_replace(
            ['#table#', '#reference#'],
            [CmsBlockDefinition::ENTITY_NAME, BlockRuleDefinition::ENTITY_NAME],
            'ALTER TABLE `#table#`
                    ADD COLUMN `#reference#_id` BINARY(16) NULL AFTER `cms_section_id`'
        );
        $connection->executeStatement($sql);
    }
}
