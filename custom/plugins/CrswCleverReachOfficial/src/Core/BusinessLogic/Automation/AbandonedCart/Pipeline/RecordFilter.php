<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\Pipeline;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\Contracts\AbandonedCartRecordService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\DTO\AbandonedCartTrigger;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\Exceptions\FailedToPassFilterException;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;

class RecordFilter extends Filter
{
    /**
     * Checks whether trigger can pass the filter.
     *
     * @param \Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\DTO\AbandonedCartTrigger $trigger
     *
     * @return void
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\Exceptions\FailedToPassFilterException
     *
     */
    public function pass(AbandonedCartTrigger $trigger)
    {
        $record = $this->getService()->get($trigger->getGroupId(), $trigger->getPoolId());

        if ($record !== null) {
            throw new FailedToPassFilterException(
                "Record already created for [{$trigger->getGroupId()}:{$trigger->getPoolId()}]."
            );
        }
    }

    /**
     * @return AbandonedCartRecordService | object
     */
    private function getService()
    {
        return ServiceRegister::getService(AbandonedCartRecordService::CLASS_NAME);
    }
}