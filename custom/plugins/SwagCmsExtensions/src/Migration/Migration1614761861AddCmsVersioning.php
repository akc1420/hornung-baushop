<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\CmsExtensions\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Content\Cms\Aggregate\CmsBlock\CmsBlockDefinition;
use Shopware\Core\Content\Cms\Aggregate\CmsSection\CmsSectionDefinition;
use Shopware\Core\Content\Cms\Aggregate\CmsSlot\CmsSlotDefinition;
use Shopware\Core\Content\Cms\CmsPageDefinition;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Migration\MigrationStep;
use Swag\CmsExtensions\BlockRule\BlockRuleDefinition;
use Swag\CmsExtensions\Form\FormDefinition;
use Swag\CmsExtensions\Quickview\QuickviewDefinition;
use Swag\CmsExtensions\ScrollNavigation\Aggregate\ScrollNavigationPageSettings\ScrollNavigationPageSettingsDefinition;
use Swag\CmsExtensions\ScrollNavigation\ScrollNavigationDefinition;

class Migration1614761861AddCmsVersioning extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1_614_761_861;
    }

    public function update(Connection $connection): void
    {
        $sql = \str_replace(
            ['#table#', '#reference#'],
            [CmsBlockDefinition::ENTITY_NAME, BlockRuleDefinition::ENTITY_NAME],
            'ALTER TABLE `#table#`
                    DROP COLUMN `#reference#_id`'
        );
        $connection->executeStatement($sql);

        $this->addColumn(
            BlockRuleDefinition::ENTITY_NAME,
            CmsBlockDefinition::ENTITY_NAME,
            'swag_cms_extensions_block_rule_cms_block',
            $connection
        );

        $this->addColumn(
            QuickviewDefinition::ENTITY_NAME,
            CmsBlockDefinition::ENTITY_NAME,
            'swag_cms_extensions_quickview_cms_block',
            $connection
        );

        $this->addColumn(
            ScrollNavigationDefinition::ENTITY_NAME,
            CmsSectionDefinition::ENTITY_NAME,
            'swag_cms_extensions_scroll_navigation_cms_section',
            $connection
        );

        $this->addColumn(
            ScrollNavigationPageSettingsDefinition::ENTITY_NAME,
            CmsPageDefinition::ENTITY_NAME,
            'swag_cms_extensions_scroll_navigation_cms_page',
            $connection
        );

        $this->addColumn(
            FormDefinition::ENTITY_NAME,
            CmsSlotDefinition::ENTITY_NAME,
            'swag_cms_extensions_form.cms_slot_id',
            $connection
        );

        $this->changeUniqueIndex(
            FormDefinition::ENTITY_NAME,
            CmsSlotDefinition::ENTITY_NAME,
            $connection
        );
    }

    public function updateDestructive(Connection $connection): void
    {
    }

    private function addColumn(string $table, string $reference, string $constraint, Connection $connection): void
    {
        // if plugin is installed during 6.4 update, columns are created automatically
        if ($this->checkIfColumnExists($table, $reference . '_version_id', $connection)) {
            return;
        }

        $sql = <<<SQL
ALTER TABLE `#table#`
    DROP FOREIGN KEY `fk.#constraint#`,
    ADD `#reference#_version_id` BINARY(16) NOT NULL DEFAULT 0x#default# AFTER `#reference#_id`;
SQL;
        $connection->executeStatement(\str_replace(
            ['#table#', '#reference#', '#constraint#', '#default#'],
            [$table, $reference, $constraint, Defaults::LIVE_VERSION],
            $sql
        ));

        $sql = <<<SQL
ALTER TABLE `#table#`
    ADD CONSTRAINT `fk.#constraint#`
            FOREIGN KEY (`#reference#_id`, `#reference#_version_id`)
            REFERENCES `#reference#` (`id`, `version_id`)
            ON DELETE CASCADE
            ON UPDATE CASCADE;
SQL;
        $connection->executeStatement(\str_replace(
            ['#table#', '#reference#', '#constraint#'],
            [$table, $reference, $constraint],
            $sql
        ));
    }

    private function checkIfColumnExists(string $table, string $column, Connection $connection): bool
    {
        $sql = <<<SQL
SHOW COLUMNS FROM `#table#` LIKE '#column#';
SQL;

        return $connection->executeQuery(\str_replace(
            ['#table#', '#column#'],
            [$table, $column],
            $sql
        ))->fetchColumn() !== false;
    }

    private function changeUniqueIndex(string $table, string $reference, Connection $connection): void
    {
        $sql = <<<SQL
ALTER TABLE `#table#`
    DROP INDEX `uniq.#table#.#reference#_id`,
    ADD CONSTRAINT `uniq.#table#.#reference#`
        UNIQUE (`#reference#_id`, `#reference#_version_id`)
SQL;
        $connection->executeStatement(\str_replace(
            ['#table#', '#reference#'],
            [$table, $reference],
            $sql
        ));
    }
}
