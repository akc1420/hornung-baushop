<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\CmsExtensions\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\InheritanceUpdaterTrait;
use Shopware\Core\Framework\Migration\MigrationStep;
use Swag\CmsExtensions\Quickview\QuickviewDefinition;

class Migration1571303196Quickview extends MigrationStep
{
    use InheritanceUpdaterTrait;

    public function getCreationTimestamp(): int
    {
        return 1_571_303_196;
    }

    public function update(Connection $connection): void
    {
        $sql = <<<'SQL'
CREATE TABLE IF NOT EXISTS `#table#` (
    `id`           BINARY(16)  NOT NULL,
    `cms_block_id` BINARY(16)  DEFAULT NULL,
    `active`       BOOLEAN,
    `created_at`   DATETIME(3) NOT NULL,
    `updated_at`   DATETIME(3) DEFAULT NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk.swag_cms_extensions_quickview_cms_block`
        FOREIGN KEY (`cms_block_id`) REFERENCES `cms_block` (`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE
)

ENGINE = InnoDB
DEFAULT CHARSET = utf8mb4
COLLATE = utf8mb4_unicode_ci;
SQL;

        $connection->executeStatement(\str_replace(
            ['#table#'],
            [QuickviewDefinition::ENTITY_NAME],
            $sql
        ));
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
