<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\CmsExtensions\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;
use Swag\CmsExtensions\ScrollNavigation\Aggregate\ScrollNavigationPageSettings\ScrollNavigationPageSettingsDefinition;

class Migration1591277478SmoothScrollingPageSettings extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1_591_277_478;
    }

    public function update(Connection $connection): void
    {
        $sql = <<<'SQL'
CREATE TABLE `#table#` (
    `id`            BINARY(16)   NOT NULL,
    `cms_page_id`   BINARY(16)   DEFAULT NULL,
    `active`        TINYINT(1)   NOT NULL DEFAULT 0,
    `duration`      INT(11)      NOT NULL DEFAULT 1000,
    `easing`        VARCHAR(255) NOT NULL DEFAULT 'inOut',
    `easing_degree` INT(11)      NOT NULL DEFAULT 3,
    `bouncy`        TINYINT(1)   NOT NULL DEFAULT 0,
    `created_at`    DATETIME(3)  NOT NULL,
    `updated_at`    DATETIME(3)  NULL,
    PRIMARY KEY (`id`)    ,
    CONSTRAINT `fk.swag_cms_extensions_scroll_navigation_cms_page`
        FOREIGN KEY (`cms_page_id`)
        REFERENCES `cms_page` (`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE
)
ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;
SQL;

        $connection->executeStatement(\str_replace(
            ['#table#'],
            [ScrollNavigationPageSettingsDefinition::ENTITY_NAME],
            $sql
        ));
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
