<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Webhooks;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Entities\CartAutomation;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Exceptions\FailedToHandleWebhookException;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Interfaces\AutomationRecordService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Interfaces\CartAutomationService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\WebHookEvent\DTO\WebHook;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;

/**
 * Class Handler
 *
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Webhooks
 */
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
     * Handles cart automation webhook.
     *
     * @param \Crsw\CleverReachOfficial\Core\BusinessLogic\WebHookEvent\DTO\WebHook $hook
     *
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Exceptions\FailedToDeleteAutomationRecordException
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Exceptions\FailedToDeleteCartException
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Exceptions\FailedToHandleWebhookException
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Exceptions\FailedToUpdateCartException
     */
    public function handle(WebHook $hook)
    {
        $this->validate($hook);
        $carts = $this->getCartService()->findBy(array('condition' => $hook->getCondition()));
        $cart = !empty($carts[0]) ? $carts[0] : null;
        if (empty($cart)) {
            throw new FailedToHandleWebhookException('Cart not found [' . $hook->getCondition() . '].');
        }

        switch ($hook->getEvent()) {
            case 'automation.activated':
                $this->activateCart($cart);
                break;
            case 'automation.deactivated':
                $this->deactivateCart($cart);
                break;
            case 'automation.deleted':
                $this->deleteCart($cart);
                break;
        }
    }

    /**
     * Structurally validates webhook.
     *
     * @param \Crsw\CleverReachOfficial\Core\BusinessLogic\WebHookEvent\DTO\WebHook $hook
     *
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Exceptions\FailedToHandleWebhookException
     */
    private function validate(WebHook $hook)
    {
        if (!in_array($hook->getEvent(), static::$supportedEvents, true)) {
            throw new FailedToHandleWebhookException('Event [' . $hook->getEvent() . '] not supported.');
        }

        $condition = $hook->getCondition();
        if (empty($condition)) {
            throw new FailedToHandleWebhookException('Condition not provided.');
        }
    }

    /**
     * Activates cart.
     *
     * @param \Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Entities\CartAutomation $cart
     *
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Exceptions\FailedToUpdateCartException
     */
    private function activateCart(CartAutomation $cart)
    {
        $cart->setIsActive(true);
        $this->getCartService()->update($cart);
    }

    /**
     * Deactivates cart.
     *
     * @param \Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Entities\CartAutomation $cart
     *
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Exceptions\FailedToUpdateCartException
     */
    private function deactivateCart(CartAutomation $cart)
    {
        $cart->setIsActive(false);
        $this->getCartService()->update($cart);
    }

    /**
     * Deletes cart.
     *
     * @param \Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Entities\CartAutomation $cart
     *
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Exceptions\FailedToDeleteAutomationRecordException
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Exceptions\FailedToDeleteCartException
     */
    private function deleteCart(CartAutomation $cart)
    {
        $this->getRecordService()->deleteBy(array('automationId' => $cart->getId()));
        $this->getCartService()->delete($cart->getId());
    }

    /**
     * Provides cart automation service.
     *
     * @return CartAutomationService | object
     */
    private function getCartService()
    {
        return ServiceRegister::getService(CartAutomationService::CLASS_NAME);
    }

    /**
     * Provides automation record service.
     *
     * @return AutomationRecordService | object
     */
    private function getRecordService()
    {
        return ServiceRegister::getService(AutomationRecordService::CLASS_NAME);
    }
}