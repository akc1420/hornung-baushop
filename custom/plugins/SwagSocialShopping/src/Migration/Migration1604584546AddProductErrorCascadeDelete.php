<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagSocialShopping\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1604584546AddProductErrorCascadeDelete extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1604584546;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement(
            <<<SQL
ALTER TABLE `swag_social_shopping_product_error` DROP FOREIGN KEY `fk.swag_social_shopping_product_error.product_id`;
SQL
        );
        $connection->executeStatement(
            <<<SQL
ALTER TABLE `swag_social_shopping_product_error` ADD CONSTRAINT `fk.swag_social_shopping_product_error.product_id` 
FOREIGN KEY (`product_id`,`product_version_id`) 
REFERENCES `product` (`id`,`version_id`)
ON DELETE CASCADE ON UPDATE CASCADE;
SQL
        );
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
