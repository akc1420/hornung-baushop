<?php


namespace Crsw\CleverReachOfficial\Migration;

use Crsw\CleverReachOfficial\Core\Infrastructure\Configuration\ConfigurationManager;
use Crsw\CleverReachOfficial\Core\Infrastructure\Logger\Logger;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException;
use Crsw\CleverReachOfficial\Migration\Exceptions\ApiCredentialsNotPresentException;
use Crsw\CleverReachOfficial\Migration\Exceptions\FailedToExecuteMigrationStepException;
use Crsw\CleverReachOfficial\Migration\MigrationSteps\CreateSchedules;
use Crsw\CleverReachOfficial\Migration\MigrationSteps\EnqueueInitialSyncTask;
use Crsw\CleverReachOfficial\Migration\MigrationSteps\MigrateApiCredentials;
use Crsw\CleverReachOfficial\Migration\MigrationSteps\MigrateCleverReachWebhooksData;
use Crsw\CleverReachOfficial\Migration\MigrationSteps\MigrateDynamicContentData;
use Crsw\CleverReachOfficial\Migration\MigrationSteps\MigrateGroupId;
use Crsw\CleverReachOfficial\Migration\MigrationSteps\SaveUserInfo;
use Crsw\CleverReachOfficial\Migration\MigrationSteps\SetDefaultSyncConfig;
use Crsw\CleverReachOfficial\Migration\MigrationSteps\Step;
use Crsw\CleverReachOfficial\Migration\Repository\V2Repository;
use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

/**
 * Class Migration1607423732Version200
 *
 * @package Crsw\CleverReachOfficial\Migration
 */
class Migration1607423732Version200 extends MigrationStep
{
    protected static $migrationSteps = [
        MigrateApiCredentials::class,
        SaveUserInfo::class,
        MigrateGroupId::class,
        MigrateCleverReachWebhooksData::class,
        MigrateDynamicContentData::class,
        CreateSchedules::class,
        SetDefaultSyncConfig::class,
        EnqueueInitialSyncTask::class
    ];

    /**
     * @inheritDoc
     *
     * @return int
     */
    public function getCreationTimestamp(): int
    {
        return 1607423732;
    }

    /**
     * @param Connection $connection
     */
    public function update(Connection $connection): void
    {
        try {
            V2Repository::setConnection($connection);

            if (!V2Repository::configTableExists()) {
                return;
            }

            foreach (self::$migrationSteps as $step) {
                /** @var Step $executor */
                $executor = new $step;
                $executor->execute();
            }

            $this->removeTables($connection);
        } catch (FailedToExecuteMigrationStepException $e) {
            Logger::logError('Failed to update plugin to version 2.0.0 because: ' . $e->getMessage());
            return;
        } catch (APICredentialsNotPresentException $e) {
            // If API credentials are not present in cleverreach_config table
            // other migration steps should not be executed.
            Logger::logInfo('Successfully executed migration, API credentials not present in cleverreach_config table.');
            $this->removeTables($connection);
            return;
        }

        try {
            ConfigurationManager::getInstance()->saveConfigValue('pluginOpened', false);
        } catch (QueryFilterInvalidParamException $e) {
            Logger::logError($e->getMessage());
        }

        Logger::logInfo('Successfully executed migration.');
    }

    public function updateDestructive(Connection $connection): void
    {
        // No need for update destructive.
    }

    protected function removeTables(Connection $connection): void
    {
        $sql = 'DROP TABLE IF EXISTS cleverreach_configs, cleverreach_queues, cleverreach_processes';

        $connection->executeUpdate($sql);
    }
}