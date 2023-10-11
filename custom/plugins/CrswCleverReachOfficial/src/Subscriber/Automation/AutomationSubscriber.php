<?php

namespace Crsw\CleverReachOfficial\Subscriber\Automation;

use Crsw\CleverReachOfficial\Components\Utility\Bootstrap;
use Crsw\CleverReachOfficial\Components\Utility\Initializer;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Group\GroupService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Contracts\RecoveryEmailStatus;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Entities\AutomationRecord;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Services\AutomationRecordService;
use Crsw\CleverReachOfficial\Core\Infrastructure\Exceptions\BaseException;
use Crsw\CleverReachOfficial\Core\Infrastructure\Logger\Logger;
use Crsw\CleverReachOfficial\Service\BusinessLogic\Automation\AutomationService;
use Crsw\CleverReachOfficial\Service\BusinessLogic\Automation\RecoveryRecordService;
use Crsw\CleverReachOfficial\Service\BusinessLogic\SalesChannel\SalesChannelContextService;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\Event\CartDeletedEvent;
use Shopware\Core\Checkout\Cart\Event\CartSavedEvent;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Customer\Event\CustomerRegisterEvent;
use Shopware\Core\Checkout\Order\OrderEvents;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenEvent;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class AutomationSubscriber
 *
 * @package Crsw\CleverReachOfficial\Subscriber\Automation
 */
class AutomationSubscriber implements EventSubscriberInterface
{
    /**
     * @var AutomationService
     */
    private $automationService;
    /**
     * @var GroupService
     */
    private $groupService;
    /**
     * @var AutomationRecordService
     */
    private $automationRecordService;
    /**
     * @var CartService
     */
    private $cartService;
    /**
     * @var RequestStack
     */
    private $requestStack;
    /**
     * @var RecoveryRecordService
     */
    private $recoveryRecordService;
    /**
     * @var ParameterBagInterface
     */
    private $params;
    /**
     * @var SalesChannelContextService
     */
    private $salesChannelContextService;
    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * AutomationSubscriber constructor.
     *
     * @param Initializer $initializer
     * @param AutomationService $automationService
     * @param GroupService $groupService
     * @param AutomationRecordService $automationRecordService
     * @param CartService $cartService
     * @param RequestStack $requestStack
     * @param RecoveryRecordService $recoveryRecordService
     * @param ParameterBagInterface $params
     * @param SalesChannelContextService $salesChannelContextService
     */
    public function __construct(
        Initializer $initializer,
        AutomationService $automationService,
        GroupService $groupService,
        AutomationRecordService $automationRecordService,
        CartService $cartService,
        RequestStack $requestStack,
        RecoveryRecordService $recoveryRecordService,
        ParameterBagInterface $params,
        SalesChannelContextService $salesChannelContextService,
        SessionInterface $session
    ) {
        Bootstrap::register();
        $initializer->registerServices();

        $this->automationService = $automationService;
        $this->groupService = $groupService;
        $this->automationRecordService = $automationRecordService;
        $this->cartService = $cartService;
        $this->requestStack = $requestStack;
        $this->recoveryRecordService = $recoveryRecordService;
        $this->params = $params;
        $this->salesChannelContextService = $salesChannelContextService;
        $this->session = $session;
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents(): array
    {
        return [
            CartSavedEvent::class => 'onCartCreated',
            CustomerRegisterEvent::class => 'onCustomerRegistered',
            CartDeletedEvent::class => 'onCartDeleted',
            OrderEvents::ORDER_WRITTEN_EVENT => 'onOrderCreated',
            KernelEvents::CONTROLLER => 'onCheckoutRegistration'
        ];
    }

    /**
     * Handles customer registration during checkout.
     *
     * @param ControllerEvent $event
     */
    public function onCheckoutRegistration(ControllerEvent $event): void
    {
        $request = $event->getRequest();
        $route = $request->get('_route');

        if ($route !== 'frontend.checkout.confirm.page') {
            return;
        }

        /** @var SalesChannelContext $context */
        $context = $request->get('sw-sales-channel-context') ?:
            $this->salesChannelContextService->getSalesChannelContext($request);

        $customer = $context->getCustomer();

        if (!$customer) {
            return;
        }

        $cartId = $context->getToken();
        $cart = $this->cartService->getCart($cartId, $context);

        if (!$cart || $this->isRecordAlreadyCreated($cartId)) {
            return;
        }

        $storeId = $context->getSalesChannel()->getId();
        $this->handleRecordCreated($storeId, $cart, $customer);
    }

    /**
     * Handles cart created event.
     *
     * @param CartSavedEvent $event
     */
    public function onCartCreated(CartSavedEvent $event): void
    {
        $request = $this->requestStack->getCurrentRequest();

        if (!$request) {
            return;
        }

        if (version_compare($this->params->get('kernel.shopware_version'), '6.4.0', 'lt')) {
            $context = $event->getContext();
        } else {
            $context = $event->getSalesChannelContext();
        }
        $cart = $event->getCart();
        $customer = $context->getCustomer();

        if (!$cart || !$customer) {
            return;
        }

        if ($this->isRecordAlreadyCreated($cart->getToken())) {
            $this->refreshScheduleTime($cart);

            return;
        }

        $storeId = $context->getSalesChannel()->getId();
        $this->handleRecordCreated($storeId, $cart, $customer);
    }

    /**
     * Handles customer registered event.
     *
     * @param CustomerRegisterEvent $event
     */
    public function onCustomerRegistered(CustomerRegisterEvent $event): void
    {
        $customer = $event->getCustomer();
        $cartId = $event->getSalesChannelContext()->getToken();
        $cart = $this->cartService->getCart($cartId, $event->getSalesChannelContext());

        if (!$cart || $this->isRecordAlreadyCreated($cartId)) {
            return;
        }

        $storeId = $event->getSalesChannelId();
        $this->handleRecordCreated($storeId, $cart, $customer);
    }

    /**
     * Handles cart deleted event.
     *
     * @param CartDeletedEvent $event
     */
    public function onCartDeleted(CartDeletedEvent $event): void
    {
        if (version_compare($this->params->get('kernel.shopware_version'), '6.4.0', 'lt')) {
            $context = $event->getContext();
        } else {
            $context = $event->getSalesChannelContext();
        }

        $cartId = $context->getToken();
        $customer = $context->getCustomer();

        $this->deleteRecord($cartId, $customer);
    }

    /**
     * Handles order created event.
     *
     * @param EntityWrittenEvent $event
     */
    public function onOrderCreated(EntityWrittenEvent $event): void
    {
        $request = $this->requestStack->getCurrentRequest();

        if (!$request || empty($request->attributes->get('sw-sales-channel-id'))) {
            return;
        }

        /** @var SalesChannelContext $salesChannelContext */
        $salesChannelContext = $request->get('sw-sales-channel-context') ?:
            $this->salesChannelContextService->getSalesChannelContext($request);

        $cartId = $salesChannelContext->getToken();
        $customer = $salesChannelContext->getCustomer();

        $this->deleteRecord($cartId, $customer);
    }

    /**
     * @param string $basketId
     * @return mixed
     */
    private function isRecordAlreadyCreated(string $basketId): bool
    {
        return $this->session->get('cr_ac_' . $basketId, false);
    }

    /**
     * @param string $basketId
     * @param bool $status
     */
    private function setRecordAlreadyCreated(string $basketId, bool $status): void
    {
        $this->session->set('cr_ac_' . $basketId, $status);
    }

    /**
     * @param string $storeId
     * @param Cart $cart
     * @param CustomerEntity $customer
     */
    private function handleRecordCreated(string $storeId, Cart $cart, CustomerEntity $customer): void
    {
        try {
            $automation = $this->automationService->get($storeId);

            if (!$automation || !$automation->isActive() || $automation->getStatus() !== 'created') {
                return;
            }

            $oldRecord = $this->automationRecordService->findBy(
                [
                    'automationId' => $automation->getId(),
                    'email' => $customer->getEmail(),
                    'status' => RecoveryEmailStatus::PENDING,
                ]
            );

            if (!empty($oldRecord)) {
                $oldRecord[0]->setCartId($cart->getToken());
                $this->automationRecordService->update($oldRecord[0]);
            } else {
                $record = new AutomationRecord();
                $record->setAutomationId($automation->getId());
                $record->setCartId($cart->getToken());
                $record->setGroupId($this->groupService->getId());
                $record->setEmail($customer->getEmail());

                $this->automationRecordService->create($record);
            }

            $this->setRecordAlreadyCreated($cart->getToken(), true);
        } catch (BaseException $e) {
            Logger::logError('Failed to create cart record because ' . $e->getMessage());
        }
    }

    /**
     * @param string|null $cartId
     * @param CustomerEntity|null $customer
     */
    private function deleteRecord(?string $cartId, ?CustomerEntity $customer): void
    {
        if (!$cartId || !$customer) {
            return;
        }

        try {
            $this->automationRecordService->deleteBy(['email' => $customer->getEmail(), 'isRecovered' => false]);
            $this->setRecordAlreadyCreated($cartId, false);

            $record = $this->recoveryRecordService->find(['email' => $customer->getEmail(), 'token' => $cartId]);
            if ($record && isset($record[0])) {
                $this->recoveryRecordService->delete($record[0]);
            }
        } catch (BaseException $e) {
            Logger::logError('Failed to delete cart record because ' . $e->getMessage());
        }
    }

    /**
     * Refreshes schedule time for given cart.
     *
     * @param Cart $cart
     */
    private function refreshScheduleTime(Cart $cart): void
    {
        try {
            $records = $this->automationRecordService->findBy(['cartId' => $cart->getToken()]);

            if (empty($records[0])) {
                return;
            }

            $this->automationRecordService->refreshScheduleTime($records[0]);
        } catch (BaseException $e) {
            Logger::logError($e->getMessage(), 'Integration');
        }
    }
}
