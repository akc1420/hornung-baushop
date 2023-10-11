<?php

namespace Crsw\CleverReachOfficial\Entity\CustomerGroup\Repositories;

use Shopware\Core\Checkout\Customer\Aggregate\CustomerGroup\CustomerGroupEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;

/**
 * Class CustomerGroupRepository
 *
 * @package Crsw\CleverReachOfficial\Entity\CustomerGroup\Repositories
 */
class CustomerGroupRepository
{
    /**
     * @var EntityRepositoryInterface
     */
    private $baseRepository;

    /**
     * CustomerGroupRepository constructor.
     *
     * @param EntityRepositoryInterface $baseRepository
     */
    public function __construct(EntityRepositoryInterface $baseRepository)
    {
        $this->baseRepository = $baseRepository;
    }

    /**
     * Gets customer groups.
     *
     * @param Context $context
     *
     * @return EntityCollection
     */
    public function getCustomerGroups(Context $context): EntityCollection
    {
        $criteria = new Criteria();

        return $this->baseRepository->search($criteria, $context)->getEntities();
    }

    /**
     * Get customer group by id.
     *
     * @param string $id
     * @param Context $context
     *
     * @return CustomerGroupEntity|null
     */
    public function getCustomerGroupById(string $id, Context $context): ?CustomerGroupEntity
    {
        $criteria = new Criteria([$id]);

        return $this->baseRepository->search($criteria, $context)->first();
    }
}