<?php

namespace Recommendy\Services;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DbalException;
use Psr\Log\LoggerInterface;
use Recommendy\Core\Content\Tracking\TrackingEntity;
use Recommendy\Services\Interfaces\ConfigServiceInterface;
use Recommendy\Services\Interfaces\BundleServiceInterface;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Content\Product\SalesChannel\Detail\AbstractProductDetailRoute;
use Shopware\Core\Content\Product\SalesChannel\Detail\ProductConfiguratorLoader;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Routing\Exception\MissingRequestParameterException;
use Shopware\Core\Framework\Struct\ArrayEntity;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\Entity\SalesChannelRepository;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Event\SwitchBuyBoxVariantEvent;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

class BundleService implements BundleServiceInterface
{
    /** @var Connection */
    private $connection;
    /** @var ConfigServiceInterface */
    private $configService;
    /** @var CartService */
    private $cartService;
    /** @var RequestStack */
    private $requestStack;
    /** @var SalesChannelRepository */
    private $productRepository;
    /** @var LoggerInterface */
    private $logger;
    /**
     * @var ProductConfiguratorLoader
     */
    private $configuratorLoader;
    /**
     * @param Connection $connection
     * @param ConfigServiceInterface $configService
     * @param CartService $cartService
     * @param RequestStack $requestStack
     * @param SalesChannelRepository $productRepository
     * @param LoggerInterface $logger
     * @param ProductConfiguratorLoader $configuratorLoader
     */
    public function __construct(
        Connection                      $connection,
        ConfigServiceInterface          $configService,
        CartService                     $cartService,
        RequestStack                    $requestStack,
        SalesChannelRepository $productRepository,
        LoggerInterface                 $logger,
        ProductConfiguratorLoader $configuratorLoader
    )
    {
        $this->connection = $connection;
        $this->configService = $configService;
        $this->cartService = $cartService;
        $this->requestStack = $requestStack;
        $this->productRepository = $productRepository;
        $this->logger = $logger;
        $this->configuratorLoader = $configuratorLoader;
    }

    /**
     * @param array $basketProductIds
     * @param SalesChannelContext $context
     * @return array
     */
    public function getCartBundleArticles(array $basketProductIds, SalesChannelContext $context): array
    {

        $bundleArticleIds = $this->getCartBundleArticleIds($basketProductIds, $context);


        if (empty($bundleArticleIds)) {
            return [];
        }

        return $this->getArticlesByIds($bundleArticleIds, $context);
    }

    /**
     * @param ProductEntity $product
     * @param SalesChannelContext $context
     * @return array
     */
    public function getBundleArticles(ProductEntity $product, SalesChannelContext $context): array
    {
        $bundleArticleIds = $this->getBundleArticleIds($product, $context);

        if (empty($bundleArticleIds)) {
            return [];
        }

        //array_unshift($bundleArticleIds, $product->getId());

        $bundleArticles = $this->getArticlesByIds($bundleArticleIds, $context);


        return count($bundleArticles) > 0 ? $bundleArticles : [];
    }

    /**
     * @param SalesChannelContext $context
     * @return array
     */
    private function getBasketProductIds(SalesChannelContext $context): array
    {
        $basket = $this->cartService->getCart($context->getToken(), $context)->getLineItems()->getElements();
        return array_filter(array_map(function ($lineItem) {
            if ($lineItem->isGood()) {
                return $lineItem->getId();
            }
        }, $basket));
    }

    /**
     * @param ProductEntity $product
     * @param SalesChannelContext $context
     * @return array|null
     */
    private function getBundleArticleIds(ProductEntity $product, SalesChannelContext $context): ?array
    {
        $salesChanelId = $context->getSalesChannelId();
        $configStruct = $this->configService->getConfigStruct($salesChanelId);

        $basketProductIds = $this->getBasketProductIds($context);

        $builder = $this->connection->createQueryBuilder();
        $builder->select(['product.id'])
            ->from('recommendy_identifier', 'ponIdentifier')
            ->innerJoin('ponIdentifier', 'recommendy_bundle_matrix', 'bundleMatrix', 'bundleMatrix.pon = ponIdentifier.identifier and bundleMatrix.shop = :shopId')
            ->innerJoin('sonIdentifier', 'product', 'product', 'unhex(sonIdentifier.pon) = product.id')
            ->innerJoin('bundleMatrix', 'recommendy_identifier', 'sonIdentifier', 'sonIdentifier.identifier = bundleMatrix.son')
            ->andWhere('ponIdentifier.pon = :productid')
            ->andWhere('(product.child_count = 0 OR product.parent_id IS NOT NULL)')
            ->setParameter('productid', strtoupper($product->getId()))
            ->setParameter('shopId', $salesChanelId);

        if ($configStruct->isConsiderInstock()) {
            $builder->andWhere('product.available = 1');
        }

        if (!empty($basketProductIds)) {
            $builder->andWhere('sonIdentifier.identifier not in (select exceptIdentifier.identifier from recommendy_identifier exceptIdentifier where exceptIdentifier.pon in (:productids))')
                ->setParameter('productids', $basketProductIds, Connection::PARAM_STR_ARRAY);
        }

        $builder->setMaxResults($configStruct->getDetailRecommendationAmountMax())
            ->orderBy('bundleMatrix.similarity', 'DESC')
            ->addGroupBy('sonIdentifier.identifier');


        $bundleArticleIds = [];
        try {
            $bundleArticleIds = $builder->execute()->fetchAll(\PDO::FETCH_COLUMN);
        } catch (DbalException $e) {
            $this->logger->error("BundleService - getBundleArticleIds: {$e->getMessage()}");
        }

        return array_map(function ($articleId) {
            return Uuid::fromBytesToHex($articleId);
        }, $bundleArticleIds);
    }

    /**
     * @param array $basketProductIds
     * @param SalesChannelContext $context
     * @return array|null
     */
    private function getCartBundleArticleIds(array $basketProductIds, SalesChannelContext $context): ?array
    {
        $salesChanelId = $context->getSalesChannelId();
        $configStruct = $this->configService->getConfigStruct($salesChanelId);

        $builder = $this->connection->createQueryBuilder();
        $builder->select(['product.id'])
            ->from('recommendy_identifier', 'ponIdentifier')
            ->innerJoin('ponIdentifier', 'recommendy_bundle_matrix', 'bundleMatrix', 'bundleMatrix.pon = ponIdentifier.identifier and bundleMatrix.shop = :shopId')
            ->innerJoin('sonIdentifier', 'product', 'product', 'unhex(sonIdentifier.pon) = product.id')
            ->innerJoin('bundleMatrix', 'recommendy_identifier', 'sonIdentifier', 'sonIdentifier.identifier = bundleMatrix.son')
            ->andWhere('ponIdentifier.pon in (:productIds)')
            ->setParameter('productIds', $basketProductIds, Connection::PARAM_STR_ARRAY)
            ->andWhere('sonIdentifier.identifier not in (select exceptIdentifier.identifier from recommendy_identifier exceptIdentifier where exceptIdentifier.pon in (:productIds))')
            ->setParameter('productIds', $basketProductIds, Connection::PARAM_STR_ARRAY)
            ->setParameter('shopId', $salesChanelId);

        if ($configStruct->isConsiderInstock()) {
            $builder->andWhere('product.available = 1');
        }

        $builder->setMaxResults($configStruct->getBasketRecommendationAmountMax())
            ->orderBy('count(*)', 'DESC')
            ->addGroupBy('sonIdentifier.identifier');

        $bundleArticleIds = [];
        try {
            $bundleArticleIds = $builder->execute()->fetchAll(\PDO::FETCH_COLUMN);
        } catch (DbalException $e) {
            $this->logger->error("BundleService - getCartBundleArticleIds: {$e->getMessage()}");
        }

        return array_map(function ($articleId) {
            return Uuid::fromBytesToHex($articleId);
        }, $bundleArticleIds);
    }

    /**
     * @param array $articleIds
     * @param $context
     * @return array
     */
    private function getArticlesByIds(array $articleIds, $context): array
    {
        $request = $this->requestStack->getCurrentRequest();
        $criteria = new Criteria($articleIds);

        $criteria->addAssociation('options.group');
        $criteria->addAssociation('properties.group');
        $criteria->addAssociation('options');
        $criteria->addAssociation('configuration');
        $criteria->addAssociation('configuratorSettings');
        $criteria->addAssociation('configuratorSettings.option');

        $products = $this->productRepository->search($criteria, $context)->getElements();

        foreach ($products as $product){

            $configurator = $this->configuratorLoader->load($product, $context);
            $product->addExtension("rConfiguratorSettings", new ArrayEntity([
                'configuratorSettings' => $configurator
            ]));
    }



        if ($request->attributes instanceof ParameterBag
            && !empty($request->attributes->get('_route'))
        ) {
            switch ($request->attributes->get('_route')) {
                case 'frontend.detail.page':
                    $actionId = TrackingEntity::ACTION_BUNDLE;
                    break;
                case 'frontend.checkout.confirm.page':
                    $actionId = TrackingEntity::ACTION_CHECKOUT;
                    break;
                default:
                    $actionId = TrackingEntity::ACTION_UNKNOWN;
            }

            foreach ($products as $product) {
                $product->addExtension("Recommendy", new ArrayEntity([
                    'actionId' => $actionId
                ]));
            }
        }

        return $products;
    }

}
