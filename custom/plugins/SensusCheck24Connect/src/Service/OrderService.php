<?php declare(strict_types=1);

namespace Sensus\Check24Connect\Service;

use Psr\Log\LoggerInterface;
use Sensus\Check24Connect\Struct\Address;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Checkout\Order\Api\OrderActionController;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Order\OrderStates;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Shopware\Core\Content\Product\Cart\ProductLineItemFactory;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextService;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\HttpFoundation\Request;
use Shopware\Core\System\SalesChannel\Context\AbstractSalesChannelContextFactory;

class OrderService
{
    use LocalDirAwareTrait;

    /**
     * @var SystemConfigService
     */
    private $systemConfigService;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var CustomerService
     */
    private $customerService;

    /**
     * @var CartService
     */
    private $cartService;

    /**
     * @var EntityRepositoryInterface
     */
    private $productRepository;

    /**
     * @var EntityRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var AbstractSalesChannelContextFactory
     */
    private $salesChannelContextFactory;

    /**
     * @var OrderActionController
     */
    private $orderActionController;

    /**
     * OrderService constructor.
     * @param SystemConfigService $systemConfigService
     * @param LoggerInterface $logger
     * @param CustomerService $customerService
     * @param CartService $cartService
     * @param EntityRepositoryInterface $productRepository
     * @param SalesChannelContextFactory $salesChannelContextFactory
     */
    public function __construct(SystemConfigService $systemConfigService,
                                LoggerInterface $logger,
                                CustomerService $customerService,
                                CartService $cartService,
                                EntityRepositoryInterface $productRepository,
                                EntityRepositoryInterface $orderRepository,
                                AbstractSalesChannelContextFactory  $salesChannelContextFactory,
                                OrderActionController $orderActionController)
    {
        $this->systemConfigService = $systemConfigService;
        $this->logger = $logger;
        $this->customerService = $customerService;
        $this->cartService = $cartService;
        $this->productRepository = $productRepository;
        $this->orderRepository = $orderRepository;
        $this->salesChannelContextFactory = $salesChannelContextFactory;
        $this->orderActionController = $orderActionController;
    }

    public function parseAndExecuteOrder(string $filename, SalesChannelContext $context)
    {
        $filename = $this->getLocalDir() . '/' . $filename;

        if (!file_exists($filename)) {
            $this->logger->error('Something went wrong. Could not find fetched order file.');
            return false;
        }

        $xml = new \SimpleXMLElement(file_get_contents($filename));

        $customer = $this->createCustomer($xml, $context);

        $context = $this->salesChannelContextFactory->create(
            '',
            $context->getSalesChannel()->getId(),
            [
                SalesChannelContextService::CUSTOMER_ID => $customer->getId(),
                SalesChannelContextService::PAYMENT_METHOD_ID => $this->systemConfigService->get('SensusCheck24Connect.config.paymentMethod', $context->getSalesChannel()->getId()),
                SalesChannelContextService::SHIPPING_METHOD_ID => $this->systemConfigService->get('SensusCheck24Connect.config.shippingMethod', $context->getSalesChannel()->getId())
            ]
        );

        $token = Uuid::randomHex();

        $cart = $this->cartService->createNew($token);

        foreach ($xml->ORDER_ITEM_LIST->ORDER_ITEM as $item) {
            $articleOrderNumber = $item->PRODUCT_ID->SUPPLIER_PID . '';

            $criteria = new Criteria();
            $criteria->addFilter(new EqualsFilter('productNumber', $articleOrderNumber));
            $products = $this->productRepository->search($criteria, $context->getContext());

            if($products->count() > 0) {
                $lineItem = (new ProductLineItemFactory())->create($products->first()->getId(), ['quantity' => intval($item->QUANTITY . '')]);

                $this->cartService->add($cart, $lineItem, $context);
            }
        }

        $emptyRequestBag = new RequestDataBag([]);

        $orderId = $this->cartService->order($cart, $context, $emptyRequestBag);

        $orderState = $this->systemConfigService->get('SensusCheck24Connect.config.orderState', $context->getSalesChannel()->getId());
        if ($orderState && $orderState !== OrderStates::STATE_OPEN) {
            $this->transitionOrderState($orderId, $orderState, $context->getContext());
        }

        $orderTransactionState = $this->systemConfigService->get('SensusCheck24Connect.config.orderTransactionState', $context->getSalesChannel()->getId());
        if ($orderTransactionState && $orderTransactionState !== OrderTransactionStates::STATE_OPEN) {
            $criteria = new Criteria([$orderId]);
            $criteria->addAssociation('transactions');
            $result = $this->orderRepository->search($criteria, $context->getContext());

            /** @var OrderEntity|null $order */
            $order = $result->first();

            if (empty($order)) {
                throw new \Exception('Could not find order with ID: ' . $orderId);
            }

            foreach ($order->getTransactions()->getIds() as $orderTransactionId) {
                $this->transitionOrderTransactionState($orderTransactionId, $orderTransactionState, $context->getContext());
            }
        }
    }

    /**
     * @param \SimpleXMLElement $xml
     * @param SalesChannelContext $context
     * @return \Shopware\Core\Checkout\Customer\CustomerEntity
     */
    protected function createCustomer(\SimpleXMLElement $xml, SalesChannelContext $context): \Shopware\Core\Checkout\Customer\CustomerEntity
    {
        $deliveryIndex = $billingIndex = 0;
        $key = 0;
        foreach ($xml->ORDER_HEADER->ORDER_INFO->PARTIES->PARTY as $party) {
            if ($party->PARTY_ROLE . '' == 'invoice') {
                $billingIndex = $key;
            } elseif ($party->PARTY_ROLE . '' == 'delivery') {
                $deliveryIndex = $key;
            }
            $key++;
        }

        $customer = $this->customerService->createCustomer(
            $xml->ORDER_HEADER->ORDER_INFO->PARTIES->PARTY[$billingIndex]->ADDRESS->EMAIL . '',
            $this->mapAddress($xml, $billingIndex),
            $this->mapAddress($xml, $deliveryIndex),
            $context
        );
        return $customer;
    }

    /**
     * @param \SimpleXMLElement $xml
     * @param int $index
     * @return Address
     */
    protected function mapAddress(\SimpleXMLElement $xml, int $index): Address
    {
        $address = new Address(
            ($xml->ORDER_HEADER->ORDER_INFO->PARTIES->PARTY[$index]->ADDRESS->CONTACT_DETAILS->TITLE . '' == 'male' ? 'mr' : 'mrs'),
            $xml->ORDER_HEADER->ORDER_INFO->PARTIES->PARTY[$index]->ADDRESS->NAME2 . '',
            $xml->ORDER_HEADER->ORDER_INFO->PARTIES->PARTY[$index]->ADDRESS->NAME3 . '',
            $xml->ORDER_HEADER->ORDER_INFO->PARTIES->PARTY[$index]->ADDRESS->NAME . '',
            $xml->ORDER_HEADER->ORDER_INFO->PARTIES->PARTY[$index]->ADDRESS->STREET . '',
            $xml->ORDER_HEADER->ORDER_INFO->PARTIES->PARTY[$index]->ADDRESS->ZIP . '',
            $xml->ORDER_HEADER->ORDER_INFO->PARTIES->PARTY[$index]->ADDRESS->CITY . '',
            $xml->ORDER_HEADER->ORDER_INFO->PARTIES->PARTY[$index]->ADDRESS->COUNTRY_CODED[0] . ''
        );
        return $address;
    }

    /**
     * Transition an order to a different state
     * $state should be one of @OrderStates
     * https://docs.shopware.com/en/shopware-platform-dev-en/admin-api-guide/action-routes?category=shopware-platform-dev-en/admin-api-guide
     * @param string $orderId
     * @param string $state
     * @param Context $context
     */
    protected function transitionOrderState(string $orderId, string $state, Context $context): void
    {
        $apiVersion = 2;
        $request = new Request();
        $request->request->set('sendMail', false);

        $this->orderActionController->orderStateTransition($orderId, $state, $request, $context);
    }

    /**
     * Transition an order transaction to a different state
     * $state should be one of @OrderTransactionStates
     * https://docs.shopware.com/en/shopware-platform-dev-en/admin-api-guide/action-routes?category=shopware-platform-dev-en/admin-api-guide
     * @param string $orderTransactionId
     * @param string $state
     * @param Context $context
     */
    protected function transitionOrderTransactionState(string $orderTransactionId, string $state, Context $context): void
    {
        $apiVersion = 2;
        $request = new Request();
        $request->request->set('sendMail', false);

        $this->orderActionController->orderTransactionStateTransition($orderTransactionId, $state, $request, $context);
    }


}
