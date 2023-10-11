<?php declare(strict_types=1);

namespace Ott\IdealoConnector\Service;

use Ott\IdealoConnector\Dbal\DataPersister;
use Ott\IdealoConnector\Dbal\DataProvider;
use Ott\IdealoConnector\Event\IdealoCustomerBeforeSaveEvent;
use Ott\IdealoConnector\Event\IdealoCustomerNumberGeneratedEvent;
use Ott\IdealoConnector\Event\IdealoCustomerSavedEvent;
use Ott\IdealoConnector\Event\IdealoOrderBeforeSaveEvent;
use Ott\IdealoConnector\Event\IdealoOrderItemFetchedEvent;
use Ott\IdealoConnector\Event\IdealoOrderLineItemBeforeSaveEvent;
use Ott\IdealoConnector\Event\IdealoOrderNumberGeneratedEvent;
use Ott\IdealoConnector\Event\IdealoOrderSavedEvent;
use Ott\IdealoConnector\OttIdealoConnector;
use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Cart\CartRuleLoader;
use Shopware\Core\Checkout\Cart\Delivery\Struct\DeliveryDate;
use Shopware\Core\Checkout\Cart\Delivery\Struct\DeliveryTime;
use Shopware\Core\Checkout\Cart\Event\CheckoutOrderPlacedEvent;
use Shopware\Core\Checkout\Order\Event\OrderStateMachineStateChangeEvent;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Util\Random;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\Country\CountryEntity;
use Shopware\Core\System\NumberRange\ValueGenerator\NumberRangeValueGenerator;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextService;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class OrderImportService
{
    private const DEBUG = false;
    private const DEBUG_ITEM = 'SWDEMO10001';
    private LoggerInterface $logger;
    private ConfigProvider $configProvider;
    private EventDispatcherInterface $eventDispatcher;
    private DataProvider $dataProvider;
    private NumberRangeValueGenerator $valueGenerator;
    private Context $context;
    private DataPersister $dataPersister;

    /**
     * @var CachedSalesChannelContextFactory|mixed
     */
    private $salesChannelContextFactory;
    private CartRuleLoader $ruleLoader;

    public function __construct(
        DataProvider $dataProvider,
        DataPersister $dataPersister,
        NumberRangeValueGenerator $valueGenerator,
        EventDispatcherInterface $eventDispatcher,
        CartRuleLoader $ruleLoader,
        LoggerInterface $logger,
        $salesChannelContextFactory
    )
    {
        $this->logger = $logger;
        $this->eventDispatcher = $eventDispatcher;
        $this->dataProvider = $dataProvider;
        $this->valueGenerator = $valueGenerator;
        $this->dataPersister = $dataPersister;
        $this->context = Context::createDefaultContext();
        $this->salesChannelContextFactory = $salesChannelContextFactory;
        $this->ruleLoader = $ruleLoader;
    }

    public function setConfigProvider(ConfigProvider $configProvider): void
    {
        $this->configProvider = $configProvider;
    }

    public function importOrder(array $orderItem, SalesChannelEntity $salesChannelEntity): string
    {
        $orderItem = $this->eventDispatcher
            ->dispatch(new IdealoOrderItemFetchedEvent($orderItem, $salesChannelEntity))
            ->getOrderItem()
        ;

        foreach ($orderItem['lineItems'] as &$item) {
            if (self::DEBUG) {
                $item['sku'] = self::DEBUG_ITEM;
            }
            $product = $this->dataProvider->getProductByProductNumber($item['sku']);

            if (!$product) {
                $error = sprintf(
                    'Could not find product for productNumber %s in order %s',
                    $item['sku'],
                    $orderItem['idealoOrderId']
                );
                $this->logger->critical($error);
                throw new \Exception($error);
            }

            $item['product'] = $product;
        }

        $customer = $this->createGuestCustomer($orderItem, $salesChannelEntity);
        $order = $this->createOrderMainData($orderItem, $customer, $salesChannelEntity);
        $orderEntity = $this->dataProvider->getOrderById($order['id']);

        $token = Uuid::randomHex();
        $salesChannelContext = $this->salesChannelContextFactory->create(
            $token,
            $salesChannelEntity->getId(),
            [
                SalesChannelContextService::LANGUAGE_ID       => $salesChannelEntity->getLanguageId(),
                SalesChannelContextService::CUSTOMER_ID       => $customer['id'],
                SalesChannelContextService::CUSTOMER_GROUP_ID => $this->configProvider->getDefaultCustomerGroup(),
            ]
        );

        $this->ruleLoader->loadByToken($salesChannelContext, $token);
        if ($this->configProvider->isSendMail()) {
            if (null !== $orderEntity) {
                $orderPlacedEvent = new CheckoutOrderPlacedEvent(
                    $salesChannelContext->getContext(),
                    $orderEntity,
                    $salesChannelEntity->getId()
                );

                $this->eventDispatcher->dispatch($orderPlacedEvent);
            }
        }

        $eventName = 'state_enter.order_transaction.state.paid';
        $orderTransactionStateChangedEvent = new OrderStateMachineStateChangeEvent(
            $eventName,
            $orderEntity,
            $salesChannelContext->getContext()
        );

        $this->eventDispatcher->dispatch($orderTransactionStateChangedEvent, $eventName);

        return $order['orderNumber'];
    }

    private function createOrderMainData(array $orderItem, array $customer, SalesChannelEntity $salesChannelEntity): array
    {
        $orderId = Uuid::randomHex();
        $orderAddress = $this->getAddressData($orderItem['billingAddress'], $orderItem['customer'], $orderId, $customer['id']);
        $country = $this->dataProvider->getCountryByIso($orderItem['billingAddress']['countryCode']);

        $addresses = [$orderAddress];
        $shippingAddress = null;
        if (json_encode($orderItem['billingAddress']) !== json_encode($orderItem['shippingAddress'])) {
            $shippingAddress = $this->getAddressData($orderItem['shippingAddress'], $orderItem['customer'], $orderId, $customer['id']);
            $addresses[] = $shippingAddress;
        }

        $totalPrice = 0;
        $totalPriceNet = 0;
        $taxRate = 0;
        if (!$country->getTaxFree()) {
            foreach ($orderItem['lineItems'] as $lineItem) {
                $taxRate = $this->getProductTaxRate($lineItem['product'], $country);
                $totalPrice += $lineItem['price'] * $lineItem['quantity'];
                $totalPriceNet += ($lineItem['price'] * $lineItem['quantity']) / (1 + ($taxRate / 100));
            }
        }

        $shippingCosts = $this->getShippingCosts($orderItem, $taxRate);
        $orderNumber = $this->valueGenerator->getValue('order', $this->context, $salesChannelEntity->getId(), false);
        $orderNumber = $this->eventDispatcher
            ->dispatch(new IdealoOrderNumberGeneratedEvent($orderNumber, $salesChannelEntity))
            ->getOrderNumber()
        ;

        $languageId = null !== $salesChannelEntity->getLanguage()
            ? $salesChannelEntity->getLanguage()->getId()
            : null;

        if (null === $languageId) {
            $languageId = $this->configProvider->getFallbackLanguage();
        }

        $lineItems = $this->getOrderLineItems($orderItem, $salesChannelEntity);
        $currency = $this->dataProvider->getCurrencyByIso($orderItem['currency']);

        $order = [
            'id'               => $orderId,
            'salesChannelId'   => $salesChannelEntity->getId(),
            'deepLinkCode'     => Random::getAlphanumericString(32),
            'languageId'       => $languageId,
            'currencyId'       => $salesChannelEntity->getCurrency()->getId(),
            'orderDateTime'    => $this->getBerlinTime($orderItem['created'])->format('Y-m-d H:i:s'),
            'currencyFactor'   => $salesChannelEntity->getCurrency()->getFactor(),
            'billingAddressId' => $orderAddress['id'],
            'price'            => $this->getOrderPriceData(
                $orderItem,
                $country,
                $taxRate,
                $totalPrice,
                $totalPriceNet,
                $shippingCosts
            ),
            'shippingCosts'    => $shippingCosts,
            'orderNumber'      => $orderNumber,
            'lineItems'        => $lineItems,
            'addresses'        => $addresses,
            'deliveries'       => $this->getOrderDelivery(
                $orderId,
                $orderItem,
                $lineItems,
                $orderAddress,
                $shippingCosts,
                $shippingAddress
            ),
            'transactions'     => $this->getOrderTransaction($orderId, $orderItem, $totalPrice, $totalPriceNet),
            'stateId'          => \in_array($orderItem['status'], ['PROCESSING', 'COMPLETED'])
                ? $this->configProvider->getOrderState()
                : $this->configProvider->getOrderCancellationState(),
            'orderCustomer'    => $customer,
            'itemRounding'     => $currency->getItemRounding()->jsonSerialize(),
            'totalRounding'    => $currency->getTotalRounding()->jsonSerialize(),
        ];

        $orderItem = $this->eventDispatcher
            ->dispatch(new IdealoOrderBeforeSaveEvent($orderItem, $salesChannelEntity))
            ->getOrderItem()
        ;
        $this->dataPersister->createOrder($order);
        $this->dataPersister->createIdealoTransactionId($orderId, $orderItem['idealoOrderId']);
        $this->eventDispatcher->dispatch(new IdealoOrderSavedEvent($orderId, $orderItem, $salesChannelEntity));

        return $order;
    }

    private function getOrderLineItems(array $orderItem, SalesChannelEntity $salesChannelEntity): array
    {
        $lineItems = [];
        $country = $this->dataProvider->getCountryByIso($orderItem['billingAddress']['countryCode']);
        foreach ($orderItem['lineItems'] as $item) {
            $product = $item['product'];
            $orderLineItemId = Uuid::randomHex();

            $lineItem = [
                'id'              => $orderLineItemId,
                'productId'       => $product->getId(),
                'identifier'      => $product->getId(),
                'referencedId'    => $product->getId(),
                'price'           => $this->getLineItemPriceData($item, $country),
                'priceDefinition' => $this->getLineItemPriceDefinition($item, $country),
                'unitPrice'       => $item['price'],
                'totalPrice'      => $item['price'] * $item['quantity'],
                'quantity'        => $item['quantity'],
                'label'           => !empty($item['title']) ? $item['title'] : $product->getName(),
                'type'            => 'product',
                'payload'         => [
                    'productNumber' => $product->getProductNumber(),
                    'options'       => [], // needed cause shopware doesnt load productname otherwise
                ],
            ];

            $lineItem = $this->eventDispatcher
                ->dispatch(new IdealoOrderLineItemBeforeSaveEvent($lineItem, $salesChannelEntity))
                ->getOrderLineItem()
            ;

            $lineItems[] = $lineItem;
        }

        return $lineItems;
    }

    private function createGuestCustomer(array $orderItem, SalesChannelEntity $salesChannelEntity): array
    {
        $billingAddress = $this->getAddressData($orderItem['billingAddress'], $orderItem['customer'], 'billing');
        $shippingAddress = $this->getAddressData($orderItem['shippingAddress'], $orderItem['customer'], 'shipping');
        $customerId = Uuid::randomHex();
        $customerNumber = $this->valueGenerator->getValue('customer', $this->context, $salesChannelEntity->getId(), false);

        $customerNumber = $this->eventDispatcher
            ->dispatch(new IdealoCustomerNumberGeneratedEvent($customerNumber, $salesChannelEntity))
            ->getCustomerNumber()
        ;

        $customer = [
            'id'                     => $customerId,
            'salesChannelId'         => $salesChannelEntity->getId(),
            'languageId'             => $salesChannelEntity->getLanguage()->getId(),
            'email'                  => $orderItem['customer']['email'],
            'company'                => $billingAddress['company'],
            'guest'                  => true,
            'groupId'                => $this->configProvider->getDefaultCustomerGroup(),
            'customerNumber'         => $customerNumber,
            'defaultBillingAddress'  => $billingAddress,
            'defaultShippingAddress' => $shippingAddress,
            'defaultPaymentMethodId' => $this->getPaymentIdByMethod($orderItem['payment']['paymentMethod']),
            'firstName'              => $billingAddress['firstName'],
            'lastName'               => $billingAddress['lastName'],
            'salutationId'           => $billingAddress['salutationId'],
        ];

        $customer = $this->eventDispatcher
            ->dispatch(new IdealoCustomerBeforeSaveEvent($customer, $salesChannelEntity))
            ->getCustomer()
        ;

        $this->dataPersister->createCustomer($customer);
        $this->eventDispatcher->dispatch(new IdealoCustomerSavedEvent($customerId, $customer, $salesChannelEntity));

        $customer['customerId'] = $customer['id'];

        return $customer;
    }

    private function getAddressData(
        array $address,
        array $customer,
        ?string $orderId = null,
        ?string $customerId = null
    ): array
    {
        $countryId = $this->dataProvider->getCountryByIso($address['countryCode'])->getId();
        $address['salutation'] = $this->convertSalutation($address['salutation'] ?? '');
        $emptyPhonePlaceholder = $this->configProvider->isUseEmptyPhone() ? '' : 'no number';

        if ($this->configProvider->isUseAddressTwoAsCompany()) {
            $company = $address['addressLine2'] ?? '';
            $addAddressLine = '';
        } else {
            $company = '';
            $addAddressLine = $address['addressLine2'] ?? '';
        }

        $addressData = [
            'id'                     => Uuid::randomHex(),
            'company'                => $company,
            'salutationId'           => $address['salutation'],
            'firstName'              => $address['firstName'],
            'lastName'               => $address['lastName'],
            'street'                 => $address['addressLine1'],
            'zipcode'                => $address['postalCode'],
            'city'                   => $address['city'],
            'countryId'              => $countryId,
            'additionalAddressLine1' => $addAddressLine,
            'phoneNumber'            => $customer['phone'] ?? $emptyPhonePlaceholder,
        ];

        if ($orderId) {
            $addressData['orderId'] = $orderId;
        } else {
            $addressData['customerId'] = $customerId;
        }

        return $addressData;
    }

    private function getPaymentIdByMethod(string $paymentMethod): string
    {
        switch ($paymentMethod) {
            case 'SOFORT':
                $paymentId = $this->configProvider->getInstantTransferPaymentType();
                break;
            case 'CREDITCARD':
                $paymentId = $this->configProvider->getCreditcardType();
                break;
            case OttIdealoConnector::TITLE_IDEALO_PAYMENT:
                $paymentId = $this->dataProvider->getPaymentMethodIdByName(OttIdealoConnector::TITLE_IDEALO_PAYMENT);
                break;
            case 'PAYPAL':
            default:
                $paymentId = $this->configProvider->getPaypalPaymentType();
        }

        return $paymentId;
    }

    private function mapDispatchId(array $orderItem): string
    {
        switch ($orderItem['fulfillment']['method']) {
            default:
            case 'POSTAL':
                return $this->configProvider->getDispatchTypePostal();
            case 'LETTER':
                return $this->configProvider->getDispatchTypeLetter();
            case 'DOWNLOAD':
                return $this->configProvider->getDispatchTypeDownload();
            case 'FORWARDING':
                $twoMenDelivery = false;
                $pickupService = false;
                if (!empty($orderItem['fulfillment']['options'])) {
                    foreach ($orderItem['fulfillment']['options'] as $option) {
                        if (isset($option['forwardOption']) && 'TWO_MAN_DELIVERY' === $option['forwardOption']) {
                            $twoMenDelivery = true;
                        } elseif (isset($option['forwardOption']) && 'PICKUP_SERVICE' === $option['forwardOption']) {
                            $pickupService = true;
                        }
                    }
                }

                if ($twoMenDelivery && $pickupService) {
                    return $this->configProvider->getDispatchTypeForwardingTwoMen();
                }
                if ($twoMenDelivery) {
                    return $this->configProvider->getDispatchTypeForwardingTwoMen();
                }
                if ($pickupService) {
                    return $this->configProvider->getDispatchTypeForwardingPickup();
                }

                return $this->configProvider->getDispatchTypeForwarding();
        }
    }

    private function convertSalutation(string $salutation): string
    {
        $salutation = str_replace('ms', 'mrs', strtolower($salutation));

        if (empty($salutation) || 'none' === $salutation) {
            return $this->configProvider->getDefaultSalutation();
        }

        $salutationEntity = $this->dataProvider->getSalutationByKey($salutation);

        if (null === $salutationEntity) {
            return $this->configProvider->getDefaultSalutation();
        }

        return $this->dataProvider->getSalutationByKey($salutation)->getId();
    }

    private function getProductTaxRate(ProductEntity $product, CountryEntity $country): float
    {
        if (null === $product->getTax()) {
            $parentProduct = $this->dataProvider->getProductById($product->getParentId());
            $tax = $parentProduct->getTax();
        } else {
            $tax = $product->getTax();
        }

        $productTaxRules = $tax->getRules();
        if (null !== $productTaxRules) {
            foreach ($productTaxRules as $taxRule) {
                if ($taxRule->getCountry()->getId() === $country->getId()) {
                    return $taxRule->getTaxRate();
                }
            }
        }

        if ($country->getTaxFree()) {
            return 0.0;
        }

        return $product->getTax()->getTaxRate();
    }

    private function getDeliveryDates(array $lineItems, string $shippingMethodId): array
    {
        $shippingMethod = $this->dataProvider->getShippingMethod($shippingMethodId);
        $deliveryTime = DeliveryTime::createFromEntity($shippingMethod->getDeliveryTime());
        $deliveryDate = DeliveryDate::createFromDeliveryTime($deliveryTime);
        $earliest = $deliveryDate->getEarliest();
        $latest = $deliveryDate->getLatest();
        foreach ($lineItems as $lineItem) {
            if (!$lineItem['product']->getDeliveryTime()) {
                continue;
            }

            $deliveryTime = DeliveryDate::createFromDeliveryTime($lineItem['product']->getDeliveryTime());

            $earliest = $earliest > $deliveryTime->getEarliest() ? $earliest : $deliveryTime->getEarliest();
            $latest = $latest > $deliveryTime->getLatest() ? $latest : $deliveryTime->getLatest();
        }

        return [$earliest, $latest];
    }

    private function getOrderPriceData(
        array $orderItem,
        CountryEntity $country,
        float $taxRate,
        float $totalPrice,
        float $totalPriceNet,
        array $shippingCosts
    ): array
    {
        return [
            'taxStatus'     => $country->getTaxFree() ? 'net' : 'gross',
            'taxRules'      => [
                [
                    'taxRate'    => $taxRate,
                    'extensions' => [],
                    'percentage' => 100,
                ],
            ],
            'rawTotal'        => 0 < $totalPrice ? $totalPrice + $shippingCosts['unitPrice'] : $orderItem['grossPrice'],
            'totalPrice'      => 0 < $totalPrice ? $totalPrice + $shippingCosts['unitPrice'] : $orderItem['grossPrice'],
            'positionPrice'   => 0 < $totalPrice ? $totalPrice : $orderItem['offersPrice'],
            'netPrice'        => 0 < $totalPriceNet ? $totalPriceNet + ($shippingCosts['unitPrice'] - $shippingCosts['calculatedTaxes'][0]['tax']) : $orderItem['grossPrice'],
            'calculatedTaxes' => [
                [
                    'tax'        => 0 < $totalPrice ? ($totalPrice - $totalPriceNet) + $shippingCosts['calculatedTaxes'][0]['tax'] : 0,
                    'price'      => $totalPrice,
                    'taxRate'    => $taxRate,
                    'extensions' => [],
                ],
            ],
        ];
    }

    private function getLineItemPriceDefinition(array $item, CountryEntity $country): array
    {
        $itemTaxRate = $this->getProductTaxRate($item['product'], $country);

        return [
            'type'                           => 'quantity',
            'price'                          => $item['price'],
            'quantity'                       => $item['quantity'],
            'listPrice'                      => null,
            'isCalculated'                   => true,
            'referencePriceDefinition'       => null,
            'taxRules'                       => [
                [
                    'taxRate'    => $itemTaxRate,
                    'extensions' => [],
                    'percentage' => 100,
                ],
            ],
        ];
    }

    private function getLineItemPriceData(array $item, CountryEntity $country): array
    {
        $itemTaxRate = $this->getProductTaxRate($item['product'], $country);
        $itemTax = ($item['price'] * $item['quantity']) / (1 + ($itemTaxRate / 100));

        return [
            'quantity'        => $item['quantity'],
            'listPrice'       => null,
            'taxRules'        => [
                [
                    'taxRate'    => $itemTaxRate,
                    'extensions' => [],
                    'percentage' => 100,
                ],
            ],
            'unitPrice'       => $item['price'],
            'totalPrice'      => $item['price'] * $item['quantity'],
            'referencePrice'  => null,
            'calculatedTaxes' => [
                [
                    'tax'        => ($item['price'] * $item['quantity']) - $itemTax,
                    'price'      => $item['price'] * $item['quantity'],
                    'taxRate'    => $itemTaxRate,
                    'extensions' => [],
                ],
            ],
        ];
    }

    private function getOrderTransaction(string $orderId, array $orderItem, float $totalPrice, float $totalPriceNet): array
    {
        return [
            [
                'orderId'         => $orderId,
                'amount'          => [
                    'unitPrice'  => 0 < $totalPrice ? $totalPrice : $orderItem['offerPrice'],
                    'totalPrice' => 0 < $totalPrice ? $totalPrice : $orderItem['offerPrice'],
                    'quantity'   => 1,
                    'taxRules'   => [
                        [
                            'taxRate'    => 19,
                            'extensions' => [],
                            'percentage' => 100,
                        ],
                    ],
                    'calculatedTaxes' => [
                        [
                            'tax'        => 0 < $totalPrice ? $totalPrice - $totalPriceNet : 0,
                            'price'      => 0 < $totalPrice ? $totalPrice : $orderItem['offerPrice'],
                            'taxRate'    => 19,
                            'extensions' => [],
                        ],
                    ],
                ],
                'paymentMethodId' => $this->getPaymentIdByMethod($orderItem['payment']['paymentMethod']),
                'stateId'         => $this->configProvider->getPaymentState(),
                'customFields'    => $this->getTransactionCustomFields($orderItem),
            ],
        ];
    }

    private function getTransactionCustomFields(array $orderItem): array
    {
        $customFields = [
            'custom_idealo_transaction_id' => $orderItem['payment']['transactionId'],
        ];
        if ('PAYPAL' === $orderItem['payment']['paymentMethod']) {
            $customFields['swag_paypal_transaction_id'] = $orderItem['payment']['transactionId'];
        }

        return $customFields;
    }

    private function getOrderDelivery(string $orderId, array $orderItem, array $orderLineItems, array $orderAddress, array $shippingCosts, ?array $shippingAddress): array
    {
        [$earliest, $latest] = $this->getDeliveryDates($orderItem['lineItems'], $this->configProvider->getDispatchTypePostal());

        return [
            [
                'orderId'                => $orderId,
                'stateId'                => $this->configProvider->getDeliveryState(),
                'shippingOrderAddressId' => isset($shippingAddress)
                    ? $shippingAddress['id']
                    : $orderAddress['id'],
                'shippingMethodId'       => $this->mapDispatchId($orderItem),
                'shippingDateEarliest'   => $earliest,
                'shippingDateLatest'     => $latest,
                'shippingCosts'          => $shippingCosts,
                'positions'              => $this->getOrderPositions($orderLineItems),
            ],
        ];
    }

    private function getOrderPositions(array $orderLineItems): array
    {
        $positions = [];
        foreach ($orderLineItems as $orderLineItem) {
            $orderLineItem['orderLineItemId'] = $orderLineItem['id'];
            unset($orderLineItem['id']);
            $positions[] = $orderLineItem;
        }

        return $positions;
    }

    private function getShippingCosts(array $orderItem, float $taxRate): array
    {
        return [
            'quantity' => 1,
            'taxRules' => [
                [
                    'taxRate'    => $taxRate,
                    'extensions' => [],
                    'percentage' => 100,
                ],
            ],
            'listPrice'       => null,
            'unitPrice'       => $orderItem['shippingCosts'],
            'totalPrice'      => $orderItem['shippingCosts'],
            'referencePrice'  => null,
            'calculatedTaxes' => [
                [
                    'tax'        => $orderItem['shippingCosts'] - ($orderItem['shippingCosts'] / (1 + ($taxRate / 100))),
                    'price'      => $orderItem['shippingCosts'],
                    'taxRate'    => $taxRate,
                    'extensions' => [],
                ],
            ],
        ];
    }

    private function getBerlinTime(string $timeString): \DateTime
    {
        $originalSendTime = new \DateTime($timeString);
        if (preg_match('/\+(.*)/', $timeString, $result)) {
            $timezone = $result[1];
            $timeDifference = ((int) $timezone) - 1;
            $originalSendTime->modify(sprintf('-%s hours', $timeDifference));
        } elseif (preg_match('/000-(.*)/', $timeString, $result)) {
            $timezone = $result[1];
            $timeDifference = ((int) $timezone) + 1;
            $originalSendTime->modify(sprintf('+%s hours', $timeDifference));
        } elseif (preg_match('/Z/', $timeString, $result)) {
            $originalSendTime->modify(sprintf('+%s hours', $this->isGermanWinterTime() ? 1 : 2));
        }

        if (!empty($this->configProvider->getAdjustOrderTime())) {
            $cleanedAdjustedTime = preg_replace('/[^0-9+-]/i', '', $this->configProvider->getAdjustOrderTime());
            if (
                false === strpos($cleanedAdjustedTime, '+')
                && false === strpos($cleanedAdjustedTime, '-')
            ) {
                $cleanedAdjustedTime = '+' . $cleanedAdjustedTime;
            }
            if ('+' !== trim($cleanedAdjustedTime)) {
                $originalSendTime->modify(sprintf('%s hours', $cleanedAdjustedTime));
            }
        }

        return $originalSendTime;
    }

    private function isGermanWinterTime(): bool
    {
        $now = (float) date('n.d');
        if (10.27 <= $now || 3.29 >= $now) {
            return true;
        }

        return false;
    }
}
