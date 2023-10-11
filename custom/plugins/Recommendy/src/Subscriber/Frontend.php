<?php declare(strict_types=1);

namespace Recommendy\Subscriber;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DbalException;
use Psr\Log\LoggerInterface;
use Recommendy\Components\Struct\ActionStruct;
use Recommendy\Core\Content\Tracking\TrackingEntity;
use Recommendy\Services\Interfaces\BundleServiceInterface;
use Recommendy\Services\Interfaces\ConfigServiceInterface;
use Recommendy\Services\Interfaces\SimilarityServiceInterface;
use Recommendy\Services\Interfaces\TrackingServiceInterface;
use Shopware\Core\Checkout\Cart\Event\BeforeLineItemAddedEvent;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Checkout\Customer\Event\CustomerBeforeLoginEvent;
use Shopware\Core\Checkout\Customer\Event\CustomerLoginEvent;
use Shopware\Core\Checkout\Customer\Event\CustomerRegisterEvent;
use Shopware\Core\Checkout\Customer\Event\GuestCustomerRegisterEvent;
use Shopware\Core\Content\Product\Events\ProductListingResultEvent;
use Shopware\Core\Content\Product\Events\ProductSearchResultEvent;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Struct\ArrayEntity;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\Entity\SalesChannelRepository;
use Shopware\Storefront\Page\Checkout\Cart\CheckoutCartPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Finish\CheckoutFinishPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Offcanvas\OffcanvasCartPageLoadedEvent;
use Shopware\Storefront\Page\Navigation\NavigationPageLoadedEvent;
use Shopware\Storefront\Page\PageLoadedEvent;
use Shopware\Storefront\Page\Product\ProductPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class Frontend implements EventSubscriberInterface
{
    /** @var SimilarityServiceInterface */
    private $similarityService;
    /** @var SimilarityServiceInterface */
    private $bundleService;
    /** @var TrackingServiceInterface */
    private $trackingService;
    /** @var Connection */
    private $connection;
    /** @var ConfigServiceInterface */
    private $configService;
    /** @var CartService */
    private $cartService;
    /** @var SalesChannelRepository */
    private $productRepository;
    /** @var EntityRepository */
    private $recommendyTrackingRepository;
    /** @var RequestStack */
    private $requestStack;
    /** @var LoggerInterface */
    private $logger;
    /** @var string */
    private $sessionId;
    /** @var string */
    private $oldSessionId;

    /**
     * @param SimilarityServiceInterface $similarityService
     * @param BundleServiceInterface $bundleService
     * @param TrackingServiceInterface $trackingService
     * @param Connection $connection
     * @param ConfigServiceInterface $configService
     * @param CartService $cartService
     * @param SalesChannelRepository $productRepository
     * @param EntityRepository $recommendyTrackingRepository
     * @param RequestStack $requestStack
     * @param LoggerInterface $logger
     */
    public function __construct(
        SimilarityServiceInterface      $similarityService,
        BundleServiceInterface          $bundleService,
        TrackingServiceInterface        $trackingService,
        Connection                      $connection,
        ConfigServiceInterface          $configService,
        CartService                     $cartService,
        SalesChannelRepository $productRepository,
        EntityRepository       $recommendyTrackingRepository,
        RequestStack                    $requestStack,
        LoggerInterface                 $logger
    )
    {
        $this->similarityService = $similarityService;
        $this->bundleService = $bundleService;
        $this->trackingService = $trackingService;
        $this->connection = $connection;
        $this->configService = $configService;
        $this->cartService = $cartService;
        $this->productRepository = $productRepository;
        $this->recommendyTrackingRepository = $recommendyTrackingRepository;
        $this->requestStack = $requestStack;
        $this->logger = $logger;
        $this->sessionId = '';
        $this->oldSessionId = '';
    }
// CheckoutOffcanvasWidgetLoadedHook
    /**
     * @return array[]
     */
    public static function getSubscribedEvents(): array
    {
        return [
            NavigationPageLoadedEvent::class => 'onNavigationPageLoadedEvent',
            ProductPageLoadedEvent::class => 'onProductPageLoadedEvent',
            CheckoutCartPageLoadedEvent::class => 'onCheckoutCartPageLoadedEvent',
            BeforeLineItemAddedEvent::class => 'onBeforeLineItemAddedEvent',
            CheckoutFinishPageLoadedEvent::class => 'onCheckoutFinishPageLoadedEvent',
            CustomerLoginEvent::class => 'onCustomerLoginEvent',
            CustomerBeforeLoginEvent::class => 'onCustomerBeforeLoginEvent',
            ProductListingResultEvent::class => 'onProductListingResultEvent',
            OffcanvasCartPageLoadedEvent::class => 'onOffcanvasCartPageLoadedEvent',
            ProductSearchResultEvent::class => 'onProductListingResultEvent',
            GuestCustomerRegisterEvent::class => 'onGuestCustomerRegisterEvent',
            CustomerRegisterEvent::class => 'onCustomerRegisterEvent',
        ];
    }

    /**
     * @param OffcanvasCartPageLoadedEvent $event
     */
    public function onOffcanvasCartPageLoadedEvent(OffcanvasCartPageLoadedEvent $event)
    {
        $configStruct = $this->configService->getConfigStruct(
            $event->getSalesChannelContext()->getSalesChannel()->getId()
        );
        if (!$configStruct->isActiveOffcanvasBasket()) {
            return;
        }

        $basketProductIds = array_filter(array_map(function ($lineItem) {
            if ($lineItem->isGood()) {
                return $lineItem->getId();
            }
        }, $event->getPage()->getCart()->getLineItems()->getElements()));

        $cartBundleArticles = $this->bundleService->getCartBundleArticles($basketProductIds, $event->getSalesChannelContext());

        $event->getPage()->addExtension("Recommendy", new ArrayEntity([
            'bundleArticles' => $cartBundleArticles
        ]));



    }

    /**
     * @param ProductListingResultEvent $event
     */
    public function onProductListingResultEvent(ProductListingResultEvent $event)
    {
        $entities = $event->getResult()->getElements();


        foreach ($entities as $entity) {
            $entity->addExtension("RecommendyData", new ArrayEntity([
                'available' => $this->similarityService->similarProductsAvailable( $entity->getId() , $event->getSalesChannelContext())
            ]));
        }
    }

    /**
     * @param CheckoutCartPageLoadedEvent $event
     */
    public function onCheckoutCartPageLoadedEvent(CheckoutCartPageLoadedEvent $event)
    {
        $configStruct = $this->configService->getConfigStruct(
            $event->getSalesChannelContext()->getSalesChannel()->getId()
        );
        if (!$configStruct->isActiveBasketSlider()) {
            return;
        }
        $basketProductIds = array_filter(array_map(function ($lineItem) {
            if ($lineItem->isGood()) {
                return $lineItem->getId();
            }
        }, $event->getPage()->getCart()->getLineItems()->getElements()));


        $cartBundleArticles = $this->bundleService->getCartBundleArticles($basketProductIds, $event->getSalesChannelContext());


        $event->getPage()->addExtension("Recommendy", new ArrayEntity([
            'bundleArticles' => $cartBundleArticles
        ]));
    }

    /**
     * @param CheckoutFinishPageLoadedEvent $event
     */
    public function onCheckoutFinishPageLoadedEvent(CheckoutFinishPageLoadedEvent $event)
    {

        if ($event->getRequest()->hasSession()) {
            $this->sessionId = $event->getRequest()->getSession()->get('sessionId');
        } else {
            return;
        }

        $productsInfo = $this->getProductInfoForTracking($event->getPage()->getOrder()->getId());

        foreach ($productsInfo as $productInfo) {

            try {
                $this->trackingService->handleTracking(new ActionStruct([
                    'actionId' => TrackingEntity::ACTION_ITEM_PURCHASED,
                    'productId' => $productInfo['id'],
                    'price' => $productInfo['total_price'],
                    'sessionId' => $this->sessionId,
                    'identifier' => $productInfo['identifier']
                ]), $event->getContext());
            } catch (\Exception $e) {
                $this->logger->error("onCheckoutFinishPageLoadedEvent: {$e->getMessage()}");
            }
        }
    }

    /**
     * @param PageLoadedEvent $event
     */
    public function onNavigationPageLoadedEvent(PageLoadedEvent $event)
    {
        try {
            $configStruct = $this->configService->getConfigStruct(
                $event->getSalesChannelContext()->getSalesChannel()->getId()
            );
        } catch (\Exception $e) {
        }
    }

    /**
     * @param BeforeLineItemAddedEvent $event
     */
    public function onBeforeLineItemAddedEvent(BeforeLineItemAddedEvent $event)
    {
        if (!$this->isTrackingEnabled($event->getSalesChannelContext()->getSalesChannelId())) {
            return;
        }

        $request = $this->requestStack->getCurrentRequest();
        if ($request->hasSession()) {
            $this->sessionId = $request->getSession()->get('sessionId');
        } else {
            return;
        }

        if($event->getLineItem()->getType() !== 'product'){
            return;
        }

        $criteria = new Criteria([$event->getLineItem()->getId()]);
        // var EntitySearchResult
        $product = $this->productRepository->search($criteria, $event->getSalesChannelContext())->first();
        if (!$product) {
            return;
        }

        $pon = $this->getIdentifierForAddToBasket($product->getId());

        if($pon) {
            try {
                $this->trackingService->handleTracking(new ActionStruct([
                    'actionId' => TrackingEntity::ACTION_BASKET,
                    'productId' => $product->getId(),
                    'price' => $pon['price'],
                    'sessionId' => $this->sessionId,
                    'identifier' => $pon['pon']
                ]), $event->getContext());
            } catch (\Exception $e) {
                $this->logger->error("onBeforeLineItemAddedEvent: {$e->getMessage()}");
            }
        }
    }

    /**
     * @param ProductPageLoadedEvent $event
     */
    public function onProductPageLoadedEvent(ProductPageLoadedEvent $event)
    {

        $product = $event->getPage()->getProduct();
        $salesChannelContext = $event->getSalesChannelContext();
        $salesChannelContextId = $salesChannelContext->getSalesChannelId();

        // TRACKING
        if ($this->isTrackingEnabled($salesChannelContextId, TrackingEntity::ACTION_VISIT_DETAIL)) {
            $this->sessionId = $event->getRequest()->getSession()->get('sessionId');
            try {
                $this->trackingService->handleTracking(new ActionStruct([
                    'actionId' => TrackingEntity::ACTION_VISIT_DETAIL,
                    'productId' => $product->getId(),
                    'price' => 0,
                    'sessionId' => $this->sessionId,
                ]), $event->getContext());
            } catch (\Exception $e) {
                $this->logger->error($e->getMessage());
            }
        }

        $similarArticles = $this->similarityService->getSimilarArticles($product, $salesChannelContext);
        $bundleArticles = $this->bundleService->getBundleArticles($product, $salesChannelContext);

        $product->addExtension("Recommendy", new ArrayEntity([
            'similarArticles' => $similarArticles,
            'bundleArticles' => $bundleArticles
        ]));
    }

    /**
     * @param CustomerBeforeLoginEvent $event
     */
    public function onCustomerBeforeLoginEvent(CustomerBeforeLoginEvent $event)
    {
        $request = $this->requestStack->getCurrentRequest();
        $this->oldSessionId = $request->getSession()->getId('sessionId');
    }


    /**
     * @param GuestCustomerRegisterEvent $event
     */
    public function onGuestCustomerRegisterEvent(GuestCustomerRegisterEvent $event)
    {
        $request = $this->requestStack->getCurrentRequest();
        $this->oldSessionId = $request->getSession()->getId('sessionId');
    }


    /**
     * @param CustomerRegisterEvent $event
     */
    public function onCustomerRegisterEvent(CustomerRegisterEvent $event)
    {
        $request = $this->requestStack->getCurrentRequest();
        $this->oldSessionId = $request->getSession()->getId('sessionId');
    }

    /**
     * @param CustomerLoginEvent $event
     */
    public function onCustomerLoginEvent(CustomerLoginEvent $event)
    {
        $request = $this->requestStack->getCurrentRequest();
        $oldSessionId = $this->oldSessionId;
        $newSessionId = $request->getSession()->getId('sessionId');
        if($oldSessionId && $newSessionId){
            $builder = $this->connection->createQueryBuilder();
            try {
                $builder->update('recommendy_tracking', 'rt')
                    ->set('rt.sessionId', ':newSessionId')
                    ->where('rt.sessionId = :oldSessionId')
                    ->setParameter('newSessionId', $newSessionId)
                    ->setParameter('oldSessionId', $oldSessionId)
                    ->execute();
            } catch (DbalException $e) {
                $this->logger->error("onCustomerLoginEvent: {$e->getMessage()}");
            }
        }
    }

    /**
     * @param string $productId
     * @return string|null
     */
    private function getIdentifierForAddToBasket(string $productId): ?array
    {
        $includedActions = [
            TrackingEntity::ACTION_LIVE_RECOMMENDATION,
            TrackingEntity::ACTION_SIMILARITY,
            TrackingEntity::ACTION_BUNDLE,
            TrackingEntity::ACTION_CHECKOUT,
            TrackingEntity::ACTION_OFFCANVAS
        ];
        $builder = $this->connection->createQueryBuilder();
        $builder->select(['pon','price'])
            ->from('recommendy_tracking')
            ->andWhere('pon in (select sonIdentifier.identifier from recommendy_identifier sonIdentifier where sonIdentifier.pon = :productid)')
            ->andWhere('sessionId = :sessionId')
            ->andWhere('action in (:actions)')
            ->setParameter('productid', strtoupper($productId))
            ->setParameter('sessionId', $this->sessionId)
            ->setParameter('actions', $includedActions,Connection::PARAM_STR_ARRAY)
            ->setMaxResults(1);

        try {
            $identifier = $builder->execute()->fetch(\PDO::FETCH_ASSOC);
        } catch (DbalException $e) {
            $this->logger->error($e->getMessage());
        }

        return !empty($identifier) ? $identifier : null;
    }

    /**
     * @param string $orderId
     * @return array
     */
    private function getProductInfoForTracking(string $orderId): array
    {
        $builder = $this->connection->createQueryBuilder();
        $builder->select('ri.identifier', 'sod.total_price', 'prod.id')
            ->from('order_line_item', 'sod')
            ->innerJoin('sod', 'product', 'prod', 'sod.product_id = prod.id')
            ->innerJoin('prod', 'recommendy_identifier', 'ri', 'prod.id = unhex(ri.pon)')
            ->innerJoin('ri', 'recommendy_tracking', 'rt', 'ri.identifier = rt.pon')
            ->andWhere('rt.sessionId = :sessionId')
            ->andWhere('rt.action in (:actions)')
            ->andWhere('sod.order_id = :orderId')
            ->groupBy('ri.identifier', 'sod.total_price', 'sod.quantity')
            ->setParameter('orderId', Uuid::fromHexToBytes($orderId))
            ->setParameter('sessionId', $this->sessionId)
            ->setParameter('actions', [
                TrackingEntity::ACTION_BASKET,
                TrackingEntity::ACTION_BUY_ALL_BUNDLE,
                TrackingEntity::ACTION_BUY_SINGLE_BUNDLE
            ], Connection::PARAM_STR_ARRAY)
        ;


        try {
            return $builder->execute()->fetchAll(\PDO::FETCH_ASSOC);
        } catch (DbalException $e) {
            $this->logger->error($e->getMessage());
        }
        return [];
    }

    /**
     * @param string $salesChannelId
     * @param int|null $actionId
     * @return bool
     */
    private function isTrackingEnabled(string $salesChannelId, int $actionId = null): bool
    {
        if (empty($actionId)) {
            return true;
        }

        $configStruct = $this->configService->getConfigStruct($salesChannelId);
        return $configStruct->isEnableSessionTracking();
    }
}
