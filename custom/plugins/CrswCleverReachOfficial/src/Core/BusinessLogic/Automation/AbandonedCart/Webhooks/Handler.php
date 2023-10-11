<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\Webhooks;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\Contracts\AbandonedCartEntityService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\Contracts\AbandonedCartRecordService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\DTO\AbandonedCart;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\Events\AutomationActivatedEvent;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\Events\AutomationDeactivatedEvent;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\Events\AutomationDeletedEvent;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\Events\AutomationEventsBus;
use Crsw\CleverReachOfficial\Core\BusinessLogic\WebHookEvent\DTO\WebHook;
use Crsw\CleverReachOfficial\Core\BusinessLogic\WebHookEvent\Exceptions\UnableToHandleWebHookException;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;

class Handler
{
    /**
     * List of supported events.
     *
     * @var string[]
     */
    protected static $supportedEvents = array(
        'automation.activated',
        'automation.deactivated',
        'automation.deleted',
    );

    /**
     * Handles automation related events.
     *
     * @param \Crsw\CleverReachOfficial\Core\BusinessLogic\WebHookEvent\DTO\WebHook $hook
     *
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\WebHookEvent\Exceptions\UnableToHandleWebHookException
     */
    public function handle(WebHook $hook)
    {
        $cart = $this->getEntityService()->get();

        if ($cart === null) {
            throw new UnableToHandleWebHookException('No automation chain is found in the database.');
        }

        $this->validateWebhook($hook, $cart);
        $this->handleWebhook($hook, $cart);
    }

    /**
     * Validates received webhook against persisted cart automation.
     *
     * @param \Crsw\CleverReachOfficial\Core\BusinessLogic\WebHookEvent\DTO\WebHook $hook
     * @param \Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\DTO\AbandonedCart $cart
     *
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\WebHookEvent\Exceptions\UnableToHandleWebHookException
     */
    protected function validateWebhook(WebHook $hook, AbandonedCart $cart)
    {
        if (!in_array($hook->getEvent(), static::$supportedEvents, true)) {
            throw new UnableToHandleWebHookException('Event [' . $hook->getEvent() . '] not supported.');
        }

        if ($cart->getId() !== $hook->getCondition()) {
            throw new UnableToHandleWebHookException(
                "Event not registered for automation chain [{$hook->getCondition()}]"
            );
        }
    }

    /**
     * Handles received webhook.
     *
     * @param \Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\DTO\AbandonedCart $cart
     * @param \Crsw\CleverReachOfficial\Core\BusinessLogic\WebHookEvent\DTO\WebHook $hook
     */
    protected function handleWebhook(WebHook $hook, AbandonedCart $cart) {
        if ($hook->getEvent() === 'automation.deleted') {
            $this->deleteCart();
        } else {
            $this->updateCartStatus($cart, $hook->getEvent());
        }

        $this->fireEvents($hook);
    }

    /**
     * Updates cart status.
     *
     * @param \Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\DTO\AbandonedCart $cart
     * @param string $event
     */
    protected function updateCartStatus(AbandonedCart $cart, $event)
    {
        $cart->setActive($event === 'automation.activated');
        $this->getEntityService()->set($cart);
    }

    /**
     * Deletes cart.
     */
    protected function deleteCart()
    {
        $this->deleteCartData();
        $this->deleteRecords();
        $this->deleteAutomationEventData();
    }

    /**
     * Deletes cart data from the database.
     */
    protected function deleteCartData()
    {
        $this->getEntityService()->set(null);
    }

    /**
     * Deletes all records with associated schedules.
     */
    protected function deleteRecords()
    {
        $this->getRecordsService()->deleteAllRecords();
    }

    /**
     * Deletes automation event data.
     */
    protected function deleteAutomationEventData()
    {
        $this->getEventsService()->setCallToken('');
        $this->getEventsService()->setSecret('');
        $this->getEventsService()->setVerificationToken('');
    }

    /**
     * Fires events that notify other systems that automation webhook has occurred.
     *
     * @param \Crsw\CleverReachOfficial\Core\BusinessLogic\WebHookEvent\DTO\WebHook $hook
     */
    protected function fireEvents(WebHook $hook)
    {
        switch ($hook->getEvent()) {
            case 'automation.activated':
                AutomationEventsBus::getInstance()->fire(new AutomationActivatedEvent($hook));
                break;
            case 'automation.deactivated':
                AutomationEventsBus::getInstance()->fire(new AutomationDeactivatedEvent($hook));
                break;
            case 'automation.deleted':
                AutomationEventsBus::getInstance()->fire(new AutomationDeletedEvent($hook));
                break;
        }
    }

    /**
     * @return AbandonedCartEntityService | object
     */
    private function getEntityService()
    {
        return ServiceRegister::getService(AbandonedCartEntityService::CLASS_NAME);
    }

    /**
     * Retrieves abandoned cart record service.
     *
     * @return AbandonedCartRecordService | object
     */
    private function getRecordsService()
    {
        return ServiceRegister::getService(AbandonedCartRecordService::CLASS_NAME);
    }

    /**
     * @return \Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\Webhooks\EventsService | object
     */
    private function getEventsService()
    {
        return ServiceRegister::getService(EventsService::CLASS_NAME);
    }
}