<?php


namespace Crsw\CleverReachOfficial\Components\Webhooks\Handlers;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\Tasks\Composite\SubscribeReceiverTask;
use Exception;

/**
 * Class ReceiverSubscribedHandler
 *
 * @package Crsw\CleverReachOfficial\Components\Webhooks\Handlers
 */
class ReceiverSubscribedHandler extends Handler
{
    /**
     * Handles receiver subscribed event.
     *
     * @throws Exception
     */
    public function execute(): void
    {
        $receiver = $this->getReceiver($this->getGroupService()->getId(), $this->receiverId);
        $subscriber = $this->getSubscriberService()->getReceiver($receiver->getEmail());

        $this->reportProgress(30);

        $this->enqueue(new SubscribeReceiverTask($receiver->getEmail()));
        $this->createOrUpdateSubscriber($receiver, $subscriber);

        $this->reportProgress(100);
    }
}