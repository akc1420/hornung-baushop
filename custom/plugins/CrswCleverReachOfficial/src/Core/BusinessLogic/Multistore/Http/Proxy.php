<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\Http;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Http\Proxy as BaseProxy;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\DTO\AutomationDetails;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\DTO\AutomationSubmit;

/**
 * Class Proxy
 *
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\Http
 */
class Proxy extends BaseProxy
{
    const CLASS_NAME = __CLASS__;

    /**
     * Creates automation details.
     *
     * @param \Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\DTO\AutomationSubmit $automation
     *
     * @return \Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\DTO\AutomationDetails
     *
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRefreshAccessToken
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRetrieveAuthInfoException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpRequestException
     */
    public function create(AutomationSubmit $automation)
    {
        $response = $this->post('automation/createfromtemplate/abandonedcart.json', $automation->toArray());

        return AutomationDetails::fromArray($response->decodeBodyToArray());
    }
}