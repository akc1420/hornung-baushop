<?php


namespace Crsw\CleverReachOfficial\Components\Webhooks\Listeners;

use Crsw\CleverReachOfficial\Components\Webhooks\Handlers\ReceiverUpdatedHandler;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\Events\ReceiverUpdatedEvent;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException;

/**
 * Class ReceiverUpdatedListener
 *
 * @package Crsw\CleverReachOfficial\Components\Webhooks\Listeners
 */
class ReceiverUpdatedListener extends Listener
{
    /**
     * Enqueues ReceiverUpdatedHandler.
     *
     * @param ReceiverUpdatedEvent $event
     *
     * @throws QueueStorageUnavailableException
     */
    public static function handle(ReceiverUpdatedEvent $event): void
    {
        static::enqueue(new ReceiverUpdatedHandler($event->getReceiverId()));
    }
}