<?php


namespace Crsw\CleverReachOfficial\Migration\MigrationSteps;


use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\Contracts\SyncConfigService;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;
use Crsw\CleverReachOfficial\Migration\Exceptions\FailedToExecuteMigrationStepException;
use Crsw\CleverReachOfficial\Service\BusinessLogic\SyncSettings\SubscriberSyncSettings;
use Exception;

/**
 * Class SetDefaultSyncConfig
 *
 * @package Crsw\CleverReachOfficial\Migration\MigrationSteps
 */
class SetDefaultSyncConfig extends Step
{
    /**
     * Set default sync configuration.
     *
     * @throws FailedToExecuteMigrationStepException
     */
    public function execute(): void
    {
        try {
            $this->getSyncConfigService()->setEnabledServices([new SubscriberSyncSettings()]);
        } catch (Exception $e) {
            throw new FailedToExecuteMigrationStepException(
                'Failed to execute SetDefaultSyncConfig step because: ' . $e->getMessage()
            );
        }
    }

    /**
     * @return SyncConfigService
     */
    private function getSyncConfigService(): SyncConfigService
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return ServiceRegister::getService(SyncConfigService::CLASS_NAME);
    }
}