<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\CmsExtensions\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;
use Swag\CmsExtensions\ScrollNavigation\Aggregate\ScrollNavigationTranslation\ScrollNavigationTranslationDefinition;
use Swag\CmsExtensions\ScrollNavigation\ScrollNavigationDefinition;

class Migration1582193703ScrollNavigation extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1_582_193_703;
    }

    public function update(Connection $connection): void
    {
        $this->createScrollNavigationTable($connection);
        $this->createScrollNavigationTranslationTable($connection);
    }

    /**
     * {@inheritdoc}
     */
    public function updateDestructive(Connection $connection): void
    {
    }

    private function createScrollNavigationTable(Connection $connection): void
    {
        $sql = <<<'SQL'
CREATE TABLE IF NOT EXISTS `#table#` (
    `id`             BINARY(16)  NOT NULL,
    `cms_section_id` BINARY(16)  DEFAULT NULL,
    `active`         BOOLEAN,
    `created_at`     DATETIME(3) NOT NULL,
    `updated_at`     DATETIME(3) DEFAULT NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk.swag_cms_extensions_scroll_navigation_cms_section`
        FOREIGN KEY (`cms_section_id`)
        REFERENCES `cms_section` (`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE
)
ENGINE = InnoDB
DEFAULT CHARSET = utf8mb4
COLLATE = utf8mb4_unicode_ci;
SQL;

        $connection->executeStatement(\str_replace(
            ['#table#'],
            [ScrollNavigationDefinition::ENTITY_NAME],
            $sql
        ));
    }

    private function createScrollNavigationTranslationTable(Connection $connection): void
    {
        $sql = <<<'SQL'
CREATE TABLE IF NOT EXISTS `#table#` (
    `swag_cms_extensions_scroll_navigation_id` BINARY(16)   NOT NULL,
    `language_id`                              BINARY(16)   NOT NULL,
    `display_name`                             VARCHAR(255) COLLATE utf8mb4_unicode_ci NULL,
    `created_at`                               DATETIME(3)  NOT NULL,
    `updated_at`                               DATETIME(3)  DEFAULT NULL,
    PRIMARY KEY (`swag_cms_extensions_scroll_navigation_id`, `language_id`),
    CONSTRAINT `fk.swag_scroll_navigation_translation.swag_scroll_navigation_id`
        FOREIGN KEY (`swag_cms_extensions_scroll_navigation_id`)
        REFERENCES `#referenceTable#` (`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT `fk.swag_scroll_navigation_translation.language_id`
        FOREIGN KEY (`language_id`)
        REFERENCES `language` (`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE
)
ENGINE = InnoDB
DEFAULT CHARSET = utf8mb4
COLLATE = utf8mb4_unicode_ci;
SQL;

        $connection->executeStatement(\str_replace(
            ['#table#', '#referenceTable#'],
            [ScrollNavigationTranslationDefinition::ENTITY_NAME, ScrollNavigationDefinition::ENTITY_NAME],
            $sql
        ));
    }
}
