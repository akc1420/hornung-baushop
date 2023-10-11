<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Dashboard;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Dashboard\Contracts\DashboardService as BaseService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\SecondarySynchronization\Tasks\Composite\SecondarySyncTask;
use Crsw\CleverReachOfficial\Core\Infrastructure\Configuration\ConfigurationManager;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\QueueService;

class DashboardService implements BaseService
{
    /**
     * @inheritDoc
     *
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     */
    public function isSyncStatisticsDisplayed()
    {
        return $this->getConfigurationManager()->getConfigValue('isSyncStatisticsDisplayed', false);
    }

    /**
     * @inheritDoc
     *
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     */
    public function setSyncStatisticsDisplayed($status)
    {
        $this->getConfigurationManager()->saveConfigValue('isSyncStatisticsDisplayed', $status);
    }

    /**
     * @inheritDoc
     *
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     */
    public function getSyncedReceiversCount()
    {
        return $this->getConfigurationManager()->getConfigValue('numberOfSyncedReceivers', 0);
    }

    /**
     * @inheritDoc
     *
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     */
    public function setSyncedReceiversCount($count)
    {
        $this->getConfigurationManager()->saveConfigValue('numberOfSyncedReceivers', $count);
    }

    /**
     * Returns date of the last secondary sync task
     *
     * @return string|null
     */
    public function getLastSyncJobTime()
    {
        /** @var QueueService $queueService */
        $queueService = ServiceRegister::getService(QueueService::CLASS_NAME);
        $secondarySyncItem = $queueService->findLatestByType(
            SecondarySyncTask::getClassName(),
            $this->getConfigurationManager()->getContext()
        );

        if ($secondarySyncItem && $secondarySyncItem->getFinishTimestamp()) {
            $dateTime = new \DateTime("@{$secondarySyncItem->getFinishTimestamp()}");

            return $dateTime->format('d-m-Y H:i:s');
        }

        return null;
    }

    /**
     * Retrieves configuration manager.
     *
     * @return \Crsw\CleverReachOfficial\Core\Infrastructure\Configuration\ConfigurationManager Configuration Manager instance.
     */
    protected function getConfigurationManager()
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return ServiceRegister::getService(ConfigurationManager::CLASS_NAME);
    }
}