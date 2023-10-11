<?php declare(strict_types=1);

namespace Crsw\CleverReachOfficial\Migration;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Group\Contracts\GroupService;
use Crsw\CleverReachOfficial\Core\Infrastructure\Configuration\Configuration;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\QueueService;
use Crsw\CleverReachOfficial\Migration\RegisterEvents\UpdateRegisteredRoutes;
use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1642507826UpdateApiRoutes extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1642507826;
    }

    public function update(Connection $connection): void
    {
        if ($this->getGroupService()->getId() !== '') {
            $this->getQueueService()->enqueue(
                $this->getConfigService()->getDefaultQueueName(),
                new UpdateRegisteredRoutes()
            );
        }
    }

    public function updateDestructive(Connection $connection): void
    {
        // No need for update destructive
    }

    /**
     * @return GroupService
     */
    private function getGroupService(): GroupService
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return ServiceRegister::getService(GroupService::CLASS_NAME);
    }

    /**
     * @return QueueService
     */
    private function getQueueService(): QueueService
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return ServiceRegister::getService(QueueService::CLASS_NAME);
    }

    /**
     * @return Configuration
     */
    private function getConfigService(): Configuration
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return ServiceRegister::getService(Configuration::CLASS_NAME);
    }
}
