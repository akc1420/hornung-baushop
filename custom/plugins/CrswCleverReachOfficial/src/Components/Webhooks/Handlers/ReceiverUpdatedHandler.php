<?php


namespace Crsw\CleverReachOfficial\Components\Webhooks\Handlers;

use Exception;

/**
 * Class ReceiverUpdatedHandler
 *
 * @package Crsw\CleverReachOfficial\Components\Webhooks\Handlers
 */
class ReceiverUpdatedHandler extends Handler
{

    /**
     * Handles receiver updated handler.
     *
     * @throws Exception
     */
    public function execute(): void
    {
        $receiver = $this->getReceiver($this->getGroupService()->getId(), $this->receiverId);
        $subscriber = $this->getSubscriberService()->getReceiver($receiver->getEmail());

        $this->reportProgress(30);
        $this->handleSubscriberUpdateOrCreateEvent($receiver, $subscriber);
        $this->reportProgress(100);
    }
}