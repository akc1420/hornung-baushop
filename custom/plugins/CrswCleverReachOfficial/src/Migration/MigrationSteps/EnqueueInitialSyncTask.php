<?php


namespace Crsw\CleverReachOfficial\Migration\MigrationSteps;


use Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Contracts\AuthorizationService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Group\Contracts\GroupService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\InitialSynchronization\Tasks\Composite\InitialSyncTask;
use Crsw\CleverReachOfficial\Core\Infrastructure\Configuration\Configuration;
use Crsw\CleverReachOfficial\Core\Infrastructure\Configuration\ConfigurationManager;
use Crsw\CleverReachOfficial\Core\Infrastructure\Exceptions\BaseException;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\QueueService;
use Crsw\CleverReachOfficial\Migration\Exceptions\FailedToExecuteMigrationStepException;
use Crsw\CleverReachOfficial\Migration\InitialSync\InitialSyncTask as MigrationInitialSyncTask;

/**
 * Class EnqueueInitialSyncTask
 * @package Crsw\CleverReachOfficial\Migration\MigrationSteps
 */
class EnqueueInitialSyncTask extends Step
{
    /**
     * Enqueues InitialSyncTask.
     *
     * @throws FailedToExecuteMigrationStepException
     */
    public function execute(): void
    {
        try {
            if ($this->getAuthService()->isOffline()) {
                ConfigurationManager::getInstance()->saveConfigValue('userMigrated', true);
                return;
            }

            if ($this->getGroupService()->getId() !== '') {
                $this->getQueueService()->enqueue(
                    $this->getConfigService()->getDefaultQueueName(),
                    new MigrationInitialSyncTask()
                );
            } else {
                $this->getQueueService()->enqueue(
                    $this->getConfigService()->getDefaultQueueName(),
                    new InitialSyncTask()
                );
            }
        } catch (BaseException $e) {
            throw new FailedToExecuteMigrationStepException(
                'Failed to execute EnqueueInitialSyncTask step because: ' . $e->getMessage()
            );
        }
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

    /**
     * @return AuthorizationService
     */
    private function getAuthService(): AuthorizationService
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return ServiceRegister::getService(AuthorizationService::CLASS_NAME);
    }
}