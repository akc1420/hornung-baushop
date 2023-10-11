<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Tasks\Trigger\Filter;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\DTO\Trigger;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Entities\AutomationRecord;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Exceptions\FailedToPassFilterException;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Interfaces\CartAutomationService;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;

/**
 * Class AutomationFilter
 *
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Tasks\Trigger\Filter
 */
class AutomationFilter extends Filter
{
    /**
     * Checks if the record and cart data satisfy necessary requirements
     * before the mail can be sent.
     *
     * @param \Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Entities\AutomationRecord $record
     * @param \Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\DTO\Trigger $cartData
     *
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Exceptions\FailedToPassFilterException
     *
     * @return void
     */
    public function pass(AutomationRecord $record, Trigger $cartData)
    {
        $automation = $this->getAutomationService()->find($record->getAutomationId());
        if ($automation === null || !$automation->isActive()) {
            throw new FailedToPassFilterException('Automation does not exist or is not active.');
        }
    }

    /**
     * Provides cart automation service.
     *
     * @return CartAutomationService | object
     */
    private function getAutomationService()
    {
        return ServiceRegister::getService(CartAutomationService::CLASS_NAME);
    }
}