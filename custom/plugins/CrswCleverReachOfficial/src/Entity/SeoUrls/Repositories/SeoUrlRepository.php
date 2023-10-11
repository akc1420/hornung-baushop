<?php

namespace Crsw\CleverReachOfficial\Entity\SeoUrls\Repositories;

use Shopware\Core\Content\Seo\SeoUrl\SeoUrlEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\ContainsFilter;

/**
 * Class SeoUrlRepository
 *
 * @package Crsw\CleverReachOfficial\Entity\SeoUrls\Repositories
 */
class SeoUrlRepository
{
    /**
     * @var EntityRepositoryInterface
     */
    private $baseRepository;

    /**
     * SeoUrlRepository constructor.
     *
     * @param EntityRepositoryInterface $baseRepository
     */
    public function __construct(EntityRepositoryInterface $baseRepository)
    {
        $this->baseRepository = $baseRepository;
    }

    /**
     * @param string $pathInfo
     * @param Context $context
     *
     * @return SeoUrlEntity | null
     */
    public function getProductSeoUrl(string $pathInfo, Context $context): ?SeoUrlEntity
    {
        $criteria = new Criteria();
        $criteria->addFilter(new ContainsFilter('pathInfo', $pathInfo));

        return $this->baseRepository->search($criteria, $context)->first();
    }
}
