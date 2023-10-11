<?php

namespace Crsw\CleverReachOfficial\Entity\Product\Repositories;

use Shopware\Core\Content\Product\ProductCollection;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;

/**
 * Class ProductRepository
 *
 * @package Crsw\CleverReachOfficial\Entity\Product\Repositories
 */
class ProductRepository
{
    /**
     * @var EntityRepositoryInterface
     */
    private $baseRepository;

    /**
     * ProductRepository constructor.
     *
     * @param EntityRepositoryInterface $baseRepository
     */
    public function __construct(EntityRepositoryInterface $baseRepository)
    {
        $this->baseRepository = $baseRepository;
    }

    /**
     * @param string $searchTerm
     * @param Context $context
     *
     * @return ProductCollection
     */
    public function search(string $searchTerm, Context $context): ProductCollection
    {
        $criteria = new Criteria();

        $criteria->addFilter(new EqualsFilter('productNumber', $searchTerm));
        $criteria->addAssociations([
                'translations',
                'media',
                'media.media.mediaFolder',
                'media.media.mediaFolder.configuration',
                'product.parent',
                'properties',
                'categories',
                'options',
                'prices'
            ]);
        $criteria->setLimit(100);

        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->baseRepository->search($criteria, $context)->getEntities();
    }

    /**
     * Gets products for order sync.
     *
     * @param array $productIds
     * @param Context $context
     *
     * @return ProductCollection
     */
    public function getProductsForSync(array $productIds, Context $context): ProductCollection
    {
        $criteria = new Criteria($productIds);
        $criteria->addAssociations(['manufacturer', 'categories', 'properties.group']);
        $criteria->addAssociation('options')->addAssociation('options.group');

        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->baseRepository->search($criteria, $context)->getEntities();
    }

    /**
     * @param array $productIds
     * @param Context $context
     *
     * @return ProductCollection
     */
    public function getProductsById(array $productIds, Context $context): ProductCollection
    {
        $criteria = new Criteria($productIds);
        $criteria->addAssociations([
            'translations',
            'media',
            'media.media.mediaFolder',
            'media.media.mediaFolder.configuration',
            'product.parent',
            'properties',
            'categories',
            'options',
            'prices'
        ]);

        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->baseRepository->search($criteria, $context)->getEntities();
    }

    /**
     * @param string $productId
     * @param Context $context
     *
     * @return ProductEntity|null
     */
    public function getProductById(string $productId, Context $context): ?ProductEntity
    {
        $criteria = new Criteria([$productId]);
        $criteria->addAssociations([
            'translations',
            'media',
            'media.media.mediaFolder',
            'media.media.mediaFolder.configuration',
            'properties',
            'categories',
            'options'
        ]);

        return $this->baseRepository->search($criteria, $context)->first();
    }
}
