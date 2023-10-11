<?php


namespace Crsw\CleverReachOfficial\Components\Webhooks\Handlers;

use Exception;

/**
 * Class ReceiverCreatedHandler
 *
 * @package Crsw\CleverReachOfficial\Components\Webhooks\Handlers
 */
class ReceiverCreatedHandler extends Handler
{
    /**
     * Handles receiver created event.
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