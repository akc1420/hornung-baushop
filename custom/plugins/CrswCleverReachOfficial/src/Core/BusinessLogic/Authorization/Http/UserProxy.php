<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Http;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\DTO\UserInfo;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Http\Proxy;

class UserProxy extends Proxy
{
    const CLASS_NAME = __CLASS__;

    /**
     * Retrieves current User Info.
     *
     * @return UserInfo
     *
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRefreshAccessToken
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRetrieveAuthInfoException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpRequestException
     */
    public function getUserInfo()
    {
        $response = $this->get('debug/whoami.json');

        return UserInfo::fromArray($response->decodeBodyToArray());
    }

    /**
     * Retrieves user's language.
     *
     * @param string $userId
     *
     * @return string
     *
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRefreshAccessToken
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRetrieveAuthInfoException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpRequestException
     */
    public function getUserLanguage($userId)
    {
        $response = $this->get("clients.json/$userId/users");
        $response = $response->decodeBodyToArray();

        return $response[0]['lang'];
    }
}