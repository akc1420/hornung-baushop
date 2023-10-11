<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\PaymentPlan\Contracts;

interface PaymentPlanService
{
    const CLASS_NAME = __CLASS__;

    /**
     * Retrieves payment plan info for a particular client identified by the client id.
     * 
     * @param string $clientId CleverReach client id (not integration client id).
     *
     * @return \Crsw\CleverReachOfficial\Core\BusinessLogic\PaymentPlan\DTO\PaymentPlan
     */
    public function getPlanInfo($clientId);

    /**
     * Retrieves count of active receivers for a client.
     *
     * @param string $clientId CleverReach client id (not integration client id).
     *
     * @return int
     */
    public function getActiveReceiversCount($clientId);
}