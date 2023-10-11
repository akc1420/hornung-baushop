<?php


namespace Crsw\CleverReachOfficial\Components\Webhooks\Handlers;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRefreshAccessToken;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRetrieveAuthInfoException;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Group\Contracts\GroupService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\DTO\Receiver;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\Http\Proxy;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\Tasks\Composite\Configuration\SyncConfiguration;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\Tasks\Composite\ReceiverSyncTask;
use Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpCommunicationException;
use Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpRequestException;
use Crsw\CleverReachOfficial\Core\Infrastructure\Logger\Logger;
use Crsw\CleverReachOfficial\Core\Infrastructure\Serializer\Serializer;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\QueueService;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\Task;
use Crsw\CleverReachOfficial\Service\BusinessLogic\Customer\SubscriberService;
use Exception;

/**
 * Class Handler
 *
 * @package Crsw\CleverReachOfficial\Components\Webhooks\Handlers
 */
abstract class Handler extends Task
{
    protected $receiverId;

    /**
     * Handler constructor.
     *
     * @param string $receiverId
     */
    public function __construct(string $receiverId)
    {
        $this->receiverId = $receiverId;
    }

    /**
     * @inheritDoc
     */
    public static function fromArray(array $array)
    {
        return new static($array['receiverId']);
    }

    /**
     * @inheritDoc
     */
    public function serialize(): string
    {
        return Serializer::serialize($this->receiverId);
    }

    /**
     * @inheritDoc
     */
    public function unserialize($serialized): void
    {
        $this->receiverId = (int)Serializer::unserialize($serialized);
    }

    /**
     * @inheritDoc
     */
    public function toArray(): array
    {
        return [
            'receiverId' => $this->receiverId
        ];
    }

    /**
     * Retrieves receiver from CleverReach.
     *
     * @param $groupId
     * @param $receiverId
     *
     * @return Receiver
     *
     * @throws FailedToRefreshAccessToken
     * @throws FailedToRetrieveAuthInfoException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     */
    protected function getReceiver($groupId, $receiverId): Receiver
    {
        return $this->getReceiverProxy()->getReceiver($groupId, $receiverId);
    }

    /**
     * @return Proxy
     */
    protected function getReceiverProxy(): Proxy
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return ServiceRegister::getService(Proxy::CLASS_NAME);
    }

    /**
     * Creates or updates subscriber.
     *
     * @param Receiver $receiver
     * @param Receiver|null $subscriber
     *
     * @throws Exception
     */
    protected function createOrUpdateSubscriber(Receiver $receiver, Receiver $subscriber = null): void
    {
        if ($subscriber !== null) {
            $this->getSubscriberService()->updateSubscriber($receiver);
        } else {
            $this->getSubscriberService()->createSubscriber($receiver);
            $this->enqueue(new ReceiverSyncTask(new SyncConfiguration([$receiver->getEmail()])));
        }
    }

    /**
     * @return SubscriberService
     */
    protected function getSubscriberService(): SubscriberService
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return ServiceRegister::getService(SubscriberService::class);
    }

    /**
     * @return GroupService
     */
    protected function getGroupService(): GroupService
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return ServiceRegister::getService(GroupService::class);
    }

    /**
     * Enqueues task.
     *
     * @param Task $task
     */
    protected function enqueue(Task $task): void
    {
        $queueName = $this->getConfigService()->getDefaultQueueName();

        try {
            $this->getQueueService()->enqueue($queueName, $task);
        } catch (QueueStorageUnavailableException $e) {
            Logger::logError($e->getMessage(), 'Integration');
        }
    }

    /**
     * @return QueueService
     */
    protected function getQueueService(): QueueService
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return ServiceRegister::getService(QueueService::CLASS_NAME);
    }

    /**
     * Checks is receiver active.
     *
     * @param Receiver $receiver
     *
     * @return bool
     */
    protected function isReceiverActive(Receiver $receiver): bool
    {
        return ($receiver->getDeactivated() !== null && $receiver->getDeactivated()->getTimestamp() !== 0)
            || ($receiver->getActivated() !== null && $receiver->getActivated()->getTimestamp() !== 0);
    }

    /**
     * Handles subscriber update or create event.
     *
     * @param Receiver $receiver
     * @param Receiver|null $subscriber
     *
     * @throws Exception
     */
    protected function handleSubscriberUpdateOrCreateEvent(Receiver $receiver, ?Receiver $subscriber): void
    {
        if (!$this->isReceiverActive($receiver)) {
            if ($subscriber !== null) {
                $this->getSubscriberService()->unsubscribeSubscriber($subscriber);
            }

            return;
        }

        $this->createOrUpdateSubscriber($receiver, $subscriber);
    }
}
