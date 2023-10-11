<?php declare(strict_types=1);

namespace Sensus\Check24Connect\Service;

use Psr\Log\LoggerInterface;
use Sensus\Check24Connect\Struct\Address;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\Country\CountryEntity;
use Shopware\Core\System\NumberRange\ValueGenerator\NumberRangeValueGeneratorInterface;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\Salutation\SalutationEntity;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class CustomerService
{
    /**
     * @var SystemConfigService
     */
    private $systemConfigService;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var EntityRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var NumberRangeValueGeneratorInterface
     */
    private $numberRangeValueGenerator;

    /**
     * @var EntityRepositoryInterface
     */
    private $salutationRepository;

    /**
     * @var EntityRepositoryInterface
     */
    private $countryRepository;

    /**
     * CustomerService constructor.
     * @param SystemConfigService $systemConfigService
     * @param LoggerInterface $logger
     * @param EntityRepositoryInterface $customerRepository
     * @param EventDispatcherInterface $eventDispatcher
     * @param NumberRangeValueGeneratorInterface $numberRangeValueGenerator
     * @param EntityRepositoryInterface $salutationRepository
     * @param EntityRepositoryInterface $countryRepository
     */
    public function __construct(SystemConfigService $systemConfigService, LoggerInterface $logger, EntityRepositoryInterface $customerRepository,
                                EventDispatcherInterface $eventDispatcher, NumberRangeValueGeneratorInterface $numberRangeValueGenerator,
                                EntityRepositoryInterface $salutationRepository, EntityRepositoryInterface $countryRepository)
    {
        $this->systemConfigService = $systemConfigService;
        $this->logger = $logger;
        $this->customerRepository = $customerRepository;
        $this->eventDispatcher = $eventDispatcher;
        $this->numberRangeValueGenerator = $numberRangeValueGenerator;
        $this->salutationRepository = $salutationRepository;
        $this->countryRepository = $countryRepository;
    }

    /**
     * @param string $email
     * @param Address $billingAddress
     * @param Address $shippingAddress
     * @param SalesChannelContext $context
     * @return CustomerEntity
     */
    public function createCustomer(string $email, Address $billingAddress, Address $shippingAddress, SalesChannelContext $context)
    {
        $salutationEntity = $this->getSalutationEntity($billingAddress, $context);
        $shippingSalutationEntity = $this->getSalutationEntity($shippingAddress, $context);

        $customerData = [
            'id' => Uuid::randomHex(),
            'customerNumber' => $this->numberRangeValueGenerator->getValue(
                $this->customerRepository->getDefinition()->getEntityName(),
                $context->getContext(),
                $context->getSalesChannel()->getId()
            ),
            'salesChannelId' => $context->getSalesChannel()->getId(),
            'languageId' => $context->getContext()->getLanguageId(),
            'groupId' => $this->systemConfigService->get('SensusCheck24Connect.config.customerGroup', $context->getSalesChannel()->getId()) ?? $context->getCurrentCustomerGroup()->getId(),
            'defaultPaymentMethodId' => $this->systemConfigService->get('SensusCheck24Connect.config.paymentMethod', $context->getSalesChannel()->getId()) ?? $context->getPaymentMethod()->getId(),
            'salutationId' => $salutationEntity->getId(),
            'firstName' => $billingAddress->getFirstname(),
            'lastName' => $billingAddress->getLastname(),
            'email' => $email,
            'title' => '',
            'affiliateCode' => '',
            'campaignCode' => '',
            'active' => true,
            'birthday' => NULL,
            'guest' => true,
            'firstLogin' => new \DateTimeImmutable(),
            'addresses' => []
        ];

        $billingAddressArray = $this->mapAddress($billingAddress, $context, $salutationEntity, $customerData['id']);
        $shippingAddressArray = $this->mapAddress($shippingAddress, $context, $shippingSalutationEntity, $customerData['id']);

        $customerData['defaultBillingAddressId'] = $billingAddressArray['id'];
        $customerData['defaultShippingAddressId'] = $shippingAddressArray['id'];

        $customerData['addresses'][] = $billingAddressArray;
        $customerData['addresses'][] = $shippingAddressArray;

        $this->customerRepository->create([$customerData], $context->getContext());

        $criteria = new Criteria([$customerData['id']]);
        $criteria->addAssociation('addresses');
        $criteria->addAssociation('salutation');

        /** @var CustomerEntity $customerEntity */
        $customerEntity = $this->customerRepository->search($criteria, $context->getContext())->first();

        return $customerEntity;
    }

    /**
     * @param Address $address
     * @param SalesChannelContext $context
     * @return SalutationEntity
     */
    protected function getSalutationEntity(Address $address, SalesChannelContext $context): SalutationEntity
    {
        $salutationKey = 'mr';
        if ($address->getSalutation() != 'mr') {
            $salutationKey = 'mrs';
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('salutationKey', $salutationKey));
        $salutations = $this->salutationRepository->search($criteria, $context->getContext());

        return $salutations->first();
    }

    /**
     * @param Address $address
     * @param SalesChannelContext $context
     * @return CountryEntity
     */
    protected function getCountryEntity(Address $address, SalesChannelContext $context): CountryEntity
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('iso', $address->getCountry()));
        $countries = $this->countryRepository->search($criteria, $context->getContext());

        return $countries->first();
    }

    /**
     * @param Address $address
     * @param SalesChannelContext $context
     * @param SalutationEntity $salutationEntity
     * @param $customerId
     * @return array
     */
    protected function mapAddress(Address $address, SalesChannelContext $context, SalutationEntity $salutationEntity, $customerId): array
    {
        $country = $this->getCountryEntity($address, $context);

        return [
            'id' => Uuid::randomHex(),
            'salutationId' => $salutationEntity->getId(),
            'firstName' => $address->getFirstname(),
            'lastName' => $address->getLastname(),
            'company' => $address->getCompany(),
            'department' => '',
            'vatId' => '',
            'street' => $address->getStreet(),
            'zipcode' => $address->getZip(),
            'city' => $address->getCity(),
            'countryId' => $country->getId(),
            'countryStateId' => NULL,
            'customerId' => $customerId
        ];
    }

}