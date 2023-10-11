<?php

namespace Crsw\CleverReachOfficial\Service\BusinessLogic\MigratedUser;

use Crsw\CleverReachOfficial\Core\Infrastructure\Configuration\Configuration;
use Crsw\CleverReachOfficial\Core\Infrastructure\Configuration\ConfigurationManager;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\QueueService;
use Crsw\CleverReachOfficial\Migration\InitialSync\InitialSyncTask;

/**
 * Class MigratedUserService
 *
 * @package Crsw\CleverReachOfficial\Service\BusinessLogic\MigratedUser
 */
class MigratedUserService
{
    /**
     * @var QueueService
     */
    private $queueService;

    /**
     * MigratedUserService constructor.
     *
     * @param QueueService $queueService
     */
    public function __construct(QueueService $queueService)
    {
        $this->queueService = $queueService;
    }

    /**
     * If user is migrated enqueue migration InitialSyncTask.
     *
     * @throws QueryFilterInvalidParamException
     * @throws QueueStorageUnavailableException
     */
    public function enqueueMigrationInitialSyncTask(): void
    {
        if (ConfigurationManager::getInstance()->getConfigValue('userMigrated')) {
            $this->enqueueMigrationSyncTask();
        }
    }

    /**
     * Enqueues migration InitialSyncTask.
     *
     * @throws QueueStorageUnavailableException
     * @throws QueryFilterInvalidParamException
     */
    private function enqueueMigrationSyncTask(): void
    {
        if (!$this->queueService->findLatestByType('InitialSyncTask')) {
            $this->queueService->enqueue($this->getConfigService()->getDefaultQueueName(), new InitialSyncTask());
        }

        ConfigurationManager::getInstance()->saveConfigValue('userMigrated', false);
    }

    /**
     * @return object | Configuration
     */
    private function getConfigService()
    {
        return ServiceRegister::getService(Configuration::class);
    }
}