<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Report\Http;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Report\DTO\Report;

/**
 * Class Proxy
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic\Report\Http
 */
class Proxy extends \Crsw\CleverReachOfficial\Core\BusinessLogic\Http\Proxy
{
    const CLASS_NAME = __CLASS__;

    /**
     * Returns certain report
     *
     * @param string $mailingId id of mailing
     *
     * @return \Crsw\CleverReachOfficial\Core\BusinessLogic\Report\DTO\Report
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRefreshAccessToken
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRetrieveAuthInfoException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpRequestException
     */
    public function getReport($mailingId)
    {
        $response = $this->get("reports.json/$mailingId");

        return Report::fromArray($response->decodeBodyToArray());
    }
}
