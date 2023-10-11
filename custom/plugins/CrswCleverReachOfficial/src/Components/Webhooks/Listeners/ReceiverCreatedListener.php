<?php


namespace Crsw\CleverReachOfficial\Components\Webhooks\Listeners;

use Crsw\CleverReachOfficial\Components\Webhooks\Handlers\ReceiverCreatedHandler;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\Events\ReceiverCreatedEvent;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException;

/**
 * Class ReceiverCreatedListener
 *
 * @package Crsw\CleverReachOfficial\Components\Webhooks\Listeners
 */
class ReceiverCreatedListener extends Listener
{
    /**
     * Enqueues ReceiverCreatedHandler.
     *
     * @param ReceiverCreatedEvent $event
     *
     * @throws QueueStorageUnavailableException
     */
    public static function handle(ReceiverCreatedEvent $event): void
    {
        static::enqueue(new ReceiverCreatedHandler($event->getReceiverId()));
    }
}