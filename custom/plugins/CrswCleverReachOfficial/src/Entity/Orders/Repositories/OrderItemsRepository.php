<?php


namespace Crsw\CleverReachOfficial\Entity\Orders\Repositories;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Order\DTO\Attribute\Attribute;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Order\DTO\Category\Category;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Order\DTO\OrderItem;
use Crsw\CleverReachOfficial\Entity\Product\Repositories\ProductRepository;
use DateTime;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\RangeFilter;

/**
 * Class OrderItemsRepository
 *
 * @package Crsw\CleverReachOfficial\Entity\Orders\Repositories
 */
class OrderItemsRepository implements OrderItemsRepositoryInterface
{
    public const DEFAULT_CURRENCY = 'EUR';

    /**
     * @var EntityRepositoryInterface
     */
    private $baseRepository;
    /**
     * @var ProductRepository
     */
    private $productRepository;
    /**
     * @var EntityRepositoryInterface
     */
    private $orderRepository;

    /**
     * OrderItemsRepository constructor.
     *
     * @param EntityRepositoryInterface $baseRepository
     * @param ProductRepository $productRepository
     * @param EntityRepositoryInterface $orderRepository
     */
    public function __construct(
        EntityRepositoryInterface $baseRepository,
        ProductRepository $productRepository,
        EntityRepositoryInterface $orderRepository
    ) {
        $this->baseRepository = $baseRepository;
        $this->productRepository = $productRepository;
        $this->orderRepository = $orderRepository;
    }

    /**
     * Retrieves list of order items for a given order id.
     *
     * @param int | string $orderId
     *
     * @return OrderItem[]
     */
    public function getByOrderId($orderId): array
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('orderId', $orderId));
        $criteria->addAssociation('order');
        $criteria->getAssociation('order')->addAssociations(['currency', 'customer']);

        return $this->getOrderItems($criteria);
    }

    /**
     * Get list of order items for given customer email.
     *
     * @param string $email
     * @param bool $ordersBeforeGivenDate
     * @param DateTime $date
     *
     * @return array
     */
    public function getOrderItemsByEmail(string $email, bool $ordersBeforeGivenDate, DateTime $date): array
    {
        $criteria = new Criteria();
        $criteria->addAssociation('order');
        $criteria->getAssociation('order')->addAssociations(['currency', 'customer']);
        $criteria->addFilter(new EqualsFilter('order.orderCustomer.email', $email));

        if ($ordersBeforeGivenDate) {
            $criteria->addFilter(new RangeFilter('order.orderDateTime', [
                RangeFilter::LT => $date->format('Y-m-d H:i:s')
            ]));
        } else {
            $criteria->addFilter(new RangeFilter('order.orderDateTime', [
                RangeFilter::GTE => $date->format('Y-m-d H:i:s')
            ]));
        }

        return $this->getOrderItems($criteria);
    }

    /**
     * Provides order source that will be attached to receiver during export.
     *
     * @param mixed $orderId
     *
     * @return string
     */
    public function getOrderSource($orderId): string
    {
        $criteria = new Criteria([$orderId]);
        $criteria->addAssociation('salesChannel');

        /** @var OrderEntity $order */
        $order = $this->orderRepository->search($criteria, Context::createDefaultContext())->getEntities()->first();
        $salesChannel = $order->getSalesChannel();

        return ($salesChannel && $salesChannel->getDomains()) ?
            $salesChannel->getDomains()->first()->getUrl() : 'Shopware 6';
    }

    /**
     * Gets customer email by order id.
     *
     * @param $orderId
     *
     * @return string
     */
    public function getEmailByOrderId($orderId): string
    {
        $criteria = new Criteria([$orderId]);
        $criteria->addAssociation('customer');

        /** @var OrderEntity $order */
        $order = $this->orderRepository->search($criteria, Context::createDefaultContext())->getEntities()->first();

        return $order->getOrderCustomer() ? $order->getOrderCustomer()->getEmail() : '';
    }

    /**
     * Fetches OrderLineItemCollection, based on given criteria, and returns formatted OrderItem DTOs
     *
     * @param Criteria $criteria
     *
     * @return OrderItem[]
     */
    private function getOrderItems(Criteria $criteria): array
    {
        /** @var OrderLineItemCollection $collection */
        $collection = $this->baseRepository->search($criteria, Context::createDefaultContext())->getEntities();
        $products = $this->fetchProducts($collection);
        $formattedOrderItems = [];

        /** @var OrderLineItemEntity $item */
        foreach ($collection as $item) {
            $productId = $item->getProductId();
            $productEntity = !empty($products[$productId]) ? $products[$productId] : null;
            $formattedOrderItems[] = $this->formatOrderItem($item, $productEntity);
        }

        return $formattedOrderItems;
    }

    /**
     * @param OrderLineItemCollection $orderItems
     *
     * @return array
     */
    private function fetchProducts(OrderLineItemCollection $orderItems): array
    {
        $productIds = [];

        /** @var OrderLineItemEntity $item */
        foreach ($orderItems as $item) {
            $productId = $item->getProductId();
            if ($productId && !in_array($productId, $productIds, true)) {
                $productIds[] = $productId;
            }
        }

        $productsMap = [];

        $products = $this->productRepository->getProductsForSync($productIds, Context::createDefaultContext());

        foreach ($products as $productEntity) {
            $productsMap[$productEntity->getId()] = $productEntity;
        }

        return $productsMap;
    }

    /**
     * @param OrderLineItemEntity $entity
     * @param ProductEntity|null $productEntity
     *
     * @return OrderItem|null
     */
    private function formatOrderItem(OrderLineItemEntity $entity, ?ProductEntity $productEntity): ?OrderItem
    {
        $order = $entity->getOrder();

        if (!$order) {
            return null;
        }

        if ($productEntity) {
            $productId = $productEntity->getProductNumber();
        } else {
            $productId = $entity->getIdentifier();
        }

        $currency = $order->getCurrency() ? $order->getCurrency()->getIsoCode() : self::DEFAULT_CURRENCY;
        $createdAt = $entity->getCreatedAt() ?: new DateTime();

        $orderItem = new OrderItem($order->getOrderNumber(), $productId, $entity->getLabel());
        $orderItem->setPrice($entity->getTotalPrice());
        $orderItem->setStamp($createdAt->getTimestamp());
        $orderItem->setCurrency($currency);
        $orderItem->setQuantity($entity->getQuantity());

        if ($productEntity) {
            $manufacturer = $productEntity->getManufacturer() ?
                $productEntity->getManufacturer()->getTranslation('name') : '';
            $orderItem->setVendor($manufacturer);
            $orderItem->setCategories($this->getProductCategories($productEntity));
            $orderItem->setAttributes($this->getProductAttributes($productEntity));
        }

        return $orderItem;
    }

    /**
     * @param ProductEntity $productEntity
     *
     * @return array
     */
    private function getProductCategories(ProductEntity $productEntity): array
    {
        $categories = [];

        foreach ($productEntity->getCategories() as $category) {
            foreach ($category->getBreadcrumb() as $item) {
                $categories[] = new Category($item);
            }
        }

        return $categories;
    }

    /**
     * @param ProductEntity $productEntity
     *
     * @return array
     */
    private function getProductAttributes(ProductEntity $productEntity): array
    {
        $attributes = [];

        foreach ($productEntity->getProperties() as $property) {
            $key = $property->getGroup() ? $property->getGroup()->getTranslation('name') : 'custom';
            $attributes[] = new Attribute($key, $property->getTranslation('name'));
        }

        foreach ($productEntity->getOptions() as $option) {
            $key = $option->getGroup() ? $option->getGroup()->getTranslation('name') : 'custom';
            $attributes[] = new Attribute($key, $option->getTranslation('name'));
        }

        return $attributes;
    }
}
