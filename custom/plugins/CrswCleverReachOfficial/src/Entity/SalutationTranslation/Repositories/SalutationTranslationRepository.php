<?php

namespace Crsw\CleverReachOfficial\Entity\SalutationTranslation\Repositories;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\Salutation\Aggregate\SalutationTranslation\SalutationTranslationEntity;

/**
 * Class SalutationTranslationRepository
 *
 * @package Crsw\CleverReachOfficial\Entity\SalutationTranslation\Repositories
 */
class SalutationTranslationRepository
{
    /**
     * @var EntityRepositoryInterface
     */
    private $baseRepository;

    /**
     * SalutationTranslationRepository constructor.
     *
     * @param EntityRepositoryInterface $baseRepository
     */
    public function __construct(EntityRepositoryInterface $baseRepository)
    {
        $this->baseRepository = $baseRepository;
    }

    /**
     * Gets salutation translation by display name.
     *
     * @param string $displayName
     * @param Context $context
     *
     * @return SalutationTranslationEntity | null
     */
    public function getSalutationByDisplayName(string $displayName, Context $context): ?SalutationTranslationEntity
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('displayName', $displayName));

        return $this->baseRepository->search($criteria, $context)->first() ?: null;
    }
}