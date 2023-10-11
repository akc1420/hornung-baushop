<?php

namespace Crsw\CleverReachOfficial\Entity\Customer\Repositories;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\DTO\Receiver;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use RuntimeException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * Class BuyerRepository
 *
 * @package Crsw\CleverReachOfficial\Entity\Customer\Repositories
 */
class BuyerRepository extends CustomerRepository implements OrderCountAware
{

    /**
     * BuyerRepository constructor.
     *
     * @param EntityRepositoryInterface $baseRepository
     * @param Connection $connection
     * @param ParameterBagInterface $parameterBag
     */
    public function __construct(
        EntityRepositoryInterface $baseRepository,
        Connection                $connection,
        ParameterBagInterface     $parameterBag
    )
    {
        parent::__construct($baseRepository, $connection, $parameterBag);
    }

    /**
     * @param string $customerEmail
     *
     * @return int
     *
     * @throws Exception
     */
    public function countOrders(string $customerEmail): int
    {
        if (!filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
            return 0;
        }

        $sql = "SELECT COUNT(*) as numberOfOrders from order_customer WHERE email = :customerEmail";

        return $this->connection->fetchAllAssociative($sql, ['customerEmail' => $customerEmail])[0]['numberOfOrders'];
    }

    /**
     * @inheritDoc
     */
    public function count(): int
    {
        if ($this->isVersion643OrGreater()) {
            return $this->countBuyersFromOrdersTable();
        }

        $sql = $this->getCountSql();

        $sql .= " WHERE customer.last_order_date IS NOT NULL
                ) as receiver";

        return $this->connection->fetchAll($sql)[0]['numberOfReceivers'];
    }

    /**
     * @inheritDoc
     */
    public function getByEmail(string $email): ?Entity
    {
        $criteria = $this->getByEmailCriteria($email);
        if (!$this->isVersion643OrGreater()) {
            $criteria->addFilter(new NotFilter(
                NotFilter::CONNECTION_OR,
                [
                    new EqualsFilter('lastOrderDate', null)
                ]
            ));
        } else {
            $criteria->addFilter(new NotFilter(
                NotFilter::CONNECTION_OR,
                [
                    new EqualsFilter('orderCustomers.id', null)
                ]
            ));
        }

        return $this->baseRepository->search($criteria, Context::createDefaultContext())->first();
    }

    /**
     * @inheritDoc
     */
    public function getByEmails(array $emails): EntityCollection
    {
        $criteria = $this->getByEmailsCriteria($emails);
        if (!$this->isVersion643OrGreater()) {
            $criteria->addFilter(new NotFilter(
                NotFilter::CONNECTION_OR,
                [
                    new EqualsFilter('lastOrderDate', null)
                ]
            ));
        } else {
            $criteria->addFilter(new NotFilter(
                NotFilter::CONNECTION_OR,
                [
                    new EqualsFilter('orderCustomers.id', null)
                ]
            ));
        }

        return $this->baseRepository->search($criteria, Context::createDefaultContext())->getEntities();
    }

    /**
     * @inheritDoc
     */
    public function getEmails(): array
    {
        if ($this->isVersion643OrGreater()) {
            $sql = "SELECT DISTINCT email from order_customer";
        } else {
            $sql = $this->getEmailsCriteria();
            $sql .= " WHERE customer.last_order_date IS NOT NULL";
        }

        return array_column($this->connection->fetchAll($sql), 'email');
    }

    /**
     * @inheritDoc
     */
    public function update(Receiver $receiver): void
    {
        throw new RuntimeException('Not implemented.');
    }

    /**
     * @inheritDoc
     */
    public function create(Receiver $receiver): void
    {
        throw new RuntimeException('Not implemented.');
    }

    /**
     * @inheritDoc
     */
    public function delete(string $email): void
    {
        throw new RuntimeException('Not implemented.');
    }

    /**
     * @return int
     * @throws Exception
     */
    private function countBuyersFromOrdersTable(): int
    {
        $sql = "SELECT COUNT(distinct customer_id) as numberOfBuyers from order_customer";

        return $this->connection->fetchAllAssociative($sql)[0]['numberOfBuyers'];
    }
}
