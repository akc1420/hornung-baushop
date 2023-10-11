<?php

namespace Crsw\CleverReachOfficial\Entity\Customer\Repositories;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\DTO\Receiver;
use Crsw\CleverReachOfficial\Core\Infrastructure\Logger\Logger;
use Crsw\CleverReachOfficial\Entity\Language\Repositories\LanguageRepository;
use Crsw\CleverReachOfficial\Entity\SalesChannel\Repositories\SalesChannelRepository;
use Crsw\CleverReachOfficial\Entity\SalutationTranslation\Repositories\SalutationTranslationRepository;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Content\Newsletter\Aggregate\NewsletterRecipient\NewsletterRecipientCollection;
use Shopware\Core\Content\Newsletter\Aggregate\NewsletterRecipient\NewsletterRecipientEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Uuid\Uuid;

/**
 * Class SubscriberRepository
 *
 * @package Crsw\CleverReachOfficial\Entity\Customer\Repositories
 */
class SubscriberRepository implements ReceiverRepository
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
     * @var LanguageRepository
     */
    private $languageRepository;
    /**
     * @var SalesChannelRepository
     */
    private $salesChannelRepository;
    /**
     * @var SalutationTranslationRepository
     */
    private $salutationRepository;
    /**
     * @var CustomerRepository
     */
    private $customerRepository;

    private static $selectAttributes = [
        'subscriber.id AS subscriberId',
        'subscriber.email',
        'subscriber.first_name',
        'subscriber.last_name',
        'subscriber.zip_code',
        'subscriber.city',
        'subscriber.street',
        'subscriber.status',
        'subscriber.created_at',
        'subscriber.confirmed_at',
        'language.name AS languageName',
        'sales_channel_translation.name AS salesChannelName',
        'salutation_translation.display_name',
        'GROUP_CONCAT(distinct sales_channel_domain.url) as domainUrls',
        'customer.id AS customerId',
        'customer.first_name AS customerFirstName',
        'customer.last_name AS customerLastName',
        'customer.birthday',
        'customer_group_translation.name AS customerGroup',
        'customer_address.street AS customerStreet',
        'customer_address.zipcode AS customerZip',
        'customer_address.city AS customerCity',
        'customer_address.company',
        'customer_address.phone_number',
        'country_translation.name AS countryName',
        'country_state_translation.name AS stateName'
    ];

    /**
     * SubscriberRepository constructor.
     *
     * @param EntityRepositoryInterface $baseRepository
     * @param Connection $connection
     * @param LanguageRepository $languageRepository
     * @param SalesChannelRepository $salesChannelRepository
     * @param SalutationTranslationRepository $salutationRepository
     * @param CustomerRepository $customerRepository
     */
    public function __construct(
        EntityRepositoryInterface $baseRepository,
        Connection $connection,
        LanguageRepository $languageRepository,
        SalesChannelRepository $salesChannelRepository,
        SalutationTranslationRepository $salutationRepository,
        CustomerRepository $customerRepository
    ) {
        $this->baseRepository = $baseRepository;
        $this->connection = $connection;
        $this->languageRepository = $languageRepository;
        $this->salesChannelRepository = $salesChannelRepository;
        $this->salutationRepository = $salutationRepository;
        $this->customerRepository = $customerRepository;
    }

    /**
     * @inheritDoc
     */
    public function count(): int
    {
        /** @noinspection SqlDialectInspection */
        /** @noinspection SqlNoDataSourceInspection */
        $sql = "SELECT COUNT(newsletter_recipient.id) as numberOfSubscribers
        FROM {$this->baseRepository->getDefinition()->getEntityName()}";

        return $this->connection->fetchAll($sql)[0]['numberOfSubscribers'];
    }

    /**
     * Gets receiver by email.
     *
     * @param string $email
     *
     * @return mixed
     */
    public function getByEmail(string $email)
    {
        $sql = "SELECT " . implode(', ', static::$selectAttributes) . ", tags.tagNames, customerTags.customerTagsNames
        FROM newsletter_recipient AS subscriber
        LEFT JOIN language ON subscriber.language_id = language.id
        LEFT JOIN sales_channel_translation ON subscriber.sales_channel_id = sales_channel_translation.sales_channel_id
        LEFT JOIN customer ON customer.email = subscriber.email
        LEFT JOIN customer_group_translation ON customer.customer_group_id =
        customer_group_translation.customer_group_id
        AND customer_group_translation.language_id = language.id
        LEFT JOIN customer_address ON customer_address.id = customer.default_shipping_address_id
        LEFT JOIN country_translation ON customer_address.country_id = country_translation.country_id AND
        country_translation.language_id = language.id
        LEFT JOIN country_state_translation ON customer_address.country_state_id =
        country_state_translation.country_state_id
        AND country_state_translation.language_id = language.id
        LEFT JOIN sales_channel_domain ON sales_channel_domain.sales_channel_id = subscriber.sales_channel_id
        LEFT JOIN salutation_translation ON (salutation_translation.salutation_id = subscriber.salutation_id OR
        customer.salutation_id = salutation_translation.salutation_id) AND
        salutation_translation.language_id = language.id
        LEFT JOIN (
            SELECT newsletter_recipient.id AS subscriberId, GROUP_CONCAT(distinct tag.name) AS tagNames
            FROM tag
            LEFT JOIN newsletter_recipient_tag ON newsletter_recipient_tag.tag_id = tag.id
            LEFT JOIN newsletter_recipient on newsletter_recipient.id = newsletter_recipient_tag.newsletter_recipient_id
            GROUP BY newsletter_recipient.id) AS tags ON tags.subscriberId = subscriber.id
        LEFT JOIN (
            SELECT customer.id AS customerId, GROUP_CONCAT(distinct tag.name) AS customerTagsNames
            FROM tag
            LEFT JOIN customer_tag ON customer_tag.tag_id = tag.id
            LEFT JOIN customer ON customer.id = customer_tag.customer_id
            GROUP BY customer.id) AS customerTags ON customerTags.customerId = customer.id
        WHERE subscriber.email = :email
        GROUP BY subscriber.id, language.id, sales_channel_domain.url, sales_channel_translation.name,
        salutation_translation.display_name, customer.id, customer_group_translation.customer_group_id,
         customer_address.id, country_translation.name, country_state_translation.name";

        $result = $this->connection->fetchAll($sql, ['email' => $email]);

        return $result ? $result[0] : null;
    }

    /**
     * @inheritDoc
     */
    public function getByEmails(array $emails): array
    {
        $sql = "SELECT " . implode(', ', static::$selectAttributes) . ", tags.tagNames, customerTags.customerTagsNames
        FROM newsletter_recipient AS subscriber
        LEFT JOIN language ON subscriber.language_id = language.id
        LEFT JOIN sales_channel_translation ON subscriber.sales_channel_id = sales_channel_translation.sales_channel_id
        LEFT JOIN customer ON customer.email = subscriber.email
        LEFT JOIN customer_group_translation ON customer.customer_group_id =
        customer_group_translation.customer_group_id
        AND customer_group_translation.language_id = language.id
        LEFT JOIN customer_address ON customer_address.id = customer.default_shipping_address_id
        LEFT JOIN country_translation ON customer_address.country_id = country_translation.country_id AND
        country_translation.language_id = language.id
        LEFT JOIN country_state_translation ON customer_address.country_state_id =
        country_state_translation.country_state_id
        AND country_state_translation.language_id = language.id
        LEFT JOIN sales_channel_domain ON sales_channel_domain.sales_channel_id = subscriber.sales_channel_id
        LEFT JOIN salutation_translation ON (salutation_translation.salutation_id = subscriber.salutation_id OR
        customer.salutation_id = salutation_translation.salutation_id) AND
        salutation_translation.language_id = language.id
        LEFT JOIN (
            SELECT newsletter_recipient.id AS subscriberId, GROUP_CONCAT(distinct tag.name) AS tagNames
            FROM tag
            LEFT JOIN newsletter_recipient_tag ON newsletter_recipient_tag.tag_id = tag.id
            LEFT JOIN newsletter_recipient on newsletter_recipient.id = newsletter_recipient_tag.newsletter_recipient_id
            GROUP BY newsletter_recipient.id) AS tags ON tags.subscriberId = subscriber.id
        LEFT JOIN (
            SELECT customer.id AS customerId, GROUP_CONCAT(distinct tag.name) AS customerTagsNames
            FROM tag
            LEFT JOIN customer_tag ON customer_tag.tag_id = tag.id
            LEFT JOIN customer ON customer.id = customer_tag.customer_id
            GROUP BY customer.id) AS customerTags ON customerTags.customerId = customer.id
        WHERE subscriber.email IN (?)
        GROUP BY subscriber.id, language.id, sales_channel_domain.url, sales_channel_translation.name,
        salutation_translation.display_name, customer.id, customer_group_translation.customer_group_id,
         customer_address.id, country_translation.name, country_state_translation.name";

        return $this->connection->fetchAll($sql, array($emails), array(Connection::PARAM_STR_ARRAY));
    }

    /**
     * Get newsletter recipients by ids.
     *
     * @param array $ids
     * @param Context $context
     *
     * @return NewsletterRecipientCollection
     */
    public function getByIds(array $ids, Context $context): NewsletterRecipientCollection
    {
        $criteria = new Criteria($ids);

        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->baseRepository->search($criteria, $context)->getEntities();
    }

    /**
     * Retrieves list of receiver emails provided by the integration.
     *
     * @return string[]
     */
    public function getEmails(): array
    {
        /** @noinspection SqlNoDataSourceInspection */
        /** @noinspection SqlDialectInspection */
        $sql = "SELECT newsletter_recipient.email FROM newsletter_recipient";

        return array_column($this->connection->fetchAll($sql), 'email');
    }

    /**
     * @inheritDoc
     */
    public function update(Receiver $receiver): void
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('email', $receiver->getEmail()));

        /** @var NewsletterRecipientEntity $oldData */
        $oldData = $this->baseRepository->search($criteria, Context::createDefaultContext())->first();

        if (!$oldData) {
            return;
        }

        $data = $this->receiverToArray($receiver);
        $data['id'] = $oldData->getId();
        $data['hash'] = $oldData->getHash();

        $this->baseRepository->update([$data], Context::createDefaultContext());
    }

    /**
     * @inheritDoc
     */
    public function create(Receiver $receiver): void
    {
        $data = $this->receiverToArray($receiver);
        $data['hash'] = Uuid::randomHex();
        $this->baseRepository->create([$data], Context::createDefaultContext());
    }

    /**
     * @inheritDoc
     */
    public function delete(string $email): void
    {
        /** @noinspection SqlDialectInspection */
        /** @noinspection SqlNoDataSourceInspection */
        $sql = "DELETE FROM newsletter_recipient WHERE email=:email";

        try {
            $this->connection->executeUpdate($sql, ['email' => $email]);
        } catch (DBALException $e) {
            Logger::logError($e->getMessage());
        }
    }

    /**
     * Unsubscribes receiver.
     *
     * @param string $email
     */
    public function unsubscribe(string $email): void
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('email', $email));

        /** @var NewsletterRecipientEntity $oldData */
        $oldData = $this->baseRepository->search($criteria, Context::createDefaultContext())->first();

        if (!$oldData) {
            return;
        }

        $data = [
            'email' => $email,
            'status' => 'optOut'
        ];
        $data['id'] = $oldData->getId();
        $data['hash'] = $oldData->getHash();

        $this->baseRepository->update([$data], Context::createDefaultContext());
    }

    /**
     * @param Receiver $receiver
     *
     * @return array
     */
    private function receiverToArray(Receiver $receiver): array
    {
        /** @var CustomerEntity $customer */
        $customer = $this->customerRepository->getByEmail($receiver->getEmail());

        $salutation = $this->salutationRepository
            ->getSalutationByDisplayName(
                $receiver->getSalutation() ?: 'Not specified',
                Context::createDefaultContext()
            );

        $language = $this->languageRepository
            ->getLanguageByName($receiver->getLanguage() ?: '', Context::createDefaultContext());

        $shopName = str_replace('Shopware 6 ', '', $receiver->getShop());
        $salesChannel = $this->salesChannelRepository
            ->getSalesChannelByName($shopName, Context::createDefaultContext());

        // if any of receiver fields are not set and customer already exists in Shopware,
        // set receiver fields to customer's fields values
        if ($customer) {
            $receiver->setFirstName($receiver->getFirstName() ?: $customer->getFirstName());
            $receiver->setLastName($receiver->getLastName() ?: $customer->getLastName());
            $salutation = $salutation ?: $customer->getSalutation();
            $language = $language ?: $customer->getLanguage();
            $salesChannel = $salesChannel ?: $customer->getSalesChannel();
        }

        if ($customer && $customer->getDefaultShippingAddress()) {
            $receiver->setZip($receiver->getZip() ?: $customer->getDefaultShippingAddress()->getZipcode());
            $receiver->setCity($receiver->getCity() ?: $customer->getDefaultShippingAddress()->getCity());
            $receiver->setStreet($receiver->getStreet() ?: $customer->getDefaultShippingAddress()->getStreet());
        }

        return [
            'email' => $receiver->getEmail(),
            'firstName' =>  $receiver->getFirstName(),
            'lastName' => $receiver->getLastName(),
            'zipCode' => $receiver->getZip(),
            'city' => $receiver->getCity(),
            'street' => $receiver->getStreet() . ' ' . $receiver->getStreetNumber(),
            'status' => $receiver->getActivated() ?
                'direct' : 'optOut',
            'salutationId' => $salutation ? $salutation->getSalutationId() : null,
            'languageId' => $language ? $language->getId() : null,
            'salesChannelId' => $salesChannel ? $salesChannel->getId() : null,
            'createdAt' => $receiver->getRegistered(),
        ];
    }
}
