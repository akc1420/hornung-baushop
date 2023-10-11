<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\PaymentPlan\Http;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Http\Proxy as BaseProxy;
use Crsw\CleverReachOfficial\Core\BusinessLogic\PaymentPlan\DTO\PaymentPlan;

class Proxy extends BaseProxy
{
    const CLASS_NAME = __CLASS__;

    /**
     * Retrieves payment plan.
     *
     * @param string $clientId CleverReach client id. (Not integration client id).
     *
     * @return \Crsw\CleverReachOfficial\Core\BusinessLogic\PaymentPlan\DTO\PaymentPlan
     *
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRefreshAccessToken
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRetrieveAuthInfoException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpRequestException
     */
    public function getPaymentPlan($clientId)
    {
        $response = $this->get("clients.json/$clientId/plan");

        return PaymentPlan::fromArray($response->decodeBodyToArray());
    }

    /**
     * Returns the count of active receivers.
     *
     * @param string $clientId
     *
     * @return int
     *
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRefreshAccessToken
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRetrieveAuthInfoException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpRequestException
     */
    public function getActiveReceiversCount($clientId)
    {
        $response = $this->get("clients.json/$clientId/receivercount");

        return (int) $response->getBody();
    }
}