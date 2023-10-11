<?php

namespace Ott\IdealoConnector\Test\Subscriber;

use DateTime;
use Ott\IdealoConnector\Subscriber\OrderSearchSubscriber;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Order\OrderDefinition;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntitySearchResultLoadedEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;

class OrderSearchSubscriberTest extends TestCase
{
    use IntegrationTestBehaviour;

    public function testOrderSearchResultModification()
    {
        $context          = Context::createDefaultContext();
        $orderId          = Uuid::randomHex();
        $languageId       = $context->getLanguageId();
        $currencyId       = $context->getCurrencyId();
        $stateId          = Uuid::randomHex();
        $billingAddressId = Uuid::randomHex();

        $salutationRepository = $this->getContainer()->get('salutation.repository');
        $salutationId = $salutationRepository->search(new Criteria(), $context)->getEntities()->first()->getId();

        $salesChannelRepository = $this->getContainer()->get('sales_channel.repository');
        $salesChannelId = $salesChannelRepository->search(new Criteria(), $context)->getEntities()->first()->getId();

        $customerRepository = $this->getContainer()->get('customer.repository');
        $customer = $customerRepository->search(new Criteria(), $context)->getEntities()->first();

        /** @var EntityRepositoryInterface $idealoOrderRepository */
        $orderRepository = $this->getContainer()->get('order.repository');
        $orderRepository->create([
            [
                'id'               => $orderId,
                'salesChannelId'   => $salesChannelId,
                'languageId'       => $languageId,
                'currencyId'       => $currencyId,
                'billingAddressId' => $billingAddressId,
                'orderDateTime'    => (new DateTime())->format('Y-m-d H:i:s'),
                'currencyFactor'   => 1,
                'price'            => [
                    'taxStatus'     => 'gross',
                    'taxRules'      => [
                        [
                            'taxRate'    => 19,
                            'extensions' => [],
                            'percentage' => 100
                        ]
                    ],
                    'totalPrice'    => 10,
                    'positionPrice' => 10,
                    'netPrice'      => 8.10,
                    'calculatedTaxes' => [
                        [
                            'tax'        => 1.90,
                            'price'      => 10,
                            'taxRate'    => 19,
                            'extensions' => []
                        ]
                    ],
                    'rawTotal' => 10,
                ],
                'shippingCosts'    => [
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
                ],
                'orderNumber'      => '123456',
                'stateId'          => $stateId,
                'orderCustomer' => [
                    'id'           => $customer->getId(),
                    'email'        => 'bla@bla.com',
                    'firstName'    => 'foo',
                    'lastName'     => 'bar',
                    'salutationId' => $salutationId
                ]
            ],
        ], Context::createDefaultContext());

        $id = Uuid::randomHex();
        /** @var EntityRepositoryInterface $idealoOrderRepository */
        $idealoOrderRepository = $this->getContainer()->get('idealo_order.repository');
        $idealoOrderRepository->create([
            [
                'id'                  => $id,
                'idealoTransactionId' => 'abc',
                'orderId'             => $orderId,
            ],
        ], Context::createDefaultContext());

        $searchResult = $orderRepository->search(new Criteria([$orderId]), $context);
        $event = new EntitySearchResultLoadedEvent(new OrderDefinition(), $searchResult);

        $subscriber = new OrderSearchSubscriber($this->getContainer()->get('Ott\IdealoConnector\Dbal\DataProvider'));
        $subscriber->onOrderSearchResult($event);
        $order = $event->getResult()->getEntities()->first();

        $this->assertSame('abc', $order->getCustomFields()['ott_idealo_id']);
    }
}
