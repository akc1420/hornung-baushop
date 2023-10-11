<?php
declare(strict_types=1);

namespace Kzph\Simplenote;

use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Doctrine\DBAL\Connection;

class KzphSimplenote extends Plugin
{
    public function uninstall(UninstallContext $context): void
	{
		parent::uninstall($context);

        if ($context->keepUserData()) {
            return;
        }

		/** Drop database tables */
		$this->dropDatabaseTables();
	}

	private function dropDatabaseTables(): void
	{
		$connection = $this->container->get(Connection::class);
		$connection->executeStatement('DROP TABLE IF EXISTS `kzph_simplenote`');
	}
}