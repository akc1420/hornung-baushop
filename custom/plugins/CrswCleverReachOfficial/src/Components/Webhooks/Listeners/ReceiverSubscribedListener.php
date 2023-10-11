<?php


namespace Crsw\CleverReachOfficial\Components\Webhooks\Listeners;

use Crsw\CleverReachOfficial\Components\Webhooks\Handlers\ReceiverSubscribedHandler;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\Events\ReceiverSubscribedEvent;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException;

/**
 * Class ReceiverSubscribedListener
 *
 * @package Crsw\CleverReachOfficial\Components\Webhooks\Listeners
 */
class ReceiverSubscribedListener extends Listener
{
    /**
     * Enqueues ReceiverSubscribedHandler.
     *
     * @param ReceiverSubscribedEvent $event
     *
     * @throws QueueStorageUnavailableException
     */
    public static function handle(ReceiverSubscribedEvent $event): void
    {
        static::enqueue(new ReceiverSubscribedHandler($event->getReceiverId()));
    }
}