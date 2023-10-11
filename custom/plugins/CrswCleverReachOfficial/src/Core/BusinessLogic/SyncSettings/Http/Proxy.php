<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\SyncSettings\Http;

use Crsw\CleverReachOfficial\Core\BusinessLogic\SyncSettings\DTO\SyncSettings;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Http\Proxy as BaseProxy;

class Proxy extends BaseProxy
{
    const CLASS_NAME = __CLASS__;

    /**
     * Updates sync settings.
     *
     * @param \Crsw\CleverReachOfficial\Core\BusinessLogic\SyncSettings\DTO\SyncSettings $settings
     *
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRefreshAccessToken
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRetrieveAuthInfoException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpRequestException
     */
    public function updateSyncSettings(SyncSettings $settings)
    {
        $this->post('oauth/settings.json', $settings->toArray());
    }
}