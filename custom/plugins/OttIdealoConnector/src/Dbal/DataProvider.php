<?php declare(strict_types=1);

namespace Ott\IdealoConnector\Dbal;

use Ott\IdealoConnector\Dbal\Entity\Checkout\IdealoOrderEntity;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Shipping\ShippingMethodEntity;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\Country\CountryEntity;
use Shopware\Core\System\Currency\CurrencyEntity;
use Shopware\Core\System\Salutation\SalutationEntity;

class DataProvider
{
    private EntityRepositoryInterface $countryRepository;
    private EntityRepositoryInterface $customerRepository;
    private EntityRepositoryInterface $salutationRepository;
    private EntityRepositoryInterface $productRepository;
    private EntityRepositoryInterface $shippingMethodRepository;
    private EntityRepositoryInterface $salesChannelRepository;
    private EntityRepositoryInterface $idealoOrderRepository;
    private EntityRepositoryInterface $orderRepository;
    private EntityRepositoryInterface $pluginRepository;
    private EntityRepositoryInterface $paymentMethodRepository;
    private EntityRepositoryInterface $currencyRepository;

    public function __construct(
        EntityRepositoryInterface $countryRepository,
        EntityRepositoryInterface $customerRepository,
        EntityRepositoryInterface $salutationRepository,
        EntityRepositoryInterface $productRepository,
        EntityRepositoryInterface $shippingMethodRepository,
        EntityRepositoryInterface $salesChannelRepository,
        EntityRepositoryInterface $idealoOrderRepository,
        EntityRepositoryInterface $orderRepository,
        EntityRepositoryInterface $pluginRepository,
        EntityRepositoryInterface $paymentMethodRepository,
        EntityRepositoryInterface $currencyRepository
    )
    {
        $this->countryRepository = $countryRepository;
        $this->customerRepository = $customerRepository;
        $this->salutationRepository = $salutationRepository;
        $this->productRepository = $productRepository;
        $this->shippingMethodRepository = $shippingMethodRepository;
        $this->salesChannelRepository = $salesChannelRepository;
        $this->idealoOrderRepository = $idealoOrderRepository;
        $this->orderRepository = $orderRepository;
        $this->pluginRepository = $pluginRepository;
        $this->paymentMethodRepository = $paymentMethodRepository;
        $this->currencyRepository = $currencyRepository;
    }

    public function getCurrencyByIso(string $isoCode): CurrencyEntity
    {
        return $this->currencyRepository->search(
            (new Criteria())->addFilter(new EqualsFilter('isoCode', $isoCode)),
            Context::createDefaultContext()
        )->getEntities()->first();
    }

    public function getCountryByIso(string $isoCode): ?CountryEntity
    {
        return $this->countryRepository->search(
            (new Criteria())->addFilter(new EqualsFilter('iso', $isoCode)),
            Context::createDefaultContext()
        )->getEntities()->first();
    }

    public function getCustomerById(string $customerId): ?CustomerEntity
    {
        return $this->customerRepository->search(
            (new Criteria([$customerId])),
            Context::createDefaultContext()
        )->getEntities()->first();
    }

    public function getOrderById(string $orderId): ?OrderEntity
    {
        return $this->orderRepository->search(
            (new Criteria([$orderId]))->addAssociations([
                'currency',
                'orderCustomer',
                'orderCustomer.salutation',
                'addresses',
                'addresses.country',
                'deliveries',
                'deliveries.shippingMethod',
                'deliveries.shippingOrderAddress',
                'deliveries.shippingOrderAddress.country',
                'lineItems',
                'transactions',
                'transactions.paymentMethod',
                'lineItems.product',
            ]),
            Context::createDefaultContext()
        )->getEntities()->first();
    }

    public function getSalutationByKey(string $salutationKey): ?SalutationEntity
    {
        return $this->salutationRepository->search(
            (new Criteria())->addFilter(new EqualsFilter('salutationKey', $salutationKey)),
            Context::createDefaultContext()
        )->getEntities()->first();
    }

    public function getProductByProductNumber(string $productNumber): ?ProductEntity
    {
        return $this->productRepository->search(
            (new Criteria())
                ->addFilter(new EqualsFilter('productNumber', $productNumber))
                ->addAssociations([
                    'tax.rules',
                    'tax.rules.country',
                ]),
            Context::createDefaultContext()
        )->getEntities()->first();
    }

    public function getProductById(string $productId): ?ProductEntity
    {
        return $this->productRepository->search(
            (new Criteria())
                ->addFilter(new EqualsFilter('id', $productId))
                ->addAssociations([
                    'tax.rules',
                    'tax.rules.country',
                ]),
            Context::createDefaultContext()
        )->getEntities()->first();
    }

    public function getShippingMethod(string $shippingMethodId): ?ShippingMethodEntity
    {
        return $this->shippingMethodRepository->search(
            (new Criteria([$shippingMethodId])),
            Context::createDefaultContext()
        )->getEntities()->first();
    }

    public function getSalesChannels(): EntityCollection
    {
        return $this->salesChannelRepository->search(
            (new Criteria())
                ->addAssociation('language')
                ->addAssociation('currency'),
            Context::createDefaultContext()
        )->getEntities();
    }

    public function isIdealoOrder(string $idealoTransactionId): bool
    {
        return 0 < $this->idealoOrderRepository->search(
            (new Criteria())->addFilter(new EqualsFilter('idealoTransactionId', $idealoTransactionId)),
            Context::createDefaultContext()
        )->getTotal();
    }

    public function getIdealoOrder(string $idealoTransactionId): ?IdealoOrderEntity
    {
        return $this->idealoOrderRepository->search(
            (new Criteria())->addFilter(new EqualsFilter('idealoTransactionId', $idealoTransactionId)),
            Context::createDefaultContext()
        )->getEntities()->first();
    }

    public function getIdealoTransactionId(string $orderId): ?string
    {
        $result = $this
            ->idealoOrderRepository
            ->search(
                (new Criteria())->addFilter(new EqualsFilter('orderId', $orderId)),
                Context::createDefaultContext()
            )
        ;

        if (0 < $result->getTotal()) {
            return $result->getEntities()->first()->getIdealoTransactionId();
        }

        return '';
    }

    public function getPluginVersion(): string
    {
        $result = $this->pluginRepository->search(
            (new Criteria())->addFilter(new EqualsFilter('name', 'IdealoConnector')),
            Context::createDefaultContext()
        );

        if (0 < $result->getTotal()) {
            return $result->getEntities()->first()->getVersion();
        }

        return '';
    }

    public function getPaymentMethodIdByName(string $name): string
    {
        $result = $this->paymentMethodRepository->search(
            (new Criteria())->addFilter(new EqualsFilter('name', $name)),
            Context::createDefaultContext()
        );

        if (0 < $result->getTotal()) {
            return $result->getEntities()->first()->getId();
        }

        return '';
    }
}
