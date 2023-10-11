<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\PaymentPlan;

use Crsw\CleverReachOfficial\Core\BusinessLogic\PaymentPlan\Contracts\PaymentPlanService as BaseService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\PaymentPlan\Http\Proxy;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;

class PaymentPlanService implements BaseService
{
    /**
     * Retrieves payment plan.
     *
     * @param string $clientId
     *
     * @return \Crsw\CleverReachOfficial\Core\BusinessLogic\PaymentPlan\DTO\PaymentPlan
     *
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRefreshAccessToken
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRetrieveAuthInfoException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpRequestException
     */
    public function getPlanInfo($clientId)
    {
        return $this->getProxy()->getPaymentPlan($clientId);
    }

    /**
     * Retrieves the count of active receivers.
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
        return $this->getProxy()->getActiveReceiversCount($clientId);
    }

    /**
     * Retrieves payment plan proxy.
     *
     * @return Proxy
     */
    private function getProxy()
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return ServiceRegister::getService(Proxy::CLASS_NAME);
    }
}