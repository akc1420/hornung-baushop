<?php declare(strict_types = 1);

namespace Cbax\ModulAnalytics\Components;

use Doctrine\DBAL\Query\QueryBuilder;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\RangeFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Context;
use Shopware\Core\Checkout\Order\OrderStates;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Shopware\Core\Content\Product\ProductEntity;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use League\Flysystem\FilesystemInterface;
use Symfony\Component\HttpFoundation\Request;
use Shopware\Core\Framework\Uuid\Uuid;
use Doctrine\DBAL\Connection;

class Base
{
    const EXPORT_FILE_NAME = 'cbax-statistics.csv';
    private $stateMachineStateRepository;
    private $stateMachineRepository;
    private $config;
    private $localeRepository;
    private $languageRepository;
    private $productRepository;
    private $propertyGroupOptionRepository;
    private $configReaderHelper;
    /**
     * @var FilesystemInterface
     */
    private FilesystemInterface $fileSystemPrivate;

    public function __construct(
        EntityRepositoryInterface $stateMachineRepository,
        EntityRepositoryInterface $stateMachineStateRepository,
        EntityRepositoryInterface $localeRepository,
        EntityRepositoryInterface $languageRepository,
        EntityRepositoryInterface $productRepository,
        EntityRepositoryInterface $propertyGroupOptionRepository,
        ConfigReaderHelper $configReaderHelper,
        FilesystemInterface $fileSystemPrivate
    )
    {
        $this->stateMachineRepository =  $stateMachineRepository;
        $this->stateMachineStateRepository =  $stateMachineStateRepository;
        $this->localeRepository =  $localeRepository;
        $this->languageRepository =  $languageRepository;
        $this->productRepository = $productRepository;
        $this->propertyGroupOptionRepository = $propertyGroupOptionRepository;
        $this->config = null;
        $this->configReaderHelper = $configReaderHelper;
        $this->fileSystemPrivate = $fileSystemPrivate;
    }

    /**
     * @param     string    $prodId
     * @param     Context $context
     * @return    string
     */
    public function getProductNameFromId($prodId, Context $context)
    {
        $context->setConsiderInheritance(true);
        $criteria = new Criteria();
        $criteria->setLimit(1);
        $criteria->addAssociation('options');
        $criteria->getAssociation('options')
            ->addSorting(new FieldSorting('groupId'))
            ->addSorting(new FieldSorting('id'));
        $criteria->addFilter(new EqualsFilter('id', $prodId));
        $prod = $this->productRepository->search($criteria, $context)->first();

        $name = '';
        $optionNames = '';
        if (!empty($prod))
        {
            $name = $prod->getName() ?? $prod->getTranslated()['name'];
            if (!empty($prod->getParentId()))
            {
                $options = $prod->getOptions()->getElements();
                foreach($options as $option) {
                    $optionName = $option->getTranslated()['name'];
                    if (!empty($optionName)) {
                        $optionNames .= ' ' . $optionName;
                    }
                }
            }
        }

        return $name . $optionNames;
    }

    /**
     * @param     Request    $request
     * @return    array
     */
    public function getBaseParameters($request)
    {
        $this->config = $this->config ?? $this->configReaderHelper->getConfig();
        $parameters = [];
        $parameters['config'] = $this->config;
        $parameters['salesChannelIds'] = $request->query->get('salesChannelIds','');
        $parameters['customerGroupIds'] = $request->query->get('customerGroupIds','');
        $parameters['productSearchIds'] = $request->query->get('productSearchIds','');
        $parameters['adminLocalLanguage'] = trim($request->query->get('adminLocaleLanguage',''));
        $parameters['format'] = trim($request->query->get('format',''));
        $parameters['labels'] = trim($request->query->get('labels',''));

        $dates = $this->getDates($request);

        $blacklistedStatesIds = [];
        $blacklistedStatesIds['transaction'] = !empty($this->config['blacklistedTransactionStates']) ? $this->config['blacklistedTransactionStates'] : [];
        $blacklistedStatesIds['delivery'] = !empty($this->config['blacklistedDeliveryStates']) ? $this->config['blacklistedDeliveryStates'] : [];
        $blacklistedStatesIds['order'] = !empty($this->config['blacklistedOrderStates']) ? $this->config['blacklistedOrderStates'] : [];

        $parameters['startDate'] = $dates['startDate'];
        $parameters['endDate'] = $dates['endDate'];
        $parameters['blacklistedStatesIds'] = $blacklistedStatesIds;

        return $parameters;
    }

    public function getLanguageModifiedContext($context, $languageId)
    {
        $languageIdChain = array_unique(array_merge([$languageId], $context->getLanguageIdChain()));

        return new Context(
            $context->getSource(),
            $context->getRuleIds(),
            $context->getCurrencyId(),
            $languageIdChain,
            $context->getVersionId(),
            $context->getCurrencyFactor(),
            $context->considerInheritance(),
            $context->getTaxState(),
            $context->getRounding()
        );
    }

    public function getDataFromAggregations($result, $mainValue, $termAggregation, $entityAggregation)
    {
        $aggregation = $result->getAggregations()->get($termAggregation);
        $entityElements = $result->getAggregations()->get($entityAggregation)->getEntities()->getElements();

        $data = [];
        foreach ($aggregation->getBuckets() as $bucket)
        {
            if ($entityAggregation === 'salutations') {
                $name = $entityElements[$bucket->getKey()]->getTranslated()['displayName'] ?? 'Undefined';
                if (empty($name)) $name = 'Undefined';
            } else {
                $name = !empty($entityElements[$bucket->getKey()]) ? $entityElements[$bucket->getKey()]->getTranslated()['name'] : '';
            }

            if (!empty($name))
            {
                if ($mainValue === 'sales')
                {
                    $sum = $this->calculateAmountInSystemCurrency($bucket->getResult());
                    $data[] = [
                        'name' => $name,
                        'count' => (int)$bucket->getCount(),
                        'sum' => round($sum, 2)
                    ];
                } else {
                    $sales = $this->calculateAmountInSystemCurrency($bucket->getResult());
                    $data[] = [
                        'name' => $name,
                        'sum' => (int)$bucket->getCount(),
                        'sales' => round($sales, 2)
                    ];
                }

            }
        }

        return $this->sortArrayByColumn($data);
    }

    public function getProductTranslatedName($product, $productSearch, $variantsWithOptionNames = true)
    {
        $productName = $product->getTranslated()['name'];
        if (empty($productName) && !empty($product->getparentId()) && !empty($productSearch[$product->getparentId()]))
        {
            $productName = $productSearch[$product->getparentId()]->getTranslated()['name'];
        }
        if (empty($productName)) return '';
        if ($variantsWithOptionNames && !empty($product->getOptions()) && !empty($product->getOptions()->getElements()))
        {
            $optionNames = '';
            foreach ($product->getOptions() as $option)
            {
                if (!empty($option->getTranslated()['name']))
                {
                    $optionNames .= ' ' . $option->getTranslated()['name'];
                }
            }
            $productName .= ' - ' . $optionNames;
        }

        return $productName;
    }

    public function getProductDataFromAggrgation($result, $modifiedContext, $mainValue, $termAggregation)
    {
        $aggregation = $result->getAggregations()->get($termAggregation);
        $products = $result->getAggregations()->get('products')->getEntities()->getElements();
        $parents = $result->getAggregations()->get('parents')->getEntities()->getElements();
        $optionIds = [];
        $optionSearch = [];
        $data = [];

        foreach ($products as $product)
        {
            if (!empty($product->getOptionIds()))
            {
                $optionIds = array_unique(array_merge($optionIds, $product->getOptionIds()));
            }
        }
        if (!empty($optionIds))
        {
            $optionCriteria = new Criteria();
            $optionCriteria->addFilter(new EqualsAnyFilter('id', $optionIds));
            $optionSearch = $this->propertyGroupOptionRepository->search($optionCriteria, $modifiedContext)->getElements();
        }

        foreach ($aggregation->getBuckets() as $bucket)
        {
            $key = $bucket->getKey();
            if (empty($key) || empty($products[$key])) continue;
            $productNumber = $products[$key]->getProductNumber();
            $productName = $products[$key]->getTranslated()['name'];
            if (empty($productName) && !empty($products[$key]->getparentId()) && !empty($parents[$products[$key]->getparentId()]))
            {
                $productName = $parents[$products[$key]->getparentId()]->getTranslated()['name'];
            }
            if (empty($productName)) continue;
            if (!empty($products[$key]->getOptionIds()))
            {
                $optionNames = '';
                foreach ($products[$key]->getOptionIds() as $optionId)
                {
                    if (!empty($optionSearch[$optionId]) && !empty($optionSearch[$optionId]->getTranslated()['name']))
                    {
                        $optionNames .= ' ' . $optionSearch[$optionId]->getTranslated()['name'];
                    }
                }
                $productName .= ' - ' . $optionNames;
            }
            if ($mainValue === 'count')
            {
                $data[] = [
                    'id' => $key,
                    'number' => $productNumber,
                    'name' => $productName,
                    'sum' => (int)$bucket->getCount()
                ];
            }
        }

        return $this->sortArrayByColumn($data);
    }

    // for category-type statistics with possible to many datapoints for charts to handle
    public function limitData($data, $limit = 100)
    {
        if (count($data) <= $limit) {
            return $data;
        }

        $limitedArray = array_slice($data, 0, $limit);

        $restArray = array_slice($data, $limit);

        $restArraySumColumn = array_column($restArray, 'sum');
        $restSum = array_sum($restArraySumColumn);

        if (empty($data[0]['number'])) {

            $element = ['name' => 'cbax-analytics.data.others', 'sum' => $restSum];

        } else {

            $element = ['id' => '', 'number' => '', 'name' => 'cbax-analytics.data.others', 'sum' => $restSum];
        }

        foreach (['sales', 'count', 'sum1', 'sum2'] as $columnName)
        {
            if (!empty($data[0][$columnName]))
            {
                $restArrayColumn = array_column($restArray, $columnName);
                $element[$columnName] = array_sum($restArrayColumn);
            }
        }

        $limitedArray[] = $element;

        return $limitedArray;
    }

    public function getLanguageIdByLocaleCode($code, $context)
    {
        $languageId = '';

        $criteriaLocale = new Criteria;
        $criteriaLocale->addFilter(new EqualsFilter('code', $code));
        $local = $this->localeRepository->search($criteriaLocale, $context)->first();

        if (!empty($local))
        {
            $localId = $local->get('id');
            $criteriaLanguage = new Criteria();
            $criteriaLanguage->addFilter(new EqualsFilter('localeId', $localId));
            $language = $this->languageRepository->search($criteriaLanguage, $context)->first();
        }

        if (!empty($language))
        {
            $languageId = $language->get('id');
        }

        return $languageId;
    }

    // id for order state canceled, to exclude canceled orders from statistics
    public function getCanceledStateId($context)
    {
        $canceledId = '';

        $criteriaSM = new Criteria;
        $criteriaSM->addFilter(new EqualsFilter('technicalName', OrderStates::STATE_MACHINE));
        $orderState = $this->stateMachineRepository->search($criteriaSM, $context)->first();

        if (!empty($orderState))
        {
            $orderStateId = $orderState->get('id');
            $criteriaSMS = new Criteria();
            $criteriaSMS->addFilter(new EqualsFilter('technicalName', OrderStates::STATE_CANCELLED));
            $criteriaSMS->addFilter(new EqualsFilter('stateMachineId', $orderStateId));
            $canceledState = $this->stateMachineStateRepository->search($criteriaSMS, $context)->first();
        }

        if (!empty($canceledState))
        {
            $canceledId = $canceledState->get('id');
        }

        return $canceledId;
    }

    public function sortArrayByColumn($array, $columnName = 'sum', $direction = 'DESC')
    {
        usort($array, function($a, $b) use ($columnName, $direction) {
            if ($a[$columnName] == $b[$columnName])
            {
                return 0;
            }

            if ($direction === 'DESC') {
                return ($a[$columnName] > $b[$columnName]) ? -1 : 1;
            }

            if ($direction === 'ASC') {
                return ($a[$columnName] < $b[$columnName]) ? -1 : 1;
            }

        });

        return $array;
    }

    public function calculateAmountInSystemCurrency($currencyAggregaton)
    {
        $amount = 0;
        foreach ($currencyAggregaton->getBuckets() as $bucket)
        {
            $amount += $bucket->getResult()->getSum() / $bucket->getKey();
        }

        return $amount;
    }

    /**
     * @param     array    $gridData
     * @param     String    $labels
     * @return    Int|bool
     */
    public function exportCSV($gridData, $labels)
    {
        $this->config = $this->config ?? $this->configReaderHelper->getConfig();

        if (empty($this->config['csvSeparator']))
        {
            $separator = ",";
        } else {
            switch ($this->config['csvSeparator']) {
                case 'comma':
                    $separator = ",";
                    break;
                case 'semicolon':
                    $separator = ";";
                    break;
                case 'tab':
                    $separator = "\t";
                    break;
                case 'pipe':
                    $separator = "|";
                    break;
                default:
                    $separator = ",";
            }
        }

        if (empty($this->config['csvNumberFormat']))
        {
            $decimalSeperator = ".";
            $thousandsSeperator = "";
        } else {
            switch ($this->config['csvNumberFormat']) {
                case 'pointOnly':
                    $decimalSeperator = ".";
                    $thousandsSeperator = "";
                    break;
                case 'commaOnly':
                    $decimalSeperator = ",";
                    $thousandsSeperator = "";
                    break;
                case 'pointComma':
                    $decimalSeperator = ".";
                    $thousandsSeperator = ",";
                    break;
                case 'commaPoint':
                    $decimalSeperator = ",";
                    $thousandsSeperator = ".";
                    break;
                default:
                    $decimalSeperator = ".";
                    $thousandsSeperator = "";
            }
        }

        //$labels = utf8_decode($labels);
        $labelsArray = explode(';', $labels);
        $labels = implode($separator, $labelsArray);
        $content = $labels . "\r\n";

        foreach ($gridData as $line) {
            if (!empty($line['id'])) unset($line['id']);
            if (!empty($line['date']) && !empty($line['formatedDate'])) unset($line['date']);

            if (!empty($this->config['csvTextSeperator'])) {
                foreach ($line as &$entry) {
                    if (is_string($entry)) {
                        $entry = '"' . $entry . '"';
                    } elseif (is_float($entry)) {
                        $entry = number_format($entry, 2, $decimalSeperator, $thousandsSeperator);
                    }
                }
            } else {
                foreach ($line as &$entry) {
                    if (is_float($entry)) {
                        $entry = number_format($entry, 2, $decimalSeperator, $thousandsSeperator);
                    }
                }
            }

            $content .= implode($separator, $line) . "\r\n";
        }

        $this->fileSystemPrivate->put(self::EXPORT_FILE_NAME, $content);
        $size = $this->fileSystemPrivate->getSize(self::EXPORT_FILE_NAME);

        return $size;
    }

    /**
     * @param     String    $fileName
     * @param     Int|bool    $fileSize
     * @return    Response|JsonResponse
     */
    public function getDownloadResponse($fileName, $fileSize)
    {
        //self::EXPORT_FILE_NAME Name des Files in /Files/plugins/cbax_modul_analytics
        //$fileName Name des Files nach Download beim Kunden
        if ($fileSize != $this->fileSystemPrivate->getSize(self::EXPORT_FILE_NAME)) {
            return new JsonResponse(array("success" => false));
        }

        $headers = [
            'Content-Disposition' => HeaderUtils::makeDisposition(
                'attachment',
                $fileName,
                // only printable ascii
                preg_replace('/[\x00-\x1F\x7F-\xFF]/', '', $fileName)
            ),
            'Content-Length' => $fileSize,
            'Content-Type' => 'text/comma-separated-values'
        ];

        $content = $this->fileSystemPrivate->read(self::EXPORT_FILE_NAME);

        if ($content === FALSE) {
            return new JsonResponse(array("success" => false));
        }

        $response = new Response($content, Response::HTTP_OK, $headers);
        $this->fileSystemPrivate->delete(self::EXPORT_FILE_NAME);

        return $response;
    }

    public function getFormatedDate($date, $adminLocalLanguage = 'de-DE')
    {
        if (is_string($date))
        {
            if ($adminLocalLanguage === 'de-DE')
            {
                return \DateTime::createFromFormat('Y-m-d', explode(' ', $date)[0])->format('d.m.Y');
            }
            else
            {
                return \DateTime::createFromFormat('Y-m-d', explode(' ', $date)[0])->format('d/m/Y');
            }

        } else {

            if ($adminLocalLanguage === 'de-DE')
            {
                return $date->format('d.m.Y');
            }
            else
            {
                return $date->format('d/m/Y');
            }

        }

    }

    public function getDates($request)
    {
        $start = $request->query->get('start','');
        $end = $request->query->get('end','');

        $dates['startDate'] = \DateTime::createFromFormat('d/m/Y', $start)->format('Y-m-d');
        $dates['endDate'] = \DateTime::createFromFormat('d/m/Y', $end)->format('Y-m-d');

        return $dates;
    }

    public function getDatesFromRange($start, $end, $format = 'Y-m-d') {
        $array = array();
        $interval = new \DateInterval('P1D');

        $realEnd = new \DateTime($end);
        $realEnd->add($interval);

        $period = new \DatePeriod(new \DateTime($start), $interval, $realEnd);

        foreach($period as $date) {
            $array[] = $date->format($format);
        }

        return $array;
    }

    public function getVariantOptionsNames($product)
    {
        $optionNames = '';
        $options = $product->getOptions()->getElements();
        foreach($options as $option) {
            $optionName = $option->getTranslated()['name'];
            if (!empty($optionName)) {
                $optionNames .= ' ' . $optionName;
            }
        }
        return $optionNames;
    }

    public function getCanceledTransactionsStateIds($context)
    {
        $canceledIds = [];
        $criteriaSM = new Criteria;
        $criteriaSM->addFilter(new EqualsFilter('technicalName', OrderTransactionStates::STATE_MACHINE));
        $orderTransactionState = $this->stateMachineRepository->search($criteriaSM, $context)->first();

        if (!empty($orderTransactionState))
        {
            $orderTransactionStateId = $orderTransactionState->get('id');
            $criteriaSMS = new Criteria();
            $criteriaSMS->addFilter(new EqualsAnyFilter('technicalName', [OrderTransactionStates::STATE_CANCELLED, OrderTransactionStates::STATE_FAILED]));
            $criteriaSMS->addFilter(new EqualsFilter('stateMachineId', $orderTransactionStateId));
            $idResult = $this->stateMachineStateRepository->searchIds($criteriaSMS, $context);
        }

        if (!empty($idResult))
        {
            $canceledIds = $idResult->getIds();
        }

        return $canceledIds;
    }

    public function getTransactionsFilters($parameters)
    {
        if (empty($parameters['blacklistedStatesIds']['transaction']))
        {
            $filters = [];
        } else {
            $filters = [
                new NotFilter(
                    NotFilter::CONNECTION_OR,
                    [
                        new EqualsAnyFilter('transactions.stateMachineState.technicalName', ['cancelled', 'failed']),
                        new EqualsAnyFilter('transactions.stateId', $parameters['blacklistedStatesIds']['transaction'])
                    ]
                )
            ];
        }

        return $filters;
    }

    /**
     * @param     QueryBuilder    $query
     * @param     array    $parameters
     * @param     Context    $context
     * @return    QueryBuilder
     */
    public function setMoreQueryConditions($query, $parameters, $context)
    {
        if (!empty($parameters['salesChannelIds']))
        {
            array_walk($parameters['salesChannelIds'],
                function (&$value) { $value = Uuid::fromHexToBytes($value); }
            );

            $query->andWhere('orders.sales_channel_id IN (:salesChannels)')
                ->setParameter('salesChannels', $parameters['salesChannelIds'], Connection::PARAM_STR_ARRAY);
        }

        if (!empty($parameters['customerGroupIds']))
        {
            array_walk($parameters['customerGroupIds'],
                function (&$value) { $value = Uuid::fromHexToBytes($value); }
            );

            $query->leftJoin('orders', 'order_customer', 'ordercustomers',
                'orders.id = ordercustomers.order_id AND ordercustomers.version_id = :versionId')
                ->leftJoin('ordercustomers', 'customer', 'customers',
                    'ordercustomers.customer_id = customers.id')
                ->andWhere('customers.customer_group_id IN (:customerGroupIds)')
                ->setParameter('customerGroupIds', $parameters['customerGroupIds'], Connection::PARAM_STR_ARRAY);
        }

        if (!empty($parameters['blacklistedStatesIds']['order']))
        {
            array_walk($parameters['blacklistedStatesIds']['order'],
                function (&$value) { $value = Uuid::fromHexToBytes($value); }
            );

            $query->andWhere('orders.state_id NOT IN (:modStateIds)')
                ->setParameter('modStateIds', $parameters['blacklistedStatesIds']['order'], Connection::PARAM_STR_ARRAY);
        }

        if (!empty($parameters['blacklistedStatesIds']['delivery']))
        {
            array_walk($parameters['blacklistedStatesIds']['delivery'],
                function (&$value) { $value = Uuid::fromHexToBytes($value); }
            );

            $query->leftJoin('orders', 'order_delivery', 'deliveries',
                'orders.id = deliveries.order_id AND deliveries.version_id = :versionId')
                ->andWhere('deliveries.state_id NOT IN (:modDeliveryStateIds)')
                ->setParameter('modDeliveryStateIds', $parameters['blacklistedStatesIds']['delivery'], Connection::PARAM_STR_ARRAY);
        }

        if (!empty($parameters['blacklistedStatesIds']['transaction']))
        {
            $disregardedStatesIds = $this->getCanceledTransactionsStateIds($context);
            $blacklistedStatesIds = array_unique(array_merge($disregardedStatesIds, $parameters['blacklistedStatesIds']['transaction']));

            array_walk($blacklistedStatesIds, function (&$value) {
                $value = Uuid::fromHexToBytes($value);
            });

            $query->leftJoin('orders', 'order_transaction', 'transactions',
                'orders.id = transactions.order_id AND transactions.version_id = :versionId')
                ->andWhere('transactions.state_id NOT IN (:modTransactionStateIds)')
                ->setParameter('modTransactionStateIds', $blacklistedStatesIds, Connection::PARAM_STR_ARRAY);
        }

        return $query;
    }

    public function getBaseCriteria($dateColumn, $parameters, $forOrders = true)
    {
        if ($dateColumn === 'createdAt' || $dateColumn === 'orderDateTime')
        {
            $parameters['endDate'] .= ' 23:59:59.999';
        }
        $criteria = new Criteria();
        $criteria->setLimit(1);
        $criteria->addFilter(
            new RangeFilter($dateColumn, [
                RangeFilter::GTE => $parameters['startDate'],
                RangeFilter::LTE => $parameters['endDate']
            ])
        );

        if (!empty($parameters['salesChannelIds']))
        {
            $criteria->addFilter(new EqualsAnyFilter('salesChannelId', $parameters['salesChannelIds']));
        }

        if ($forOrders && !empty($parameters['customerGroupIds']))
        {
            $criteria->addAssociation('orderCustomer');
            $criteria->getAssociation('orderCustomer')->addAssociation('customer');
            $criteria->addFilter(new EqualsAnyFilter('orderCustomer.customer.groupId', $parameters['customerGroupIds']));
        }

        if ($forOrders && !empty($parameters['blacklistedStatesIds']['order']))
        {
            $criteria->addFilter(
                new NotFilter(
                    NotFilter::CONNECTION_OR,
                    [
                        new EqualsAnyFilter('stateId', $parameters['blacklistedStatesIds']['order'])
                    ]
                )
            );
        }

        if ($forOrders && !empty($parameters['blacklistedStatesIds']['transaction']))
        {
            $criteria->addAssociation('transactions');
        }

        if ($forOrders && !empty($parameters['blacklistedStatesIds']['delivery']))
        {
            $criteria->addAssociation('deliveries');
            $criteria->addFilter(
                new NotFilter(
                    NotFilter::CONNECTION_OR,
                    [
                        new EqualsAnyFilter('deliveries.stateId', $parameters['blacklistedStatesIds']['delivery'])
                    ]
                )
            );
        }

        return $criteria;
    }

    public function checkParentPurchasePrice($product, $productSearch)
    {
        if ($product->getParentId() == null) return $product;
        if (empty($productSearch[$product->getParentId()])) return $product;

        $parent = $productSearch[$product->getParentId()];

        if ($product->getPurchasePrices() == null)
        {
            $product->setPurchasePrices($parent->getPurchasePrices());
        }

        return $product;
    }

    /**
     * @param     ProductEntity    $product
     * @param     Context    $context
     * @param     array    $products
     * @return    float|NULL
     */
    public function calculatePrice($product, $products, $context)
    {
        $this->config = $this->config ?? $this->configReaderHelper->getConfig();

        $currencyId = $context->getCurrencyId();
        $parent = (!empty($product->getParentId()) && !empty($products[$product->getParentId()])) ? $products[$product->getParentId()] : null;

        // Main product oder Variante mit eigenem Default Preis
        if (!empty($product->getPrice()) && !empty($product->getPrice()->getCurrencyPrice($currencyId))) {
            $defaultPrice = $product->getPrice()->getCurrencyPrice($currencyId);

        } // Variante mit vererbtem Default Preis
        elseif (empty($product->getPrice()) && !empty($parent) && !empty($parent->getPrice())) {
            $defaultPrice = $parent->getPrice()->getCurrencyPrice($currencyId);

        } // Kein Default Preis, nicht möglich->fehlerhaftes Produkt
        else {
            return null;
        }

        if (!empty($this->config['grossOrNet']) && $this->config['grossOrNet'] == 'gross')
        {
            return round($defaultPrice->getGross(), 2);
        } else {
            return round($defaultPrice->getNet(), 2);
        }
    }

    public function calculatePurchasePrice(ProductEntity $product, $productSearch, $context)
    {
        $this->config = $this->config ?? $this->configReaderHelper->getConfig();

        $currencyId = $context->getCurrencyId();
        $purchasePrices = $product->getPurchasePrices();
        $parent = (!empty($product->getParentId()) && !empty($productSearch[$product->getParentId()])) ? $productSearch[$product->getParentId()] : null;
        if (empty($purchasePrices) && empty($parent)) return null;
        if (empty($purchasePrices))
        {
            $purchasePrices = $parent->getPurchasePrices();
        }

        if (empty($purchasePrices)) return null;

        if (!empty($this->config['grossOrNet']) && $this->config['grossOrNet'] == 'gross')
        {
            $purchasePrice = $purchasePrices->getCurrencyPrice($currencyId)->getGross();
        } else {
            $purchasePrice = $purchasePrices->getCurrencyPrice($currencyId)->getNet();
        }

        return round($purchasePrice, 2);
    }

    public function getProductsForOverviews($salesChannelIds, $context, $prices = false)
    {
        $criteria = new Criteria();

        $criteria->addFilter(
            new NotFilter(
                NotFilter::CONNECTION_OR,
                [
                    new EqualsFilter('active', null)
                ]
            )
        );
        $criteria->addAssociation('options');
        $criteria->getAssociation('options')
            ->addSorting(new FieldSorting('groupId'))
            ->addSorting(new FieldSorting('id'));

        if (!empty($salesChannelIds)) {
            $criteria->addAssociation('visibilities');
            $criteria->addFilter(new EqualsAnyFilter('visibilities.salesChannelId', $salesChannelIds));
        }

        if ($prices) {
            $criteria->addAssociation('prices');
        }

        $productSearch = $this->productRepository->search($criteria, $context)->getElements();

        $criteria2 = new Criteria();

        $criteria2->addFilter(new EqualsFilter('active', null));
        $criteria2->addFilter(
            new NotFilter(
                NotFilter::CONNECTION_OR,
                [
                    new EqualsFilter('parentId', null)
                ]
            )
        );
        $criteria2->addAssociation('options');
        $criteria2->getAssociation('options')
            ->addSorting(new FieldSorting('groupId'))
            ->addSorting(new FieldSorting('id'));

        if (!empty($salesChannelIds)) {
            $criteria2->addAssociation('visibilities');
            $criteria2->addFilter(new EqualsAnyFilter('visibilities.salesChannelId', $salesChannelIds));
        }

        $variantSearch = $this->productRepository->search($criteria2, $context)->getElements();

        foreach ($variantSearch as $variant)
        {
            if (!empty($productSearch[$variant->getParentId()]))
            {
                $variant->setActive($productSearch[$variant->getParentId()]->getActive());
                $productSearch[] = $variant;
            }
        }

        return $productSearch;
    }
}
