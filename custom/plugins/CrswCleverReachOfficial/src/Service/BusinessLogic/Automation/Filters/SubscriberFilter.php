<?php

namespace Crsw\CleverReachOfficial\Service\BusinessLogic\Automation\Filters;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\DTO\Trigger;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Entities\AutomationRecord;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Exceptions\FailedToPassFilterException;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Tasks\Trigger\Filter\Filter;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;
use Crsw\CleverReachOfficial\Service\BusinessLogic\Customer\SubscriberService;
use Exception;

/**
 * Class SubscriberFilter
 *
 * @package Crsw\CleverReachOfficial\Service\BusinessLogic\Automation\Filters
 */
class SubscriberFilter extends Filter
{
    /**
     * Checks if the record and cart data satisfy necessary requirements
     * before the mail can be sent.
     *
     * @param AutomationRecord $record
     * @param Trigger $cartData
     *
     * @throws FailedToPassFilterException
     * @throws Exception
     */
    public function pass(AutomationRecord $record, Trigger $cartData)
    {
        $email = $record->getEmail();
        if (!$this->isSubscriber($email)) {
            throw new FailedToPassFilterException("$email is not a subscriber.");
        }
    }

    /**
     * Checks if receiver is subscriber.
     *
     * @param $email
     * @return bool
     *
     * @throws Exception
     */
    private function isSubscriber($email)
    {
        $subscriber = $this->getSubscriberService()->getReceiver($email);

        return $subscriber !== null;
    }

    /**
     * Provides subscriber service.
     *
     * @return SubscriberService | object
     */
    private function getSubscriberService()
    {
        return ServiceRegister::getService(SubscriberService::class);
    }
}
