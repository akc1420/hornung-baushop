<?php declare(strict_types=1);

namespace Recommendy\Controller\Storefront;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DbalException;
use Exception;
use Psr\Log\LoggerInterface;
use Recommendy\Components\Struct\ActionStruct;
use Recommendy\Components\Struct\ConfigStruct;
use Recommendy\Core\Content\Tracking\TrackingEntity;
use Recommendy\Services\Interfaces\ConfigServiceInterface;
use Recommendy\Services\Interfaces\SimilarityServiceInterface;
use Recommendy\Services\Interfaces\TrackingServiceInterface;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Content\Product\Events\ProductSearchCriteriaEvent;
use Shopware\Core\Content\Product\Exception\ProductNotFoundException;
use Shopware\Core\Content\Product\ProductEvents;
use Shopware\Core\Content\Product\SalesChannel\Detail\ProductConfiguratorLoader;
use Shopware\Core\Framework\DataAbstractionLayer\DefinitionInstanceRegistry;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\RequestCriteriaBuilder;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\Entity\SalesChannelRepository;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Shopware\Storefront\Framework\Routing\RequestTransformer;
use Shopware\Core\Content\Product\SalesChannel\FindVariant\AbstractFindProductVariantRoute;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\EventListener\AbstractSessionListener;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Response;
use Shopware\Core\Framework\Struct\ArrayEntity;

#[Route(defaults: ['_routeScope' => ['storefront']])]
class RecommendyController extends StorefrontController
{
    /** @var SimilarityServiceInterface */
    private $similarityService;
    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var ConfigServiceInterface
     */
    private $configService;

    /**
     * @var TrackingServiceInterface
     */
    private $trackingService;

    /**
     * @var CartService
     */
    private $cartService;

    /**
     * @var EntityRepository
     */
    private $identifierRepository;

    /**
     * @var SalesChannelRepository
     */
    private $salesChannelProductRepository;

    /**
     * @var DefinitionInstanceRegistry
     */
    private $registry;

    /**
     * @var RequestCriteriaBuilder
     */
    private $criteriaBuilder;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var EntityRepository
     */
    private $recommendyTrackingRepository;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var AbstractFindProductVariantRoute
     */
    private $combinationFinder;

    /**
     * @var ProductConfiguratorLoader
     */
    private $configuratorLoader;

    /**
     * @var EntityRepository
     */
    private $categoryRepository;



    /**
     * @param SimilarityServiceInterface $similarityService
     * @param Connection $connection
     * @param ConfigServiceInterface $configService
     * @param TrackingServiceInterface $trackingService
     * @param CartService $cartService
     * @param EntityRepository $identifierRepository
     * @param SalesChannelRepository $salesChannelProductRepository
     * @param DefinitionInstanceRegistry $registry
     * @param RequestCriteriaBuilder $criteriaBuilder
     * @param EventDispatcherInterface $eventDispatcher
     * @param EntityRepository $recommendyTrackingRepository
     * @param LoggerInterface $logger
     * @param AbstractFindProductVariantRoute $combinationFinder
     * @param ProductConfiguratorLoader $configuratorLoader
     */
    public function __construct(
        SimilarityServiceInterface      $similarityService,
        Connection $connection,
        ConfigServiceInterface $configService,
        TrackingServiceInterface $trackingService,
        CartService $cartService,
        EntityRepository $identifierRepository,
        SalesChannelRepository $salesChannelProductRepository,
        DefinitionInstanceRegistry $registry,
        RequestCriteriaBuilder $criteriaBuilder,
        EventDispatcherInterface $eventDispatcher,
        EntityRepository $recommendyTrackingRepository,
        LoggerInterface $logger,
        AbstractFindProductVariantRoute $combinationFinder,
        ProductConfiguratorLoader $configuratorLoader,
        EntityRepository $categoryRepository
    )
    {
        $this->similarityService = $similarityService;
        $this->connection = $connection;
        $this->configService = $configService;
        $this->trackingService = $trackingService;
        $this->cartService = $cartService;
        $this->identifierRepository = $identifierRepository;
        $this->salesChannelProductRepository = $salesChannelProductRepository;
        $this->registry = $registry;
        $this->criteriaBuilder = $criteriaBuilder;
        $this->eventDispatcher = $eventDispatcher;
        $this->recommendyTrackingRepository = $recommendyTrackingRepository;
        $this->logger = $logger;
        $this->combinationFinder = $combinationFinder;
        $this->configuratorLoader = $configuratorLoader;
        $this->categoryRepository = $categoryRepository;
    }

    /**
     * @Route("/Recommendy/switch/{productId}", name="frontend.Recommendy.switch", methods={"GET"}, defaults={"XmlHttpRequest": true},options={"seo"="false"})
     */
    public function switch(string $productId, Request $request, SalesChannelContext $salesChannelContext): Response
    {
        $switchedOption = $request->query->has('switched') ? (string) $request->query->get('switched') : null;

        $options = (string) $request->query->get('options');

        try {
            $newOptions = json_decode($options, true, 512, \JSON_THROW_ON_ERROR);
        } catch (\JsonException $jsonException) {
            $newOptions = [];
        }

        try {
            $variantResponse = $this->combinationFinder->load(
                $productId,
                new Request(
                    [
                        'switchedGroup' => $switchedOption,
                        'options' => $newOptions ?? [],
                    ]
                ),
                $salesChannelContext
            );

            $newProductId = $variantResponse->getFoundCombination()->getVariantId();

            $criteria = (new Criteria())
                ->addAssociation('manufacturer.media')
                ->addAssociation('options.group')
                ->addAssociation('properties.group')
                ->addAssociation('mainCategories.category')
                ->addAssociation('media');
            $criteria->setIds([$newProductId]);

            $product = $this->salesChannelProductRepository
                ->search($criteria, $salesChannelContext)
                ->first();

                $configurator = $this->configuratorLoader->load($product, $salesChannelContext);
                $product->addExtension("rConfiguratorSettings", new ArrayEntity([
                    'configuratorSettings' => $configurator
                ]));

        } catch (ProductNotFoundException $productNotFoundException) {
            //nth
        }

        return $this->renderStorefront('@Storefront/storefront/recommendy/bundle/recommendy_bundle_item.html.twig', ['product' => $product]);
    }
    /**
     * @Route("/Recommendy/recommendyLike/{productId}", name="frontend.Recommendy.recommendyLike", defaults={"csrf_protected"=true, "XmlHttpRequest"=true}, options={"seo"="false"}, methods={"POST"})
     *
     * @param Request $request
     * @param SalesChannelContext $context
     * @return Response
     * @throws Exception
     */
    public function likeAction(Request $request, SalesChannelContext $context): Response
    {



        $productId = $request->get('productId');
        $categoryId = $request->get('recommendyCategoryId') ?? null;

        $price = $request->get('price') ?? null;
        $configStruct = $this->configService->getConfigStruct($context->getSalesChannelId());

        $basket = $this->cartService->getCart($context->getToken(), $context)->getLineItems()->getElements();
        $basketProductIds = array_filter(array_map(function ($lineItem) {
            if ($lineItem->isGood()) {
                return $lineItem->getId();
            }
        }, $basket));


        $recommendyAlreadyViewed = [];

        if ($request->get("alreadyViewedIds")) {
            $recommendyAlreadyViewed = explode(",", $request->get("alreadyViewedIds"));
        }

      //  die(print_r($request->get("alreadyViewedIds")));
     //   die(print_r($recommendyAlreadyViewed));
        $recommendyAlreadyViewed[] = $productId;

        if (!empty($basketProductIds)) {
            $recommendyAlreadyViewed = array_values(array_merge($recommendyAlreadyViewed, $basketProductIds));
        }
        $boxLayout = $request->get('layout');
        $displayMode = $request->get('displayMode');
        $listingColumns = $request->get('listingColumns');

        $recommendyAlreadyViewed = array_map('strtolower', $recommendyAlreadyViewed);
        $similarProductsIds = $this->getSimilarProductsIds($productId, $categoryId, $recommendyAlreadyViewed, $configStruct, $context);
        $similarProducts = $this->getSalesChannelProductsByIds($request, $similarProductsIds, $context);

        foreach ($similarProducts as $similarProduct){
            $similarProduct->addExtension("RecommendyData", new ArrayEntity([
                'available' => $this->similarityService->similarProductsAvailable($similarProduct->getId() ,$context),
                'navId' => $categoryId
            ]));
        }
        // TRACKING
        $this->trackingService->handleTracking(new ActionStruct([
            'actionId' => TrackingEntity::ACTION_LIKE_CLICK,
            'productId' => $productId,
            'price' => $price,
            'sessionId' => $this->getSessionId($request),
        ]), $context->getContext());

        return $this->renderStorefront('@Storefront/storefront/component/product/card/recommendy-products.html.twig', [
            'boxLayout' => $boxLayout,
            'displayMode' => $displayMode,
            'listingColumns' => $listingColumns,
            'searchResult' => $similarProducts
        ]);
    }

    /**
     * @Route("/Recommendy/recommendyTrack/{productId}", name="frontend.Recommendy.recommendyTrack", defaults={"csrf_protected"=false, "XmlHttpRequest"=true}, options={"seo"="false"}, methods={"POST"})
     *
     * @param Request $request
     * @param SalesChannelContext $context
     * @return JsonResponse
     * @throws Exception
     */
    public function trackAction(Request $request, SalesChannelContext $context): JsonResponse
    {
        $productId = $request->get('productId');
        $actionId = (int)$request->get('actionId');
        $price = $request->get('price');
        $configStruct = $this->configService->getConfigStruct($context->getSalesChannelId());

        // TRACKING
        $this->trackingService->handleTracking(new ActionStruct([
            'actionId' => $actionId,
            'productId' => $productId,
            'price' => $price,
            'sessionId' => $this->getSessionId($request),
        ]), $context->getContext());

        return new JsonResponse(['success' => true]);
    }

    /**
     * @param string $productId
     * @param string|null $categoryId
     * @param array $recommendyAlreadyViewed
     * @param ConfigStruct $configStruct
     * @return array
     */
    private function getSimilarProductsIds(string $productId, ?string $categoryId, array $recommendyAlreadyViewed, ConfigStruct $configStruct,SalesChannelContext $context): array
    {

        $params = [];
        $sql = <<<SQL
            SELECT `product`.id AS `prodId`, MIN(`variantIdentifier`.`pon`) AS `son`, `product`.child_count
            FROM `product`
            SQL;



        $sql .= <<<SQL
                     INNER JOIN `recommendy_identifier` AS `variantIdentifier`
                                ON unhex(`variantIdentifier`.`pon`) = `product`.`id`
                     INNER JOIN `recommendy_article_similarity` AS `ras`
                                ON `ras`.`son` = `variantIdentifier`.`identifier`
                     INNER JOIN `recommendy_identifier` AS `ponIdentifier`
                                ON `ponIdentifier`.`identifier` = `ras`.`pon`
            WHERE `product`.`parent_id` IS NULL
              AND `product`.`active` = 1
              AND (`ponIdentifier`.`pon` = :productId)
        SQL;




        if ($configStruct->isConsiderInstock()) {
            $sql .= <<<SQL
                AND `product`.`available` = 1
            SQL;
        }
        if ($recommendyAlreadyViewed) {
            $sql .= <<<SQL
                AND lower(hex(`product`.`id`)) NOT IN (:excludedNumbers)
            SQL;
            $params['excludedNumbers'] = $recommendyAlreadyViewed;
        }
        $sql .= <<<SQL
            GROUP BY `product`.`id`, `variantIdentifier`.`identifier`, `ras`.`similarity`
            ORDER BY `ras`.`similarity` DESC
            LIMIT {$configStruct->getRecommendationAmount()};
        SQL;

        $params['productId'] = $productId;
        $similarArticles = [];

        try {
            $tempSimilarArticles = $this->connection->fetchAllAssociative($sql, $params, [
                'excludedNumbers' => Connection::PARAM_STR_ARRAY
            ]);

            // generally if there are articles which are not variants (they are parents) - we
            // need to exchange the parent with one of the variants.
            $parentIds = array_map(function ($tempSimilarArticle) {
                $childCount = (int)$tempSimilarArticle['child_count'];
                if ($childCount > 0) {
                    return $tempSimilarArticle['prodId'];
                }
            }, $tempSimilarArticles);

            $sql = "SELECT `parent_id`, `id`, `product_number` FROM `product`
                            WHERE `parent_id` IN (:parent_ids) GROUP BY `parent_id`";
            $variantData = $this->connection->fetchAllAssociativeIndexed($sql, [
                'parent_ids' => array_filter($parentIds)
            ], ['parent_ids' => Connection::PARAM_STR_ARRAY]);

            foreach ($tempSimilarArticles as $similarArticle) {
                $childCount = (int)$similarArticle['child_count'];
                if ($childCount > 0) {
                    array_push($similarArticles, [
                        'prodId' => $variantData[$similarArticle['prodId']]['id'],
                        'son' => $variantData[$similarArticle['prodId']]['product_number']
                    ]);
                } else {
                    array_push($similarArticles, [
                        'prodId' => $similarArticle['prodId'],
                        'son' => $similarArticle['son']
                    ]);
                }
            }
        } catch (DbalException $e) {
            $this->logger->error("RecommendyController - getSimilarProductsIds: {$e->getMessage()}");
        }

        $similarArticleIds = array_map(function ($articleNumber) {
            return Uuid::fromBytesToHex($articleNumber['prodId']);
        }, $similarArticles);

        if (empty($similarArticleIds)) {
            return [];
        }

        return $similarArticleIds;
    }

    /**
     * @param Request $request
     * @param array $similarArticleIds
     * @param SalesChannelContext $salesChannelContext
     * @return array
     */
    private function getSalesChannelProductsByIds(Request $request, array $similarArticleIds, SalesChannelContext $salesChannelContext): array
    {
        if (empty($similarArticleIds)) {
            return [];
        }
        $criteria = new Criteria($similarArticleIds);

        $criteria = $this->criteriaBuilder->handleRequest(
            $request,
            $criteria,
            $this->registry->getByEntityName('product'),
            $salesChannelContext->getContext()
        );

        $this->eventDispatcher->dispatch(
            new ProductSearchCriteriaEvent($request, $criteria, $salesChannelContext),
            ProductEvents::PRODUCT_SEARCH_CRITERIA
        );

        $criteria->addAssociation("cover");
        $criteria->addAssociation("prices");

        return $this->salesChannelProductRepository->search($criteria, $salesChannelContext)->getElements();
    }

    /**
     * @param Request $request
     * @return string
     */
    private function getSessionId(Request $request): string
    {
        $sessionId = '';
        if ($request->getSession()->get('sessionId')) {
            $sessionId = $request->getSession()->get('sessionId');
        }
        return $sessionId;
    }
}
