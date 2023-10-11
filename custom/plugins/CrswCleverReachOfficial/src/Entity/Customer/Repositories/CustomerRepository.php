<?php


namespace Crsw\CleverReachOfficial\Entity\Customer\Repositories;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\DTO\Receiver;
use Doctrine\DBAL\Connection;
use Shopware\Core\Checkout\Customer\CustomerCollection;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * Class CustomerRepository
 *
 * @package Crsw\CleverReachOfficial\Entity\Customer\Repositories
 */
class CustomerRepository implements ReceiverRepository
{
    /**
     * @var EntityRepositoryInterface
     */
    protected $baseRepository;
    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var ParameterBagInterface
     */
    protected $params;

    /**
     * CustomerRepository constructor.
     *
     * @param EntityRepositoryInterface $baseRepository
     * @param Connection $connection
     * @param ParameterBagInterface $params
     */
    public function __construct(
        EntityRepositoryInterface $baseRepository,
        Connection $connection,
        ParameterBagInterface $params
    ) {
        $this->baseRepository = $baseRepository;
        $this->connection = $connection;
        $this->params = $params;
    }

    /**
     * @inheritDoc
     */
    public function count(): int
    {
        return 0;
    }

    /**
     * @inheritDoc
     */
    public function getByEmail(string $email): ?Entity
    {
        return $this->baseRepository->search($this->getByEmailCriteria($email), Context::createDefaultContext())
            ->first();
    }

    /**
     * @inheritDoc
     */
    public function getByEmails(array $emails): EntityCollection
    {
        return $this->baseRepository->search($this->getByEmailsCriteria($emails), Context::createDefaultContext())
            ->getEntities();
    }

    /**
     * @inheritDoc
     */
    public function getEmails(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function update(Receiver $receiver): void
    {
    }

    /**
     * @inheritDoc
     */
    public function create(Receiver $receiver): void
    {
    }

    /**
     * @inheritDoc
     */
    public function delete(string $email): void
    {
    }

    /**
     * Returns customer entity by its unique id.
     *
     * @param string $id
     * @param Context $context
     *
     * @return CustomerEntity|null
     */
    public function getCustomerById(string $id, Context $context): ?CustomerEntity
    {
        $criteria = new Criteria([$id]);
        $criteria->addAssociation('group');

        return $this->baseRepository->search($criteria, $context)->first();
    }

    /**
     * Gets customers by ids.
     *
     * @param array $ids
     * @param Context $context
     *
     * @return CustomerCollection
     */
    public function getCustomersByIds(array $ids, Context $context): EntityCollection
    {
        $criteria = new Criteria($ids);

        return $this->baseRepository->search($criteria, $context)->getEntities();
    }

    /**
     * @param string $email
     * @param Context $context
     *
     * @return CustomerEntity|null
     */
    public function getCustomerByEmail(string $email, Context $context): ?CustomerEntity
    {
        $criteria = $this->getByEmailCriteria($email);

        return $this->baseRepository->search($criteria, $context)->first();
    }

    /**
     * Gets sql for counting customers.
     *
     * @return string
     */
    protected function getCountSql(): string
    {
        return "SELECT COUNT(receiver.id) as numberOfReceivers
                FROM (
                    SELECT customer.id
                    FROM {$this->baseRepository->getDefinition()->getEntityName()}";
    }

    /**
     * Gets criteria for fetching customer by email.
     *
     * @param string $email
     *
     * @return Criteria
     */
    protected function getByEmailCriteria(string $email): Criteria
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('email', $email));

        $criteria->addAssociations([
            'group',
            'salutation',
            'defaultBillingAddress.country',
            'defaultBillingAddress.countryState',
            'defaultShippingAddress.country',
            'defaultShippingAddress.countryState',
            'language',
            'tags',
            'salesChannel.domains',
            'salesChannel.translation',
            'birthday',
            'orderCustomers.order.lineItems.order.customer',
            'orderCustomers.order.lineItems.order.currency',
        ]);

        return $criteria;
    }

    /**
     * Gets criteria for fetching customers by emails.
     *
     * @param array $emails
     *
     * @return Criteria
     */
    protected function getByEmailsCriteria(array $emails): Criteria
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsAnyFilter('email', $emails));

        $criteria->addAssociations([
            'group',
            'salutation',
            'defaultBillingAddress.country',
            'defaultBillingAddress.countryState',
            'defaultShippingAddress.country',
            'defaultShippingAddress.countryState',
            'language',
            'tags',
            'salesChannel.domains',
            'salesChannel.translation',
            'birthday',
            'orderCustomers.order.lineItems.order.customer',
            'orderCustomers.order.lineItems.order.currency',
        ]);

        return $criteria;
    }

    /**
     * Gets sql for fetching emails.
     *
     * @return string
     */
    protected function getEmailsCriteria(): string
    {
        return "SELECT customer.email
                FROM customer";
    }

    /**
     * @return bool
     */
    protected function isVersion643OrGreater(): bool
    {
        return version_compare($this->params->get('kernel.shopware_version'), '6.4.3', 'ge');
    }
}
