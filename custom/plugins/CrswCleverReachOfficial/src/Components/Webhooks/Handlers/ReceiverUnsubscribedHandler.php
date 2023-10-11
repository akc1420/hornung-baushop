<?php


namespace Crsw\CleverReachOfficial\Components\Webhooks\Handlers;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\Tasks\Composite\UnsubscribeReceiverTask;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\Exceptions\AbortTaskExecutionException;
use Exception;

/**
 * Class ReceiverUnsubscribedHandler
 *
 * @package Crsw\CleverReachOfficial\Components\Webhooks\Handlers
 */
class ReceiverUnsubscribedHandler extends Handler
{
    /**
     * Handles receiver unsubscribed event.
     *
     * @throws AbortTaskExecutionException
     * @throws Exception
     */
    public function execute(): void
    {
        $receiver = $this->getReceiver($this->getGroupService()->getId(), $this->receiverId);
        $subscriber = $this->getSubscriberService()->getReceiver($receiver->getEmail());

        if ($subscriber === null) {
            throw new AbortTaskExecutionException("Receiver [{$receiver->getEmail()}] cannot be unsubscribed.");
        }

        $this->reportProgress(60);

        $this->getSubscriberService()->unsubscribeSubscriber($subscriber);
        $this->enqueue(new UnsubscribeReceiverTask($receiver->getEmail()));

        $this->reportProgress(100);
    }
}