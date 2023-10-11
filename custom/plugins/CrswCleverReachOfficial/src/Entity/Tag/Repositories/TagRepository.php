<?php

namespace Crsw\CleverReachOfficial\Entity\Tag\Repositories;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Uuid\Uuid;

/**
 * Class TagRepository
 *
 * @package Crsw\CleverReachOfficial\Entity\Tag\Repositories
 */
class TagRepository
{
    /**
     * @var EntityRepositoryInterface
     */
    private $baseRepository;
    /**
     * @var Connection
     */
    private $connection;

    /**
     * TagRepository constructor.
     *
     * @param EntityRepositoryInterface $baseRepository
     * @param Connection $connection
     */
    public function __construct(
        EntityRepositoryInterface $baseRepository,
        Connection $connection
    ) {
        $this->baseRepository = $baseRepository;
        $this->connection = $connection;
    }

    /**
     * Gets tags.
     *
     * @param Context $context
     *
     * @return EntityCollection
     * @throws DBALException
     */
    public function getTags(Context $context): EntityCollection
    {
        $ids = array_unique(array_merge($this->getCustomerTagIds(), $this->getNewsletterRecipientTagIds()));
        if (empty($ids)) {
            return new EntityCollection();
        }
        $criteria = new Criteria($ids);

        return $this->baseRepository->search($criteria, $context)->getEntities();
    }

    /**
     * Get tag by id.
     *
     * @param string $id
     * @param Context $context
     *
     * @return mixed|null
     */
    public function getTagById(string $id, Context $context)
    {
        $criteria = new Criteria([$id]);

        return $this->baseRepository->search($criteria, $context)->getEntities()->first();
    }

    /**
     * @return array
     *
     * @throws DBALException
     */
    private function getCustomerTagIds(): array
    {
        /** @noinspection SqlDialectInspection */
        /** @noinspection SqlNoDataSourceInspection */
        $sql = 'SELECT DISTINCT `tag_id` FROM `customer_tag`';
        $results = $this->connection->executeQuery($sql)->fetchAll();
        $ids = [];

        foreach ($results as $item) {
            $ids[] = Uuid::fromBytesToHex($item['tag_id']);
        }

        return $ids;
    }

    /**
     * @return array
     * @throws \Doctrine\DBAL\Exception
     */
    private function getNewsletterRecipientTagIds(): array
    {
        /** @noinspection SqlDialectInspection */
        /** @noinspection SqlNoDataSourceInspection */
        $sql = 'SELECT DISTINCT `tag_id` FROM `newsletter_recipient_tag`';
        $results = $this->connection->executeQuery($sql)->fetchAll();
        $ids = [];

        foreach ($results as $item) {
            $ids[] = Uuid::fromBytesToHex($item['tag_id']);
        }

        return $ids;
    }
}