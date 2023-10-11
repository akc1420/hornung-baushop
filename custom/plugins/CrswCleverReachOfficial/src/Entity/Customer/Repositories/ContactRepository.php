<?php

namespace Crsw\CleverReachOfficial\Entity\Customer\Repositories;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\DTO\Receiver;
use Doctrine\DBAL\Connection;
use RuntimeException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * Class ContactRepository
 *
 * @package Crsw\CleverReachOfficial\Entity\Customer\Repositories
 */
class ContactRepository extends CustomerRepository
{
    /**
     * ContactRepository constructor.
     *
     * @param EntityRepositoryInterface $baseRepository
     * @param Connection $connection
     */
    public function __construct(
        EntityRepositoryInterface $baseRepository,
        Connection $connection,
        ParameterBagInterface $parameterBag
    ) {
        parent::__construct($baseRepository, $connection, $parameterBag);
    }

    /**
     * @inheritDoc
     */
    public function count(): int
    {
        return count($this->getEmails());
    }

    /**
     * @inheritDoc
     */
    public function getByEmail(string $email): ?Entity
    {
        $criteria = $this->getByEmailCriteria($email);
        $criteria->addFilter(new EqualsFilter('lastOrderDate', null));

        return $this->baseRepository->search($criteria, Context::createDefaultContext())->first();
    }

    /**
     * @inheritDoc
     */
    public function getByEmails(array $emails): EntityCollection
    {
        $criteria = $this->getByEmailsCriteria($emails);
        $criteria->addFilter(new EqualsFilter('lastOrderDate', null));

        return $this->baseRepository->search($criteria, Context::createDefaultContext())->getEntities();
    }

    /**
     * @inheritDoc
     */
    public function getEmails(): array
    {
        $sql = $this->getEmailsCriteria();
        $sql .= " WHERE customer.last_order_date IS NULL
                    AND customer.newsletter = 0
                    AND (
                        SELECT COUNT(*) 
                        FROM order_customer 
                        WHERE email = customer.email
                    ) = 0;";

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
}
