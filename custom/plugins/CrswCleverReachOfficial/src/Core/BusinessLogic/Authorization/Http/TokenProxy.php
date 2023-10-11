<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Http;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Http\Proxy;

/**
 * Class TokenProxy
 *
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Http
 */
class TokenProxy extends Proxy
{
    /**
     * Class name.
     */
    const CLASS_NAME = __CLASS__;

    /**
     * Revokes access token by deleting it.
     *
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRefreshAccessToken
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRetrieveAuthInfoException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpRequestException
     */
    public function revoke()
    {
        $this->delete('oauth/token.json');
    }
}