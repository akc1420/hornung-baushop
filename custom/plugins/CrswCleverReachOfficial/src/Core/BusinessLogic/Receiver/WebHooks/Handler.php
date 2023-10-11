<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\WebHooks;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Group\Contracts\GroupService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\Events\ReceiverCreatedEvent;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\Events\ReceiverDeletedEvent;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\Events\ReceiverEventBus;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\Events\ReceiverSubscribedEvent;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\Events\ReceiverUnsubscribedEvent;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\Events\ReceiverUpdatedEvent;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\Http\Proxy;
use Crsw\CleverReachOfficial\Core\BusinessLogic\WebHookEvent\DTO\WebHook;
use Crsw\CleverReachOfficial\Core\BusinessLogic\WebHookEvent\Exceptions\UnableToHandleWebHookException;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;

class Handler
{
    protected static $supportedEvents = array(
        'receiver.created' => ReceiverCreatedEvent::CLASS_NAME,
        'receiver.updated' => ReceiverUpdatedEvent::CLASS_NAME,
        'receiver.subscribed' => ReceiverSubscribedEvent::CLASS_NAME,
        'receiver.unsubscribed' => ReceiverUnsubscribedEvent::CLASS_NAME,
    );

    protected static $receiverDeletedEvent = 'receiver.deleted';

    /**
     * Handles receiver web hook event.
     *
     * @param \Crsw\CleverReachOfficial\Core\BusinessLogic\WebHookEvent\DTO\WebHook $hook
     *
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\WebHookEvent\Exceptions\UnableToHandleWebHookException
     */
    public function handle(WebHook $hook)
    {
        if ($hook->getEvent() === static::$receiverDeletedEvent) {
            return;
        }

        if (!array_key_exists($hook->getEvent(), static::$supportedEvents)) {
            throw new UnableToHandleWebHookException('Event [' . $hook->getEvent() . '] not supported.');
        }

        if ($hook->getCondition() !== $this->getGroupService()->getId()) {
            throw new UnableToHandleWebHookException('Invalid group id.');
        }

        $payload = $hook->getPayload();
        if (empty($payload['pool_id'])) {
            throw new UnableToHandleWebHookException('Invalid payload.');
        }

        try {
            $this->getProxy()->getReceiver($hook->getCondition(), $payload['pool_id']);
        } catch (\Exception $e) {
            throw new UnableToHandleWebHookException($e->getMessage(), $e->getCode(), $e->getPrevious());
        }

        $event = static::$supportedEvents[$hook->getEvent()];
        ReceiverEventBus::getInstance()->fire(new $event($payload['pool_id']));
    }

    /**
     * @return GroupService | object
     */
    private function getGroupService()
    {
        return ServiceRegister::getService(GroupService::CLASS_NAME);
    }

    /**
     * @return Proxy | object
     */
    protected function getProxy()
    {
        return ServiceRegister::getService(Proxy::CLASS_NAME);
    }
}