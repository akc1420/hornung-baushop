<?php

namespace Crsw\CleverReachOfficial\Entity\SalesChannel\Repositories;

use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;
use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;

/**
 * Class SalesChannelRepository
 *
 * @package Crsw\CleverReachOfficial\Entity\SalesChannel\Repositories
 */
class SalesChannelRepository
{
    /**
     * @var EntityRepositoryInterface
     */
    private $baseRepository;

    /**
     * SalesChannelRepository constructor.
     *
     * @param EntityRepositoryInterface $baseRepository
     */
    public function __construct(EntityRepositoryInterface $baseRepository)
    {
        $this->baseRepository = $baseRepository;
    }

    /**
     * Gets sales channels.
     *
     * @param Context $context
     *
     * @return EntitySearchResult
     */
    public function getSalesChannels(Context $context): EntitySearchResult
    {
        $criteria = new Criteria();

        return $this->baseRepository->search($criteria, $context);
    }

    /**
     * Get sales channel by name.
     *
     * @param string $name
     * @param Context $context
     *
     * @return SalesChannelEntity|null
     */
    public function getSalesChannelByName(string $name, Context $context): ?SalesChannelEntity
    {
        $criteria = new Criteria();

        if (!$name) {
            return $this->baseRepository->search($criteria, $context)->first();
        }

        $criteria->addFilter(new EqualsFilter('name', $name));

        return $this->baseRepository->search($criteria, $context)->first();
    }

    /**
     * Gets sales channel by id.
     *
     * @param string $id
     * @param Context $context
     *
     * @return SalesChannelEntity|null
     */
    public function getSalesChannelById(string $id, Context $context): ?SalesChannelEntity
    {
        $criteria = new Criteria([$id]);
        $criteria->addAssociation('domains');

        return $this->baseRepository->search($criteria, $context)->first();
    }

    /**
     * Get active sales channels.
     *
     * @param Context $context
     *
     * @return EntitySearchResult
     */
    public function getActiveSalesChannels(Context $context): EntitySearchResult
    {
        $criteria = new Criteria();
        $criteria->addAssociations(['type']);

        $criteria->addFilter(new EqualsFilter('active', 1));
        $criteria->addFilter(new EqualsFilter('type.name', 'Storefront'));

        return $this->baseRepository->search($criteria, $context);
    }

    /**
     * @return Connection
     */
    public function getConnection(): Connection
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return ServiceRegister::getService(Connection::class);
    }
}
