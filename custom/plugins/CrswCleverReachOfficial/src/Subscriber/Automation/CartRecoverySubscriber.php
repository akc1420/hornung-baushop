<?php


namespace Crsw\CleverReachOfficial\Subscriber\Automation;

use Crsw\CleverReachOfficial\Components\Utility\Bootstrap;
use Crsw\CleverReachOfficial\Components\Utility\Initializer;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Contracts\RecoveryEmailStatus;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Interfaces\AutomationRecordService;
use Crsw\CleverReachOfficial\Core\Infrastructure\Exceptions\BaseException;
use Crsw\CleverReachOfficial\Core\Infrastructure\Logger\Logger;
use Crsw\CleverReachOfficial\Entity\Product\Repositories\ProductRepository;
use Crsw\CleverReachOfficial\Service\BusinessLogic\Automation\Entities\RecoveryRecord;
use Crsw\CleverReachOfficial\Service\BusinessLogic\Automation\RecoveryRecordService;
use Crsw\CleverReachOfficial\Service\BusinessLogic\SalesChannel\SalesChannelContextService;
use Shopware\Core\Checkout\Cart\LineItemFactoryRegistry;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Framework\Context;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Event\StorefrontRenderEvent;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPage;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class CartRecoverySubscriber
 *
 * @package Crsw\CleverReachOfficial\Subscriber\Automation
 */
class CartRecoverySubscriber implements EventSubscriberInterface
{
    /**
     * @var RecoveryRecordService
     */
    private $recoveryRecordService;
    /**
     * @var ProductRepository
     */
    private $productRepository;
    /**
     * @var LineItemFactoryRegistry
     */
    private $factory;
    /**
     * @var CartService
     */
    private $cartService;
    /**
     * @var SessionInterface
     */
    private $session;
    /**
     * @var SalesChannelContextService
     */
    private $salesChannelContextService;
    /**
     * @var AutomationRecordService
     */
    private $automationRecordService;

    /**
     * CartRecoverySubscriber constructor.
     *
     * @param Initializer $initializer
     * @param RecoveryRecordService $recoveryRecordService
     * @param ProductRepository $productRepository
     * @param LineItemFactoryRegistry $factory
     * @param CartService $cartService
     * @param SessionInterface $session
     * @param SalesChannelContextService $salesChannelContextService
     */
    public function __construct(
        Initializer $initializer,
        RecoveryRecordService $recoveryRecordService,
        ProductRepository $productRepository,
        LineItemFactoryRegistry $factory,
        CartService $cartService,
        SessionInterface $session,
        SalesChannelContextService $salesChannelContextService,
        AutomationRecordService $automationRecordService
    ) {
        Bootstrap::register();
        $initializer->registerServices();
        $this->recoveryRecordService = $recoveryRecordService;
        $this->productRepository = $productRepository;
        $this->factory = $factory;
        $this->cartService = $cartService;
        $this->session = $session;
        $this->salesChannelContextService = $salesChannelContextService;
        $this->automationRecordService = $automationRecordService;
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => 'onRecover',
            StorefrontRenderEvent::class => 'afterCartUpdate'
        ];
    }

    /**
     * Handles cart recovery.
     *
     * @param ControllerEvent $event
     */
    public function onRecover(ControllerEvent $event): void
    {
        $request = $event->getRequest();
        $routeName = $request->get('_route');

        if ($routeName !== 'frontend.checkout.confirm.page') {
            return;
        }

        $token = $request->get('token');
        $flag = $request->get('recover');

        if (empty($token) || empty($flag)) {
            return;
        }

        $crMailing = $request->get('crmailing');
        if (!empty($crMailing)) {
            $this->session->set('crMailing', $crMailing);
        }

        try {
            $record = $this->recoveryRecordService->findByToken($token);

            if ($record === null) {
                return;
            }

            /** @var SalesChannelContext $salesChannelContext */
            $salesChannelContext = $request->get('sw-sales-channel-context') ?:
                $this->salesChannelContextService->getSalesChannelContext($request);

            $this->cartService->createNew($salesChannelContext->getToken());

            $lineItems = $this->getLineItems($record, $salesChannelContext);

            $cart = $this->cartService->getCart($salesChannelContext->getToken(), $salesChannelContext);
            $this->cartService->add($cart, $lineItems, $salesChannelContext);

            $this->recoverAutomationRecord($record->getEmail());
        } catch (BaseException $e) {
            Logger::logError('Failed to recover basket because: ' . $e->getMessage(), 'Integration');
        }
    }

    /**
     * Removes items from cart if they are duplicated.
     *
     * @param StorefrontRenderEvent $event
     */
    public function afterCartUpdate(StorefrontRenderEvent $event): void
    {
        if ($event->getView() !== '@Storefront/storefront/page/checkout/confirm/index.html.twig') {
            return;
        }

        $context = $event->getSalesChannelContext();
        /** @var CheckoutConfirmPage $page */
        $page = $event->getParameters()['page'];
        $cart = $page->getCart();

        try {
            $records = $this->recoveryRecordService->find(['email' => $context->getCustomer()->getEmail()]);

            if ($records === []) {
                return;
            }

            $record = $records[0];

            if ($cart->getLineItems()->count() > count($record->getItems())) {
                $newCart = $this->cartService->createNew($context->getToken());

                $lineItems = $this->getLineItems($record, $context);

                $newCart = $this->cartService->add($newCart, $lineItems, $context);
                $page->setCart($newCart);
                $this->cartService->setCart($newCart);
            }
        } catch (BaseException $e) {
            Logger::logError('Failed to retrieve recovery record because ' . $e->getMessage());
        }
    }

    /**
     * @param string $email
     * @param string $cartId
     *
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Exceptions\FailedToUpdateAutomationRecordException
     */
    protected function recoverAutomationRecord(string $email): void
    {
        $records = $this->automationRecordService->findBy(['email' => $email, 'status' => RecoveryEmailStatus::SENT]);
        if (!empty($records[0])) {
            $record = $records[0];
            $record->setIsRecovered(true);
            $this->automationRecordService->update($record);
        }
    }

    /**
     * @param RecoveryRecord $record
     * @param SalesChannelContext $context
     *
     * @return array
     */
    protected function getLineItems(RecoveryRecord $record, SalesChannelContext $context): array
    {
        $lineItems = [];

        foreach ($record->getItems() as $key => $value) {
            $product = $this->productRepository->getProductById($key, Context::createDefaultContext());

            if (!$product) {
                continue;
            }

            $lineItem = $this->factory->create([
                'type' => 'product',
                'referencedId' => $product->getId(),
                'quantity' => $value
            ], $context);

            $lineItems[] = $lineItem;
        }

        return $lineItems;
    }
}
