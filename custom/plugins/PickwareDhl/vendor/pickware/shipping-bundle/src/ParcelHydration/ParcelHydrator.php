<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\ShippingBundle\ParcelHydration;

use InvalidArgumentException;
use Pickware\DalBundle\ContextFactory;
use Pickware\DalBundle\EntityManager;
use Pickware\MoneyBundle\Currency;
use Pickware\MoneyBundle\MoneyValue;
use Pickware\ShippingBundle\Notifications\NotificationService;
use Pickware\ShippingBundle\Parcel\Parcel;
use Pickware\ShippingBundle\Parcel\ParcelCustomsInformation;
use Pickware\ShippingBundle\Parcel\ParcelItem;
use Pickware\ShippingBundle\Parcel\ParcelItemCustomsInformation;
use Pickware\UnitsOfMeasurement\Dimensions\BoxDimensions;
use Pickware\UnitsOfMeasurement\PhysicalQuantity\Length;
use Pickware\UnitsOfMeasurement\PhysicalQuantity\Weight;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Document\DocumentEntity as ShopwareDocumentEntity;
use Shopware\Core\Checkout\Document\DocumentGenerator\InvoiceGenerator;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity;
use Shopware\Core\Checkout\Order\OrderDefinition;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Content\Product\State;
use Shopware\Core\Framework\Context;

class ParcelHydrator
{
    public const CUSTOM_FIELD_NAME_CUSTOMS_INFORMATION_DESCRIPTION = 'pickware_shipping_customs_information_description';
    public const CUSTOM_FIELD_NAME_CUSTOMS_INFORMATION_TARIFF_NUMBER = 'pickware_shipping_customs_information_tariff_number';
    public const CUSTOM_FIELD_NAME_CUSTOMS_INFORMATION_COUNTRY_OF_ORIGIN = 'pickware_shipping_customs_information_country_of_origin';

    private const SUPPORTED_ORDER_LINE_ITEM_TYPES = [
        LineItem::PRODUCT_LINE_ITEM_TYPE,
        LineItem::CUSTOM_LINE_ITEM_TYPE,
    ];

    private EntityManager $entityManager;
    private ContextFactory $contextFactory;
    private NotificationService $notificationService;

    public function __construct(
        EntityManager $entityManager,
        ContextFactory $contextFactory,
        NotificationService $notificationService
    ) {
        $this->entityManager = $entityManager;
        $this->contextFactory = $contextFactory;
        $this->notificationService = $notificationService;
    }

    public function hydrateParcelFromOrder(string $orderId, Context $context): Parcel
    {
        // Consider inheritance when fetching products for inherited fields (e.g. name, weight)
        $orderContext = $this->contextFactory->deriveOrderContext($orderId, $context);
        $orderContext->setConsiderInheritance(true);
        /** @var OrderEntity $order */
        $order = $this->entityManager->findByPrimaryKey(
            OrderDefinition::class,
            $orderId,
            $orderContext,
            [
                'currency',
                'documents.documentType',
                'lineItems.product',
            ],
        );

        $parcel = new Parcel();
        $parcel->setCustomerReference($order->getOrderNumber());

        $customsInformation = new ParcelCustomsInformation($parcel);
        $currencyCode = $order->getCurrency()->getIsoCode();
        $shippingCosts = new MoneyValue($order->getShippingTotal(), new Currency($currencyCode));
        $customsInformation->addFee(ParcelCustomsInformation::FEE_TYPE_SHIPPING_COSTS, $shippingCosts);

        $invoices = $order->getDocuments()->filter(
            fn (ShopwareDocumentEntity $document) => $document->getDocumentType()->getTechnicalName() === InvoiceGenerator::INVOICE,
        );
        $invoices->sort(
            function (ShopwareDocumentEntity $a, ShopwareDocumentEntity $b) {
                if ($a->getCreatedAt() === $b->getCreatedAt()) {
                    return 0;
                }

                return $a->getCreatedAt()->getTimestamp() > $b->getCreatedAt()->getTimestamp() ? -1 : 1;
            },
        );
        if ($invoices->first()) {
            $customsInformation->setInvoiceNumber($invoices->first()->getConfig()['documentNumber']);
            $customsInformation->setInvoiceDate($invoices->first()->getConfig()['documentDate']);
        }

        $calculatedPricesByOrderLineItemIds = $this->calculateDiscountedPricesForLineItems($order->getLineItems());

        foreach ($order->getLineItems() as $orderLineItem) {
            if (!$this->isSupportedOrderLineItem($orderLineItem)) {
                continue;
            }

            $parcel->addItem($this->createParcelItemFromLineItem(
                $order,
                $orderLineItem,
                $calculatedPricesByOrderLineItemIds[$orderLineItem->getId()],
            ));
        }

        return $parcel;
    }

    private function createParcelItemFromLineItem(OrderEntity $order, OrderLineItemEntity $orderLineItem, float $calculatedPrice): ParcelItem
    {
        $parcelItem = new ParcelItem($orderLineItem->getQuantity());

        $itemCustomsInformation = new ParcelItemCustomsInformation($parcelItem);
        $customsValue = new MoneyValue(
            $calculatedPrice,
            new Currency($order->getCurrency()->getIsoCode()),
        );
        $itemCustomsInformation->setCustomsValue($customsValue);

        $product = $orderLineItem->getProduct();
        if (!$product) {
            // If the product does not exist (i.e. has been deleted) only use the label of the order line item
            $parcelItem->setName($orderLineItem->getLabel());
            $itemCustomsInformation->setDescription($orderLineItem->getLabel());

            return $parcelItem;
        }

        $productName = $product->getName() ?: $product->getTranslation('name');
        $parcelItem->setName($productName);
        $parcelItem->setUnitWeight($product->getWeight() ? new Weight($product->getWeight(), 'kg') : null);
        if ($product->getWidth() !== null && $product->getHeight() !== null && $product->getLength() !== null) {
            $parcelItem->setUnitDimensions(new BoxDimensions(
                new Length($product->getWidth(), 'mm'),
                new Length($product->getHeight(), 'mm'),
                new Length($product->getLength(), 'mm'),
            ));
        }

        $customFields = $product->getCustomFields();

        $description = $customFields[self::CUSTOM_FIELD_NAME_CUSTOMS_INFORMATION_DESCRIPTION] ?? '';
        if (!$description) {
            // If no explicit description for this product was provided, use the product name as fallback
            $description = $productName;
        }

        $itemCustomsInformation->setDescription($description);
        $itemCustomsInformation->setTariffNumber(
            $customFields[self::CUSTOM_FIELD_NAME_CUSTOMS_INFORMATION_TARIFF_NUMBER] ?? null,
        );
        try {
            $itemCustomsInformation->setCountryIsoOfOrigin(
                $customFields[self::CUSTOM_FIELD_NAME_CUSTOMS_INFORMATION_COUNTRY_OF_ORIGIN] ?? null,
            );
        } catch (InvalidArgumentException $exception) {
            // Since the customs information are optional, we can ignore this error, add a notification and continue
            // with the remaining parcel items (without throwing an Exception).
            $this->notificationService->emit(
                ParcelHydrationNotification::parcelItemCustomsInformationInvalid(
                    $orderLineItem->getLabel(),
                    $exception,
                ),
            );
        }

        return $parcelItem;
    }

    /*
     * Calculate the prices for line items if a discount or promotion is applied on an order. Therefore, we distribute
     * the discount proportionally among the line items by deducting their prices with their calculated weighted share
     * of the discount
     */
    private function calculateDiscountedPricesForLineItems(OrderLineItemCollection $orderLineItems): array
    {
        if ($orderLineItems->count() === 0) {
            return [];
        }

        $totalValueOfSupportedLineItems = 0;
        $totalDiscountPriceForOrder = 0;
        /** @var OrderLineItemEntity $orderLineItem */
        foreach ($orderLineItems as $orderLineItem) {
            if ($orderLineItem->getType() === LineItem::PROMOTION_LINE_ITEM_TYPE
                || $orderLineItem->getType() === LineItem::DISCOUNT_LINE_ITEM) {
                $totalDiscountPriceForOrder = $totalDiscountPriceForOrder + $orderLineItem->getPrice()->getTotalPrice();
            }

            if ($this->isSupportedOrderLineItem($orderLineItem)) {
                $totalValueOfSupportedLineItems = $totalValueOfSupportedLineItems + $orderLineItem->getPrice()->getTotalPrice();
            }
        }

        $calculatedPricesByLineItemId = [];
        foreach ($orderLineItems as $orderLineItem) {
            if (!$this->isSupportedOrderLineItem($orderLineItem)) {
                continue;
            }

            if ($totalDiscountPriceForOrder === 0) {
                $calculatedPricesByLineItemId[$orderLineItem->getId()] = $orderLineItem->getPrice()->getUnitPrice();
                continue;
            }

            $discountOnOrderLineItem = (($orderLineItem->getPrice()->getTotalPrice() / $totalValueOfSupportedLineItems) * $totalDiscountPriceForOrder) / $orderLineItem->getPrice()->getQuantity();

            $calculatedPricesByLineItemId[$orderLineItem->getId()] = $orderLineItem->getPrice()->getUnitPrice() + $discountOnOrderLineItem;
        }

        return $calculatedPricesByLineItemId;
    }

    private function isSupportedOrderLineItem(OrderLineItemEntity $orderLineItem): bool
    {
        if (!in_array($orderLineItem->getType(), self::SUPPORTED_ORDER_LINE_ITEM_TYPES, true)) {
            return false;
        }

        // Digital products are not supported
        // getStates() has been introduced along with digital products in SW 6.4.19
        if (method_exists($orderLineItem, 'getStates')
            && in_array(State::IS_DOWNLOAD, $orderLineItem->getStates(), true)
        ) {
            return false;
        }

        return true;
    }
}
