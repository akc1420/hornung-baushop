<?php declare(strict_types = 1);

namespace Cbax\ModulAnalytics\Components\Statistics;

//use Shopware\Core\Framework\Context;
use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Uuid\Uuid;
use Cbax\ModulAnalytics\Components\Base;
use Shopware\Core\Checkout\Cart\Cart;

class UnfinishedOrders
{
    private $config;
    private $base;
    private $connection;

    public function __construct(
        $config,
        Base $base,
        Connection $connection
    )
    {
        $this->config = $config;
        $this->base = $base;
        $this->connection = $connection;
    }

    public function getUnfinishedOrders($parameters, $context)
    {
        $languageId = $this->base->getLanguageIdByLocaleCode($parameters['adminLocalLanguage'], $context);
        $payloadColumn = 'payload';

        $sql = "SHOW COLUMNS FROM `cart` WHERE `Field` LIKE 'cart';";
        $columnTest = $this->connection->executeQuery($sql)->fetchAll();
        if (!empty($columnTest)) $payloadColumn = 'cart';

        $qb = $this->connection->createQueryBuilder();
        $query = $qb
            ->select([
                'cart.created_at as createdAt',
                'cart.price/currency.factor as price',
                'customer.first_name as firstName',
                'customer.last_name as lastName',
                'pmt.name as payment',
                'altpmt.name as altPayment',
                'line_item_count as itemCount',
                'customer.email as email',
                'sct.name as salesChannel',
                'altsct.name as altSalesChannel',

            ])
            ->from('`cart`', 'cart')
            ->InnerJoin('cart', 'customer', 'customer', 'cart.customer_id = customer.id')
            ->InnerJoin('cart', 'currency', 'currency', 'cart.currency_id = currency.id')
            ->leftJoin('cart', 'payment_method_translation', 'pmt',
                'cart.payment_method_id = pmt.payment_method_id AND pmt.language_id = UNHEX(:language)')
            ->leftJoin('cart', 'payment_method_translation', 'altpmt',
                'cart.payment_method_id = altpmt.payment_method_id AND altpmt.language_id = UNHEX(:altLanguage)')
            ->leftJoin('cart', 'sales_channel_translation', 'sct',
                'cart.sales_channel_id = sct.sales_channel_id AND sct.language_id = UNHEX(:language)')
            ->leftJoin('cart', 'sales_channel_translation', 'altsct',
                'cart.sales_channel_id = altsct.sales_channel_id AND altsct.language_id = UNHEX(:altLanguage)')
            ->andWhere('cart.created_at >= :start')
            ->andWhere('cart.created_at <= :end')
            ->andWhere('cart.name != "recalculation"')
            ->andWhere('cart.customer_id IS NOT NULL')
            ->setParameters([
                'start' => $parameters['startDate'],
                'end' => $parameters['endDate'],
                'language' => $languageId,
                'altLanguage' => $context->getLanguageId()
            ])
            ->orderBy('createdAt', 'DESC');

        if ($payloadColumn == 'cart')
        {
            $query->addSelect(['cart.cart as cart']);
        } else {
            $query->addSelect(['cart.payload as cart']);
        }

        if (!empty($parameters['salesChannelIds']))
        {
            array_walk($parameters['salesChannelIds'],
                function (&$value) { $value = Uuid::fromHexToBytes($value); }
            );

            $query->andWhere('cart.sales_channel_id IN (:salesChannels)')
                ->setParameter('salesChannels', $parameters['salesChannelIds'], Connection::PARAM_STR_ARRAY);
        }

        if (!empty($parameters['customerGroupIds']))
        {
            array_walk($parameters['customerGroupIds'],
                function (&$value) { $value = Uuid::fromHexToBytes($value); }
            );

            $query->andWhere('customer.customer_group_id IN (:customerGroupIds)')
                ->setParameter('customerGroupIds', $parameters['customerGroupIds'], Connection::PARAM_STR_ARRAY);
        }

        $data = $query->execute()->fetchAll();

        $gridData = [];

        foreach($data as $cart)
        {
            $lineItems = $this->getLineItems($cart);

            $gridData[] = [
                'date' => $this->base->getFormatedDate($cart['createdAt'], $parameters['adminLocalLanguage']),
                'sales' => round((float)$cart['price'], 2),
                'name' => $cart['firstName'] . ' ' . $cart['lastName'],
                'payment' => $cart['payment'] ?? $cart['altPayment'],
                'salesChannel' => $cart['salesChannel'] ?? $cart['altSalesChannel'],
                'itemCount' => (int)$cart['itemCount'],
                'email' => $cart['email'],
                'lineItems' => $lineItems
            ];

        }

        if ($parameters['format'] === 'csv') {
            return $this->base->exportCSV($gridData, $parameters['labels']);
        }

        return $gridData;
    }

    private function getLineItems($cart)
    {
        $lineItems = [];
        $cartObject = \unserialize($cart['cart']);

        if (!is_object($cartObject)) return $lineItems;
        if (!(get_class($cartObject) === 'Shopware\Core\Checkout\Cart\Cart')) return $lineItems;

        $lineItemsColl = $cartObject->getLineItems();
        foreach ($lineItemsColl->getElements() as $item)
        {
            $lineItems[] = [
                'quantity' => (int)$item->getQuantity(),
                'type' => $item->getType(),
                'unitPrice' => round((float)$item->getPrice()->getUnitPrice(), 2),
                'totalPrice' => round((float)$item->getPrice()->getTotalPrice(), 2),
                'label' => $item->getLabel()
            ];
        }

        return $lineItems;

    }
}

