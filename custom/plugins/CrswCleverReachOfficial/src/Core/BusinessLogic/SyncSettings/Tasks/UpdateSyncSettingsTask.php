<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\SyncSettings\Tasks;

use Crsw\CleverReachOfficial\Core\BusinessLogic\SyncSettings\Contracts\SyncSettingsService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\SyncSettings\DTO\SyncSettings;
use Crsw\CleverReachOfficial\Core\BusinessLogic\SyncSettings\Http\Proxy;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\Task;

/**
 * Class UpdateSyncSettingsTask
 *
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic\SyncSettings\Tasks
 */
class UpdateSyncSettingsTask extends Task
{
    const CLASS_NAME = __CLASS__;

    /**
     * Updates sync settings.
     *
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRefreshAccessToken
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRetrieveAuthInfoException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpRequestException
     */
    public function execute()
    {
        $synchronized = array();
        foreach ($this->getSyncSettingsService()->getEnabledServices() as $service) {
            $synchronized[] = $service->getUuid();
        }

        $this->reportProgress(30);

        $notSynchronized = array();
        foreach ($this->getSyncSettingsService()->getAvailableServices() as $service) {
            if (!in_array($service->getUuid(), $synchronized, true)) {
                $notSynchronized[] = $service->getUuid();
            }
        }

        $this->reportProgress(70);

        $postData = new SyncSettings($synchronized, $notSynchronized);
        $this->getProxy()->updateSyncSettings($postData);

        $this->reportProgress(100);
    }

    /**
     * Retrieves sync settings service.
     *
     * @return SyncSettingsService
     */
    private function getSyncSettingsService()
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return ServiceRegister::getService(SyncSettingsService::CLASS_NAME);
    }

    /**
     * Retrieves sync settings proxy.
     *
     * @return Proxy
     */
    private function getProxy()
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return ServiceRegister::getService(Proxy::CLASS_NAME);
    }
}