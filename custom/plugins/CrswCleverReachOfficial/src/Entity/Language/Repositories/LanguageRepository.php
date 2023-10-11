<?php

namespace Crsw\CleverReachOfficial\Entity\Language\Repositories;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\Language\LanguageCollection;
use Shopware\Core\System\Language\LanguageEntity;

/**
 * Class LanguageRepository
 *
 * @package Crsw\CleverReachOfficial\Entity\Language\Repositories
 */
class LanguageRepository
{
    /**
     * @var EntityRepositoryInterface
     */
    private $baseRepository;

    /**
     * LanguageRepository constructor.
     *
     * @param EntityRepositoryInterface $baseRepository
     */
    public function __construct(EntityRepositoryInterface $baseRepository)
    {
        $this->baseRepository = $baseRepository;
    }

    /**
     * Gets languages.
     *
     * @param Context $context
     *
     * @return LanguageCollection
     */
    public function getLanguages(Context $context): LanguageCollection
    {
        $criteria = new Criteria();

        $criteria->addAssociations([
            'translationCode',
        ]);
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->baseRepository->search($criteria, $context)->getEntities();
    }

    /**
     * Get language by name.
     *
     * @param string $name
     * @param Context $context
     *
     * @return LanguageEntity | null
     */
    public function getLanguageByName(string $name, Context $context): ?LanguageEntity
    {
        $criteria = new Criteria();

        if (!$name) {
            return $this->baseRepository->search($criteria, $context)->first();
        }

        $criteria->addFilter(new EqualsFilter('name', $name));

        return $this->baseRepository->search($criteria, $context)->first();
    }
}