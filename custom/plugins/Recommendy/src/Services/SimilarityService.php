<?php

namespace Recommendy\Services;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DbalException;
use Psr\Log\LoggerInterface;
use Recommendy\Core\Content\Tracking\TrackingEntity;
use Recommendy\Services\Interfaces\ConfigServiceInterface;
use Recommendy\Services\Interfaces\SimilarityServiceInterface;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Struct\ArrayEntity;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\Entity\SalesChannelRepository;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class SimilarityService implements SimilarityServiceInterface
{
    /** @var Connection */
    private $connection;
    /** @var ConfigServiceInterface */
    private $configService;
    /** @var CartService */
    private $cartService;
    /** @var SalesChannelRepository */
    private $productRepository;
    /** @var LoggerInterface */
    private $logger;

    /**
     * @param Connection $connection
     * @param ConfigServiceInterface $configService
     * @param CartService $cartService
     * @param SalesChannelRepository $productRepository
     * @param LoggerInterface $logger
     */
    public function __construct(
        Connection                      $connection,
        ConfigServiceInterface          $configService,
        CartService                     $cartService,
        SalesChannelRepository $productRepository,
        LoggerInterface                 $logger
    )
    {
        $this->connection = $connection;
        $this->configService = $configService;
        $this->cartService = $cartService;
        $this->productRepository = $productRepository;
        $this->logger = $logger;
    }

    /**
     * @param ProductEntity $product
     * @param SalesChannelContext $context
     * @return array
     */
    public function getSimilarArticles(ProductEntity $product, SalesChannelContext $context): array
    {
        $similarArticleIds = $this->getSimilarArticleIds($product, $context);

        if (empty($similarArticleIds)) {
            return [];
        }

        $criteria = new Criteria($similarArticleIds);
        $products = $this->productRepository->search($criteria, $context)->getElements();
        foreach ($products as $product) {
            $product->addExtension("Recommendy", new ArrayEntity([
                'actionId' => TrackingEntity::ACTION_SIMILARITY
            ]));
        }

        return $products;
    }

    /**
     * @param string $productId
     * @return bool
     */
    public function similarProductsAvailable(string $productId, SalesChannelContext $context) : bool
    {

        if (empty($productId)) {
            return false;
        }
       // $salesChanelId = $context->getSalesChannelId();

        $builder = $this->connection->createQueryBuilder();
        $builder->select('1')
            ->from('recommendy_identifier', 'ponIdentifier')
           // ->innerJoin('ponIdentifier','recommendy_article_similarity','simMatrix','simMatrix.pon = ponIdentifier.identifier and simMatrix.shop = :shopId')
            ->andWhere('ponIdentifier.pon = :productId')
            ->setParameter('productId', strtoupper($productId));
           // ->setParameter('shopId', strtoupper($salesChanelId));

        try {
            $similarArticleIds = $builder->execute()->fetchAll(\PDO::FETCH_COLUMN);

            return !empty($similarArticleIds);
        } catch (DbalException $e) {
            $this->logger->error("SimilarityService - getSimilarArticleIds: {$e->getMessage()}");
        }

        return false;
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
                return strtoupper($lineItem->getId());
            }
        }, $basket));
    }

    /**
     * @param ProductEntity $product
     * @param SalesChannelContext $context
     * @return array|null
     */
    private function getSimilarArticleIds(ProductEntity $product, SalesChannelContext $context): ?array
    {
        $salesChanelId = $context->getSalesChannelId();
        $configStruct = $this->configService->getConfigStruct($salesChanelId);

        $basketProductIds = $this->getBasketProductIds($context);

        $builder = $this->connection->createQueryBuilder();
        $builder->select(['product.id'])
            ->from('recommendy_identifier', 'ponIdentifier')
            ->innerJoin('ponIdentifier','recommendy_article_similarity','simMatrix','simMatrix.pon = ponIdentifier.identifier and simMatrix.shop = :shopId')

            ->innerJoin('simMatrix','recommendy_identifier','sonIdentifier', 'sonIdentifier.identifier = simMatrix.son')
            ->innerJoin('sonIdentifier','product','product', 'unhex(sonIdentifier.pon) = product.id')
            ->andWhere('ponIdentifier.pon = :productid')
            ->setParameter('productid', strtoupper($product->getId()))
            ->setParameter('shopId', strtoupper($salesChanelId));

        if($configStruct->isConsiderInstock()){
            $builder->andWhere('product.available = 1');
        }

        if(!empty($basketProductIds)){
            $builder->andWhere('sonIdentifier.identifier not in (select exceptIdentifier.identifier from recommendy_identifier exceptIdentifier where exceptIdentifier.pon in (:productIds))')
                ->setParameter('productIds', $basketProductIds,Connection::PARAM_STR_ARRAY);
        }

        $builder->setMaxResults($configStruct->getSimilarProductAmount())
            ->orderBy('simMatrix.similarity','DESC')
            ->addGroupBy('sonIdentifier.identifier');

        $similarArticleIds = [];

        try {
            $similarArticleIds = $builder->execute()->fetchAll(\PDO::FETCH_COLUMN);
        } catch (DbalException $e) {
            $this->logger->error("SimilarityService - getSimilarArticleIds: {$e->getMessage()}");
        }

        return array_map(function ($articleId) {
            return Uuid::fromBytesToHex($articleId);
        }, $similarArticleIds);
    }
}
