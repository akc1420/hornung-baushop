<?php declare(strict_types = 1);

namespace Cbax\ModulAnalytics\Components\Statistics;

//use Shopware\Core\Framework\Context;
use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Uuid\Uuid;
use Cbax\ModulAnalytics\Components\Base;

class UnfinishedOrdersByPayment
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

    public function getUnfinishedOrdersByPayment($parameters, $context)
    {
        $languageId = $this->base->getLanguageIdByLocaleCode($parameters['adminLocalLanguage'], $context);

        $qb = $this->connection->createQueryBuilder();
        $query = $qb
            ->select([
                'COUNT(cart.token) as count',
                'SUM(cart.price/currency.factor) as price',
                'pmt.payment_method_id as paymentId',
                'pmt.name as payment',
                'altpmt.name as altPayment'
            ])
            ->from('`cart`', 'cart')
            ->InnerJoin('cart', 'currency', 'currency', 'cart.currency_id = currency.id')
            ->leftJoin('cart', 'payment_method_translation', 'pmt',
                'cart.payment_method_id = pmt.payment_method_id AND pmt.language_id = UNHEX(:language)')
            ->leftJoin('cart', 'payment_method_translation', 'altpmt',
                'cart.payment_method_id = altpmt.payment_method_id AND altpmt.language_id = UNHEX(:altLanguage)')
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
            ->orderBy('count', 'DESC')
            ->groupBy('pmt.payment_method_id');

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

            $query->InnerJoin('cart', 'customer', 'customer', 'cart.customer_id = customer.id')
                ->andWhere('customer.customer_group_id IN (:customerGroupIds)')
                ->setParameter('customerGroupIds', $parameters['customerGroupIds'], Connection::PARAM_STR_ARRAY);
        }

        $data = $query->execute()->fetchAll();

        $sortedData = [];

        foreach($data as $pay)
        {
            $sortedData[] = [
                'name' => $pay['payment'] ? $pay['payment'] : $pay['altPayment'],
                'sum' => (int)$pay['count'],
                'sales' => round((float)$pay['price'], 2),
            ];
        }

        if ($parameters['format'] === 'csv') {
            return $this->base->exportCSV($sortedData, $parameters['labels']);
        }

        $seriesData = $this->base->limitData($sortedData, $this->config['chartLimit']);
        $gridData   = $this->base->limitData($sortedData, $this->config['gridLimit']);

        return ['gridData' => $gridData, 'seriesData' => $seriesData];
    }
}

