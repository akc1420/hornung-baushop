<?php


namespace Crsw\CleverReachOfficial\Components\EventHandlers;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\DTO\Tag\Tag;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\Tasks\Composite\Configuration\SyncConfiguration;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\Tasks\Composite\ReceiverSyncTask;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\Tasks\Composite\SubscribeReceiverTask;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\Tasks\Composite\UnsubscribeReceiverTask;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\Tasks\DeactivateReceiverTask;

/**
 * Class RecipientHandler
 *
 * @package Crsw\CleverReachOfficial\EventHandlers
 */
class RecipientHandler extends BaseHandler
{
    /**
     * Resyncs recipient.
     *
     * @param array $emails
     * @param Tag[] $tags
     */
    public function resyncRecipient(array $emails, array $tags = []): void
    {
        $this->enqueueTask(new ReceiverSyncTask(new SyncConfiguration($emails, $tags, false)));
    }

    /**
     * Handles recipient unsubscribed event.
     *
     * @param string $email
     */
    public function recipientUnsubscribedEvent(string $email): void
    {
        $this->enqueueTask(new UnsubscribeReceiverTask($email));
    }

    /**
     * Handles recipient subscribed event.
     *
     * @param string $email
     */
    public function recipientSubscribedEvent(string $email): void
    {
        $this->enqueueTask(new SubscribeReceiverTask($email));
    }

    /**
     * Handles recipient delete event.
     *
     * @param string $email
     */
    public function recipientDeletedEvent(string $email): void
    {
        $this->enqueueTask(new DeactivateReceiverTask($email));
    }
}
