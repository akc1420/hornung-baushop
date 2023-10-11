<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\Events;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\Contracts\AbandonedCartRecordService;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;

class AbandonedCartConvertedEventListener
{
    /**
     * Deletes record for converted cart.
     *
     * @param \Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\Events\AbandonedCartConvertedEvent $event
     */
    public function handle(AbandonedCartConvertedEvent $event)
    {
        $record = $this->getService()->getByCartId($event->getTrigger()->getCartId());

        if ($record) {
            $this->getService()->delete($record->getGroupId(), $record->getPoolId());
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