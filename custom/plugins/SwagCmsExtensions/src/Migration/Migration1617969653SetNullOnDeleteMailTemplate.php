<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\CmsExtensions\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1617969653SetNullOnDeleteMailTemplate extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1_617_969_653;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement(
            'ALTER TABLE `swag_cms_extensions_form` DROP FOREIGN KEY `fk.swag_cms_extensions_form.mail_template_id`;'
        );

        $connection->executeStatement(
            'ALTER TABLE `swag_cms_extensions_form` MODIFY `mail_template_id` BINARY(16) NULL'
        );

        $connection->executeStatement(
            'ALTER TABLE `swag_cms_extensions_form` ADD CONSTRAINT `fk.swag_cms_extensions_form.mail_template_id` FOREIGN KEY (`mail_template_id`) REFERENCES `mail_template`(`id`) ON DELETE SET NULL ON UPDATE CASCADE;'
        );
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
