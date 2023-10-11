<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\Events;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\Contracts\AbandonedCartRecordService;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;

class AbandonedCartUpdatedEventListener
{
    public function handle(AbandonedCartUpdatedEvent $event)
    {
        $record = $this->getService()->getByCartId($event->getTrigger()->getCartId());

        if ($record) {
            $record->setCartId($event->getTrigger()->getCartId());
            $record->setGroupId($event->getTrigger()->getGroupId());
            $record->setPoolId($event->getTrigger()->getPoolId());
            $record->setEmail($event->getTrigger()->getPoolId());
            $record->setTrigger($event->getTrigger());
            $record->setCustomerId($event->getTrigger()->getCustomerId());
            $this->getService()->update($record);
        }
    }

    /**
     * Retrieves record service.
     *
     * @return AbandonedCartRecordService | object
     */
    private function getService()
    {
        return ServiceRegister::getService(AbandonedCartRecordService::CLASS_NAME);
    }
}