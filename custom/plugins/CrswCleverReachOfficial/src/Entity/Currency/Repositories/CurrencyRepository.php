<?php

namespace Crsw\CleverReachOfficial\Entity\Currency\Repositories;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\Currency\CurrencyEntity;

/**
 * Class CurrencyRepository
 *
 * @package Crsw\CleverReachOfficial\Entity\Currency\Repositories
 */
class CurrencyRepository
{
    /**
     * @var EntityRepositoryInterface
     */
    private $baseRepository;

    /**
     * CurrencyRepository constructor.
     *
     * @param EntityRepositoryInterface $baseRepository
     */
    public function __construct(EntityRepositoryInterface $baseRepository)
    {
        $this->baseRepository = $baseRepository;
    }

    /**
     * Gets currency by id.
     *
     * @param string $currencyId
     * @param Context $context
     *
     * @return CurrencyEntity|null
     */
    public function getCurrencyById(string $currencyId, Context $context): ?CurrencyEntity
    {
        $criteria = new Criteria([$currencyId]);

        return $this->baseRepository->search($criteria, $context)->first();
    }
}
