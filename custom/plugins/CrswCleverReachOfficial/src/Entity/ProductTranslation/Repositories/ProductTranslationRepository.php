<?php


namespace Crsw\CleverReachOfficial\Entity\ProductTranslation\Repositories;

use Shopware\Core\Content\Product\Aggregate\ProductTranslation\ProductTranslationCollection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\ContainsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;

/**
 * Class ProductTranslationRepository
 *
 * @package Crsw\CleverReachOfficial\Entity\ProductTranslation\Repositories
 */
class ProductTranslationRepository
{
    /**
     * @var EntityRepositoryInterface
     */
    private $baseRepository;

    /**
     * ProductTranslationRepository constructor.
     *
     * @param EntityRepositoryInterface $baseRepository
     */
    public function __construct(EntityRepositoryInterface $baseRepository)
    {
        $this->baseRepository = $baseRepository;
    }

    /**
     * @param string $name
     * @param string $languageId
     * @param Context $context
     *
     * @return ProductTranslationCollection
     */
    public function getProductsTranslationsByName(
        string $name,
        string $languageId,
        Context $context
    ): ProductTranslationCollection {
        $criteria = new Criteria();
        $criteria->addFilter(new ContainsFilter('name', $name))
            ->addFilter(new EqualsFilter('languageId', $languageId));

        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->baseRepository->search($criteria, $context)->getEntities();
    }
}
