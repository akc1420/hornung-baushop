<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\Pipeline;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\DTO\AbandonedCartTrigger;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\Exceptions\FailedToPassFilterException;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\DTO\Receiver;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\Http\Proxy;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;

class ReceiverFilter extends Filter
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
        if (($receiver = $this->getReceiver($trigger)) === null) {
            throw new FailedToPassFilterException(
                "Receiver not found [{$trigger->getGroupId()}:{$trigger->getPoolId()}]."
            );
        }

        if (!$this->isReceiverActive($receiver)) {
            throw new FailedToPassFilterException(
                "Receiver not active [{$trigger->getGroupId()}:{$trigger->getPoolId()}]."
            );
        }
    }

    /**
     * Retrieves receiver from proxy.
     *
     * @param \Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\DTO\AbandonedCartTrigger $trigger
     *
     * @return \Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\DTO\Receiver|\Crsw\CleverReachOfficial\Core\Infrastructure\Data\DataTransferObject|null
     */
    private function getReceiver(AbandonedCartTrigger $trigger)
    {
        try {
            $receiver = $this->getProxy()->getReceiver($trigger->getGroupId(), $trigger->getPoolId());
        } catch (\Exception $e) {
            $receiver = null;
        }

        return $receiver;
    }

    /**
     * Checks if receiver is active.
     *
     * @param \Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\DTO\Receiver $receiver
     *
     * @return bool
     */
    private function isReceiverActive(Receiver $receiver)
    {
        $deactivated = $receiver->getDeactivated();
        $activated = $receiver->getActivated();

        return !empty($activated) && (empty($deactivated) || $activated->getTimestamp() > $deactivated->getTimestamp());
    }

    /**
     * Retrieves receiver proxy.
     *
     * @return Proxy | object
     */
    private function getProxy()
    {
        return ServiceRegister::getService(Proxy::CLASS_NAME);
    }
}