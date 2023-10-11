<?php


namespace Crsw\CleverReachOfficial\Components\Webhooks\Listeners;

use Crsw\CleverReachOfficial\Components\Webhooks\Handlers\ReceiverUnsubscribedHandler;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\Events\ReceiverUnsubscribedEvent;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException;

/**
 * Class ReceiverUnsubscribedListener
 *
 * @package Crsw\CleverReachOfficial\Components\Webhooks\Listeners
 */
class ReceiverUnsubscribedListener extends Listener
{
    /**
     * Enqueues ReceiverUnsubscribedHandler.
     *
     * @param ReceiverUnsubscribedEvent $event
     *
     * @throws QueueStorageUnavailableException
     */
    public static function handle(ReceiverUnsubscribedEvent $event): void
    {
        static::enqueue(new ReceiverUnsubscribedHandler($event->getReceiverId()));
    }
}