<?php


namespace Ott\IdealoConnector\Test\Service;

use Ott\IdealoConnector\Service\ConfigProvider;
use Ott\IdealoConnector\Service\OrderImportService;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerGroup\CustomerGroupEntity;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Checkout\Shipping\ShippingMethodEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Test\TestCaseHelper\ReflectionHelper;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\Country\CountryEntity;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Shopware\Core\System\Salutation\SalutationEntity;

class OrderImportServiceTest extends TestCase
{
    use IntegrationTestBehaviour;

    private array $orderItem;
    private OrderImportService $orderImportService;
    private Context $context;
    private SalesChannelEntity $salesChannel;
    private CustomerGroupEntity $customerGroup;
    private PaymentMethodEntity $paymentMethod;
    private SalutationEntity $salutation;
    private ShippingMethodEntity $shippingMethod;
    private ConfigProvider $configProvider;

    public function setUp(): void
    {
        parent::setUp();

        $this->orderImportService = new OrderImportService(
            $this->getContainer()->get('Ott\IdealoConnector\Dbal\DataProvider'),
            $this->getContainer()->get('Ott\IdealoConnector\Dbal\DataPersister'),
            $this->getContainer()->get('Shopware\Core\System\NumberRange\ValueGenerator\NumberRangeValueGeneratorInterface'),
            $this->getContainer()->get('event_dispatcher'),
            $this->getContainer()->get('Shopware\Core\Checkout\Cart\CartRuleLoader'),
            $this->getContainer()->get('ott_idealo_connector.logger'),
            $this->getContainer()->get('Shopware\Core\System\SalesChannel\Context\SalesChannelContextFactory')
        );

        $this->context          = Context::createDefaultContext();
        $salesChannelRepository = $this->getContainer()->get('sales_channel.repository');
        $this->salesChannel     = $salesChannelRepository->search(
            (new Criteria())
                ->addFilter(new EqualsFilter('name', 'Storefront'))
                ->addAssociations(['language', 'currency']),
            $this->context
        )->getEntities()->first();

        $productRepository = $this->getContainer()->get('product.repository');
        $product = $productRepository->search(
            (new Criteria())->addFilter(new EqualsFilter('productNumber', 'SWDEMO10001')),
            $this->context
        )->getEntities()->first();

        $customerGroupRepository = $this->getContainer()->get('customer_group.repository');
        $this->customerGroup = $customerGroupRepository->search(new Criteria(), $this->context)->getEntities()->first();

        $paymentMethodRepository = $this->getContainer()->get('payment_method.repository');
        $this->paymentMethod = $paymentMethodRepository->search(new Criteria(), $this->context)->getEntities()->first();

        $salutationRepository = $this->getContainer()->get('salutation.repository');
        $this->salutation = $salutationRepository->search((new Criteria())->addFilter(new EqualsFilter('salutationKey', 'mrs')), $this->context)->getEntities()->first();

        $shippingMethodRepository = $this->getContainer()->get('shipping_method.repository');
        $this->shippingMethod = $shippingMethodRepository->search(new Criteria(), $this->context)->getEntities()->first();

        $stateMachineRepository = $this->getContainer()->get('state_machine.repository');
        $stateMachineOrderState = $stateMachineRepository->search((new Criteria())->addFilter(new EqualsFilter('technicalName', 'order.state')), $this->context)->getEntities()->first();
        $stateMachineOrderDeliveryState = $stateMachineRepository->search((new Criteria())->addFilter(new EqualsFilter('technicalName', 'order_delivery.state')), $this->context)->getEntities()->first();
        $stateMachineOrderTransactionState = $stateMachineRepository->search((new Criteria())->addFilter(new EqualsFilter('technicalName', 'order_transaction.state')), $this->context)->getEntities()->first();
        $stateMachineStateRepository = $this->getContainer()->get('state_machine_state.repository');
        $orderState = $stateMachineStateRepository->search(
            (new Criteria())
                ->addFilter(new MultiFilter(
                        'AND',
                        [
                            new EqualsFilter('stateMachineId', $stateMachineOrderState->getId()),
                            new EqualsFilter('name', 'Open'),
                        ]
                    )
                ),
            $this->context
        )->getEntities()->first();
        $deliveryState = $stateMachineStateRepository->search(
            (new Criteria())
                ->addFilter(new MultiFilter(
                    'AND',
                    [
                        new EqualsFilter('stateMachineId', $stateMachineOrderDeliveryState->getId()),
                        new EqualsFilter('name', 'Open'),
                    ]
                )
            ),
            $this->context
        )->getEntities()->first();
        $transactionState = $stateMachineStateRepository->search(
            (new Criteria())
                ->addFilter(new MultiFilter(
                        'AND',
                        [
                            new EqualsFilter('stateMachineId', $stateMachineOrderTransactionState->getId()),
                            new EqualsFilter('name', 'Open'),
                        ]
                    )
                ),
            $this->context
        )->getEntities()->first();

        /** @var ConfigProvider $configProvider */
        $this->configProvider = $this->getContainer()->get(ConfigProvider::class);
        $this->configProvider->init($this->salesChannel->getId());
        $this->configProvider->setDefaultCustomerGroup($this->customerGroup->getId())
        ->setDispatchTypeDownload($this->shippingMethod->getId())
        ->setDispatchTypePostal($this->shippingMethod->getId())
        ->setDispatchTypeLetter($this->shippingMethod->getId())
        ->setDispatchTypeForwarding($this->shippingMethod->getId())
        ->setDispatchTypeForwardingPickup($this->shippingMethod->getId())
        ->setDispatchTypeForwardingTwoMen($this->shippingMethod->getId())
        ->setDispatchTypeForwardingTwoMenPickup($this->shippingMethod->getId())
        ->setDefaultSalutation($this->salutation->getId())
        ->setCreditcardType($this->paymentMethod->getId())
        ->setInstantTransferPaymentType($this->paymentMethod->getId())
        ->setPaypalPaymentType($this->paymentMethod->getId())
        ->setDeliveryState($deliveryState->getId())
        ->setOrderState($orderState->getId())
        ->setOrderCancellationState($orderState->getId())
        ->setOrderCompletionState($orderState->getId())
        ->setOrderPartialCancellationState($orderState->getId())
        ->setPaymentState($transactionState->getId())
        ->setIsSandboxMode(true)
        ->setIsDebugMode(false)
        ->setIsSendMail(false);


        $this->orderImportService->setConfigProvider($this->configProvider);
        $this->orderItem = [
            'idealoOrderId' => 'BWXXKXW2',
            'created' => '2019-10-15T05:56:58Z',
            'updated'=> '2019-10-15T05:56:57.731Z',
            'status'=> 'PROCESSING',
            'currency' => 'EUR',
            'offersPrice'=> '146.38',
            'grossPrice'=> '151.37',
            'shippingCosts'=>'4.99',
            'lineItems' => [
                [
                    'title' => 'Amica KMC 13281 C Rahmenlos Autarkes Kochfeld, Glaskeramik, 30 cm',
                    'price' => '146.38',
                    'quantity'=> 1,
                    'sku' => 'SWDEMO10001',
                    'merchantName' => 'Test Händler',
                    'merchantDeliveryText' => '1-2+Werktage',
                    'product' => $product,
                ]
            ],
            'customer'=> [
                'email' => 'm-mtf9n9uho6u8bh0p@checkout-stg.idealo.de',
                'phone' => '+49 30 1234 5678'
            ],
            'payment'=> [
                'paymentMethod' => 'SOFORT',
                'transactionId' => 'snakeoil-9028b78'
            ],
            'billingAddress' => [
                'salutation' => 'MRS',
                'firstName' => 'Patricia',
                'lastName' => 'Xiao-Chu',
                'addressLine1' => 'Straße 85',
                'addressLine2' => 'Hinterhof 3',
                'postalCode' => '01852',
                'city' => 'Ort',
                'countryCode' => 'DE',
            ],
            'shippingAddress' => [
                'salutation' => 'MRS',
                'firstName' => 'Patricia',
                'lastName' => 'Xiao-Chu',
                'addressLine1' => 'Straße 85',
                'addressLine2' => 'Hinterhof 3',
                'postalCode' => '01852',
                'city' => 'Ort',
                'countryCode'=> 'DE'
            ],
            'fulfillment' =>
            [
                'method' => 'FORWARDING',
                'tracking' => [],
                'options' => []
            ],
            'refunds' => [],
            'vouchers' => [
                'code' => 'some-voucher-code'
            ],
        ];
    }

    public function testCreateOrderMainData()
    {
        $reflectionMethod = ReflectionHelper::getMethod(OrderImportService::class, 'createOrderMainData');
        $reflectionMethodCustomer = ReflectionHelper::getMethod(OrderImportService::class, 'createGuestCustomer');
        $customer = $reflectionMethodCustomer->invokeArgs($this->orderImportService, [$this->orderItem, $this->salesChannel]);
        $result = $reflectionMethod->invokeArgs($this->orderImportService, [$this->orderItem, $customer, $this->salesChannel]);

        $this->assertTrue(is_array($result));
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('salesChannelId', $result);
        $this->assertArrayHasKey('languageId', $result);
        $this->assertArrayHasKey('currencyId', $result);
        $this->assertArrayHasKey('orderDateTime', $result);
        $this->assertArrayHasKey('currencyFactor', $result);
        $this->assertArrayHasKey('billingAddressId', $result);
        $this->assertArrayHasKey('price', $result);
        $this->assertArrayHasKey('shippingCosts', $result);
        $this->assertArrayHasKey('orderNumber', $result);
        $this->assertArrayHasKey('addresses', $result);
        $this->assertArrayHasKey('deliveries', $result);
        $this->assertArrayHasKey('transactions', $result);
        $this->assertArrayHasKey('stateId', $result);
        $this->assertArrayHasKey('orderCustomer', $result);
        $this->assertNotEmpty($result['id']);
        $this->assertNotEmpty($result['salesChannelId']);
        $this->assertNotEmpty($result['languageId']);
        $this->assertNotEmpty($result['currencyId']);
        $this->assertNotEmpty($result['orderDateTime']);
        $this->assertNotEmpty($result['currencyFactor']);
        $this->assertNotEmpty($result['billingAddressId']);
        $this->assertNotEmpty($result['price']);
        $this->assertNotEmpty($result['shippingCosts']);
        $this->assertNotEmpty($result['orderNumber']);
        $this->assertNotEmpty($result['addresses']);
        $this->assertNotEmpty($result['deliveries']);
        $this->assertNotEmpty($result['transactions']);
        $this->assertNotEmpty($result['stateId']);
        $this->assertNotEmpty($result['orderCustomer']);
    }

    public function testImportOrder()
    {
        $this->assertEquals('10000', $this->orderImportService->importOrder($this->orderItem, $this->salesChannel));
    }

    public function testCreateOrderLineItems()
    {
        $reflectionMethod = ReflectionHelper::getMethod(OrderImportService::class, 'getOrderLineItems');
        $reflectionMethodOrder = ReflectionHelper::getMethod(OrderImportService::class, 'createOrderMainData');
        $reflectionMethodCustomer = ReflectionHelper::getMethod(OrderImportService::class, 'createGuestCustomer');
        $customer  = $reflectionMethodCustomer->invokeArgs($this->orderImportService, [$this->orderItem, $this->salesChannel]);
        $order     = $reflectionMethodOrder->invokeArgs($this->orderImportService, [$this->orderItem, $customer, $this->salesChannel]);
        $lineItems = $reflectionMethod->invokeArgs($this->orderImportService, [$this->orderItem, $this->salesChannel]);
        $lineItem  = $lineItems[0];

        $this->assertEquals(146.38, $lineItem['unitPrice']);
        $this->assertEquals(1, $lineItem['quantity']);
        $this->assertEquals(146.38, $lineItem['totalPrice']);
        $this->assertEquals('product', $lineItem['type']);
        $this->assertEquals('Amica KMC 13281 C Rahmenlos Autarkes Kochfeld, Glaskeramik, 30 cm', $lineItem['label']);
    }

    public function testCreateGuestCustomer()
    {
        $reflectionMethod = ReflectionHelper::getMethod(OrderImportService::class, 'createGuestCustomer');
        $result = $reflectionMethod->invokeArgs($this->orderImportService, [$this->orderItem, $this->salesChannel]);
        $this->assertTrue(is_array($result));
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('salesChannelId', $result);
        $this->assertArrayHasKey('languageId', $result);
        $this->assertArrayHasKey('email', $result);
        $this->assertArrayHasKey('company', $result);
        $this->assertArrayHasKey('guest', $result);
        $this->assertArrayHasKey('groupId', $result);
        $this->assertArrayHasKey('customerNumber', $result);
        $this->assertArrayHasKey('defaultBillingAddress', $result);
        $this->assertArrayHasKey('defaultShippingAddress', $result);
        $this->assertArrayHasKey('defaultPaymentMethodId', $result);
        $this->assertArrayHasKey('firstName', $result);
        $this->assertArrayHasKey('lastName', $result);
        $this->assertArrayHasKey('salutationId', $result);
        $this->assertNotEmpty($result['id']);
        $this->assertNotEmpty($result['salesChannelId']);
        $this->assertNotEmpty($result['languageId']);
        $this->assertNotEmpty($result['email']);
        $this->assertEmpty($result['company']);
        $this->assertNotEmpty($result['guest']);
        $this->assertNotEmpty($result['groupId']);
        $this->assertNotEmpty($result['customerNumber']);
        $this->assertNotEmpty($result['defaultBillingAddress']);
        $this->assertNotEmpty($result['defaultShippingAddress']);
        $this->assertNotEmpty($result['defaultPaymentMethodId']);
        $this->assertNotEmpty($result['firstName']);
        $this->assertNotEmpty($result['lastName']);
        $this->assertNotEmpty($result['salutationId']);
    }

    public function testGetAddressData()
    {
        $customerId = Uuid::randomHex();
        $reflectionMethod = ReflectionHelper::getMethod(OrderImportService::class, 'getAddressData');
        $countryRepository = $this->getContainer()->get('country.repository');
        $countryDe = $countryRepository->search((new Criteria())->addFilter(new EqualsFilter('iso', 'DE')), $this->context)->getEntities()->first();
        $result = $reflectionMethod->invokeArgs($this->orderImportService, [$this->orderItem['billingAddress'], $this->orderItem['customer'], null, $customerId]);
        unset($result['id']);
        $this->assertTrue(is_array($result));
        $this->assertEquals(
            [
                'company'                => '',
                'salutationId'           => $this->salutation->getId(),
                'firstName'              => 'Patricia',
                'lastName'               => 'Xiao-Chu',
                'street'                 => 'Straße 85',
                'zipcode'                => '01852',
                'city'                   => 'Ort',
                'countryId'              => $countryDe->getId(),
                'additionalAddressLine1' => 'Hinterhof 3',
                'phoneNumber'            => '+49 30 1234 5678',
                'customerId'             => $customerId
            ],
            $result
        );

        $result = $reflectionMethod->invokeArgs($this->orderImportService, [$this->orderItem['billingAddress'], $this->orderItem['customer'], $customerId, null]);
        unset($result['id']);
        $this->assertTrue(is_array($result));
        $this->assertEquals(
            [
                'company'                => '',
                'salutationId'           => $this->salutation->getId(),
                'firstName'              => 'Patricia',
                'lastName'               => 'Xiao-Chu',
                'street'                 => 'Straße 85',
                'zipcode'                => '01852',
                'city'                   => 'Ort',
                'countryId'              => $countryDe->getId(),
                'additionalAddressLine1' => 'Hinterhof 3',
                'phoneNumber'            => '+49 30 1234 5678',
                'orderId'                => $customerId
            ],
            $result
        );
    }

    public function testGetProductTaxRate()
    {
        $reflectionMethod = ReflectionHelper::getMethod(OrderImportService::class, 'getProductTaxRate');
        $countryRepository = $this->getContainer()->get('country.repository');
        $countryDe = $countryRepository->search((new Criteria())->addFilter(new EqualsFilter('iso', 'DE')), $this->context)->getEntities()->first();
        $this->assertEquals(19, $reflectionMethod->invokeArgs($this->orderImportService, [$this->orderItem['lineItems'][0]['product'], $countryDe]));
        $countryCh = $countryRepository->search((new Criteria())->addFilter(new EqualsFilter('iso','CH')), $this->context)->getEntities()->first();
        $this->assertEquals(0.0, $reflectionMethod->invokeArgs($this->orderImportService, [$this->orderItem['lineItems'][0]['product'], $countryCh]));
    }

    public function testGetDeliveryDates()
    {
        $reflectionMethod = ReflectionHelper::getMethod(OrderImportService::class, 'getDeliveryDates');

        $earliest = (new \DateTime())->add(new \DateInterval('P1D'));
        $latest   = (new \DateTime())->add(new \DateInterval('P3D'));

        $this->assertEquals(
            [
                new \DateTimeImmutable($earliest->format('Y-m-d 16:00:00')),
                new \DateTimeImmutable($latest->format('Y-m-d 16:00:00')),
            ],
            $reflectionMethod->invokeArgs($this->orderImportService, [$this->orderItem['lineItems'], $this->shippingMethod->getId()])
        );
    }

    public function testGetOrderPriceData()
    {
        $country          = new CountryEntity();
        $reflectionMethod = ReflectionHelper::getMethod(OrderImportService::class, 'getOrderPriceData');

        $shippingCosts = [
            'quantity' => 1,
            'taxRules' => [
                [
                    'taxRate'    => 19,
                    'extensions' => [],
                    'percentage' => 100
                ]
            ],
            'listPrice'      => null,
            'unitPrice'      => 0,
            'totalPrice'     => 0,
            'referencePrice' => null,
            'calculatedTaxes' => [
                [
                    'tax'        => 0,
                    'price'      => 0,
                    'taxRate'    => 19,
                    'extensions' => []
                ]
            ]
        ];

        $country->setTaxFree(false);
        $this->assertEquals(
            [
                'taxStatus'     => 'gross',
                'taxRules'      => [
                    [
                        'taxRate'    => 19,
                        'extensions' => [],
                        'percentage' => 100
                    ]
                ],
                'totalPrice'    => 100.0,
                'positionPrice' => 100.0,
                'netPrice'      => 81.0,
                'calculatedTaxes' => [
                    [
                        'tax'        => 19.0,
                        'price'      => 100.0,
                        'taxRate'    => 19,
                        'extensions' => []
                    ]
                ],
                'rawTotal' => 100.0
            ],
            $reflectionMethod->invokeArgs($this->orderImportService, [$this->orderItem, $country, 19, 100, 81, $shippingCosts])
        );
    }

    public function testGetLineItemPriceData()
    {
        $reflectionMethod = ReflectionHelper::getMethod(OrderImportService::class, 'getLineItemPriceData');
        $countryRepository = $this->getContainer()->get('country.repository');
        $countryDe = $countryRepository->search((new Criteria())->addFilter(new EqualsFilter('iso', 'DE')), $this->context)->getEntities()->first();
        $this->assertEquals(
            [
                'quantity'        => 1,
                'listPrice'       => null,
                'taxRules'        => [
                    [
                        'taxRate'    => 19,
                        'extensions' => [],
                        'percentage' => 100
                    ]
                ],
                'unitPrice'       => 146.38,
                'totalPrice'      => 146.38,
                'referencePrice'  => null,
                'calculatedTaxes' => [
                    [
                        'tax'        => 23.37159663865546,
                        'price'      => 146.38,
                        'taxRate'    => 19,
                        'extensions' => []
                    ]
                ]
            ],
            $reflectionMethod->invokeArgs($this->orderImportService, [$this->orderItem['lineItems'][0], $countryDe]));
    }

    public function testGetLineItemPriceDefinition()
    {
        $reflectionMethod = ReflectionHelper::getMethod(OrderImportService::class, 'getLineItemPriceDefinition');
        $countryRepository = $this->getContainer()->get('country.repository');
        $countryDe = $countryRepository->search((new Criteria())->addFilter(new EqualsFilter('iso', 'DE')), $this->context)->getEntities()->first();
        $this->assertEquals(
            [
                'type' => 'quantity',
                'price' => 146.38,
                'quantity' => 1,
                'listPrice' => null,
                'isCalculated' => true,
                'referencePriceDefinition' => null,
                'taxRules' => [
                    [
                        'taxRate'    => 19,
                        'extensions' => [],
                        'percentage' => 100
                    ]
                ],
            ],
            $reflectionMethod->invokeArgs($this->orderImportService, [$this->orderItem['lineItems'][0], $countryDe]));
    }

    public function testGetOrderTransaction()
    {
        $reflectionMethod = ReflectionHelper::getMethod(OrderImportService::class, 'getOrderTransaction');
        $orderId = Uuid::randomHex();

        $result = $reflectionMethod->invokeArgs($this->orderImportService, [$orderId, $this->orderItem, 10, 8.10]);
        $this->assertEquals($orderId, $result[0]['orderId']);
        $this->assertEquals([
            'unitPrice'  => 10.0,
            'totalPrice' => 10.0,
            'quantity'   => 1,
            'taxRules' => [
                [
                    'taxRate'    => 19,
                    'extensions' => [],
                    'percentage' => 100
                ]
            ],
            'calculatedTaxes' => [
                [
                    'tax'        => 1.9,
                    'price'      => 10.0,
                    'taxRate'    => 19,
                    'extensions' => []
                ]
            ]
        ], $result[0]['amount']);
        $this->assertEquals($this->paymentMethod->getId(), $result[0]['paymentMethodId']);
        $this->assertEquals([
            'custom_idealo_transaction_id' => 'snakeoil-9028b78'
        ], $result[0]['customFields' ]);
    }

    public function testGetOrderDelivery()
    {
        $reflectionMethod = ReflectionHelper::getMethod(OrderImportService::class, 'getOrderDelivery');
        $orderId = Uuid::randomHex();
        $orderAddress = [
            'id'                     => Uuid::randomHex(),
            'company'                => '',
            'salutationId'           => $this->orderItem['shippingAddress']['salutation'],
            'firstName'              => $this->orderItem['shippingAddress']['firstName'],
            'lastName'               => $this->orderItem['shippingAddress']['lastName'],
            'street'                 => $this->orderItem['shippingAddress']['addressLine1'],
            'zipcode'                => $this->orderItem['shippingAddress']['postalCode'],
            'city'                   => $this->orderItem['shippingAddress']['city'],
            'countryId'              => Uuid::randomHex(),
            'additionalAddressLine1' => '',
            'phoneNumber'            => '123456'
        ];

        $earliest = (new \DateTime())->add(new \DateInterval('P1D'));
        $latest   = (new \DateTime())->add(new \DateInterval('P3D'));
        $result   = $reflectionMethod->invokeArgs($this->orderImportService, [$orderId, $this->orderItem, [], $orderAddress, [], null]);

        $this->assertEquals($orderId, $result[0]['orderId']);
        $this->assertEquals($orderAddress['id'], $result[0]['shippingOrderAddressId']);
        $this->assertEquals($this->shippingMethod->getId(), $result[0]['shippingMethodId']);
        $this->assertEquals(new \DateTimeImmutable($earliest->format('Y-m-d 16:00:00')), $result[0]['shippingDateEarliest']);
        $this->assertEquals(new \DateTimeImmutable($latest->format('Y-m-d 16:00:00')), $result[0]['shippingDateLatest']);
    }

    public function testGetOrderDeliveryDivergentShippingAddress()
    {
        $reflectionMethod = ReflectionHelper::getMethod(OrderImportService::class, 'getOrderDelivery');
        $orderId = Uuid::randomHex();
        $orderAddress = [
            'id'                     => Uuid::randomHex(),
            'company'                => '',
            'salutationId'           => $this->orderItem['shippingAddress']['salutation'],
            'firstName'              => $this->orderItem['shippingAddress']['firstName'],
            'lastName'               => $this->orderItem['shippingAddress']['lastName'],
            'street'                 => $this->orderItem['shippingAddress']['addressLine1'],
            'zipcode'                => $this->orderItem['shippingAddress']['postalCode'],
            'city'                   => $this->orderItem['shippingAddress']['city'],
            'countryId'              => Uuid::randomHex(),
            'additionalAddressLine1' => '',
            'phoneNumber'            => '123456'
        ];

        $earliest = (new \DateTime())->add(new \DateInterval('P1D'));
        $latest   = (new \DateTime())->add(new \DateInterval('P3D'));

        $shippingAddressId = Uuid::randomHex();
        $result = $reflectionMethod->invokeArgs($this->orderImportService, [$orderId, $this->orderItem, [], $orderAddress, [], ['id' => $shippingAddressId]]);

        $this->assertEquals($orderId, $result[0]['orderId']);
        $this->assertEquals($shippingAddressId, $result[0]['shippingOrderAddressId']);
        $this->assertEquals($this->shippingMethod->getId(), $result[0]['shippingMethodId']);
        $this->assertEquals(new \DateTimeImmutable($earliest->format('Y-m-d 16:00:00')), $result[0]['shippingDateEarliest']);
        $this->assertEquals(new \DateTimeImmutable($latest->format('Y-m-d 16:00:00')), $result[0]['shippingDateLatest']);
    }

    public function testGetShippingCosts()
    {
        $reflectionMethod = ReflectionHelper::getMethod(OrderImportService::class, 'getShippingCosts');
        $this->assertEquals(
            [
                'quantity' => 1,
                'taxRules' => [
                    [
                        'taxRate'    => 19,
                        'extensions' => [],
                        'percentage' => 100
                    ]
                ],
                'listPrice'      => null,
                'unitPrice'      => 4.99,
                'totalPrice'     => 4.99,
                'referencePrice' => null,
                'calculatedTaxes' => [
                    [
                        'tax'        => 0.7967226890756303,
                        'price'      => 4.99,
                        'taxRate'    => 19,
                        'extensions' => []
                    ]
                ]
            ],
            $reflectionMethod->invokeArgs($this->orderImportService, [$this->orderItem, 19.00])
        );
    }

    public function testGetBerlinTime()
    {
        $reflectionMethod = ReflectionHelper::getMethod(OrderImportService::class, 'getBerlinTime');
        $this->configProvider->setAdjustOrderTime('0');
        $this->assertEquals(new \DateTime('2019-11-25T15:37:39.000+02:00'), $reflectionMethod->invokeArgs($this->orderImportService, ['2019-11-25T16:37:39.000+02:00']));
        $this->assertEquals(new \DateTime('2019-11-25T09:37:39.000+08:00'), $reflectionMethod->invokeArgs($this->orderImportService, ['2019-11-25T16:37:39.000+08:00']));
        $this->assertEquals(new \DateTime('2019-11-25T16:37:39.000+01:00'), $reflectionMethod->invokeArgs($this->orderImportService, ['2019-11-25T16:37:39.000+01:00']));
        $this->assertEquals(new \DateTime('2019-11-25T17:37:39.000+00:00'), $reflectionMethod->invokeArgs($this->orderImportService, ['2019-11-25T16:37:39.000+00:00']));
        $this->assertEquals(new \DateTime('2019-11-25T22:37:39.000-05:00'), $reflectionMethod->invokeArgs($this->orderImportService, ['2019-11-25T16:37:39.000-05:00']));

        $this->configProvider->setAdjustOrderTime('+1 hour');
        $this->assertEquals(new \DateTime('2019-11-25T16:37:39.000+02:00'), $reflectionMethod->invokeArgs($this->orderImportService, ['2019-11-25T16:37:39.000+02:00']));


        $this->configProvider->setAdjustOrderTime('1');
        $this->assertEquals(new \DateTime('2019-11-25T16:37:39.000+02:00'), $reflectionMethod->invokeArgs($this->orderImportService, ['2019-11-25T16:37:39.000+02:00']));


        $this->configProvider->setAdjustOrderTime('-1');
        $this->assertEquals(new \DateTime('2019-11-25T14:37:39.000+02:00'), $reflectionMethod->invokeArgs($this->orderImportService, ['2019-11-25T16:37:39.000+02:00']));
    }
}
