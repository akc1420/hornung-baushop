<?php declare(strict_types = 1);

namespace Cbax\ModulAnalytics\Components\Statistics;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Doctrine\DBAL\Connection;
use Shopware\Core\Content\ProductStream\Service\ProductStreamBuilder;

use Cbax\ModulAnalytics\Bootstrap\Database;
use Cbax\ModulAnalytics\Components\Base;

class Connector
{
    private $base;
    private $orderRepository;
    private $languageRepository;
    private $productManufacturerRepository;
    private $productRepository;
    private $customerRepository;
    private $propertyGroupOptionRepository;
    private $connection;
    private $searchRepository;
    private $productStreamRepository;
    private $productStreamBuilder;
    private $productImpressionRepository;
    private $visitorsRepository;
    private $refererRepository;
    private $categoryImpressionsRepository;
    private $categoryRepository;
    private $manufacturerImpressionsRepository;
    private $crossSellingRepository;
    private $customerPoolRepository;

    public function __construct(
        Base $base,
        EntityRepositoryInterface $orderRepository,
        EntityRepositoryInterface $languageRepository,
        EntityRepositoryInterface $productRepository,
        EntityRepositoryInterface $customerRepository,
        EntityRepositoryInterface $propertyGroupOptionRepository,
        EntityRepositoryInterface $searchRepository,
        EntityRepositoryInterface $productStreamRepository,
        EntityRepositoryInterface $productManufacturerRepository,
        EntityRepositoryInterface $productImpressionRepository,
        EntityRepositoryInterface $visitorsRepository,
        EntityRepositoryInterface $refererRepository,
        EntityRepositoryInterface $categoryImpressionsRepository,
        EntityRepositoryInterface $categoryRepository,
        EntityRepositoryInterface $manufacturerImpressionsRepository,
        EntityRepositoryInterface $crossSellingRepository,
        EntityRepositoryInterface $customerPoolRepository,
        ProductStreamBuilder $productStreamBuilder,
        Connection $connection
    )
    {
        $this->base = $base;
        $this->orderRepository = $orderRepository;
        $this->languageRepository = $languageRepository;
        $this->productRepository = $productRepository;
        $this->customerRepository = $customerRepository;
        $this->propertyGroupOptionRepository = $propertyGroupOptionRepository;
        $this->searchRepository = $searchRepository;
        $this->connection = $connection;
        $this->productStreamRepository = $productStreamRepository;
        $this->productStreamBuilder = $productStreamBuilder;
        $this->productManufacturerRepository = $productManufacturerRepository;
        $this->productImpressionRepository = $productImpressionRepository;
        $this->visitorsRepository = $visitorsRepository;
        $this->refererRepository = $refererRepository;
        $this->categoryImpressionsRepository = $categoryImpressionsRepository;
        $this->categoryRepository = $categoryRepository;
        $this->manufacturerImpressionsRepository = $manufacturerImpressionsRepository;
        $this->crossSellingRepository = $crossSellingRepository;
        $this->customerPoolRepository = $customerPoolRepository;
    }

    // Produkte Lager
    public function getProductsInventory($parameters, $context)
    {
        $getStatisticsData = new ProductsInventory($parameters['config'], $this->base);

        $data = $getStatisticsData->getProductsInventory($parameters, $context);

        return $data;
    }

    // Produkte Profit
    public function getProductsProfit($parameters, $context)
    {
        $getStatisticsData = new ProductsProfit($parameters['config'], $this->base, $this->orderRepository);

        $data = $getStatisticsData->getProductsProfit($parameters, $context);

        return $data;
    }

    // Produkte Varianten Vergleich
    public function getVariantsCompare($parameters, $context)
    {
        $getStatisticsData = new VariantsCompare($parameters['config'], $this->base, $this->propertyGroupOptionRepository, $this->productRepository, $this->connection);

        $data = $getStatisticsData->getVariantsCompare($parameters, $context);

        return $data;
    }

    /* Produkte Stream-Vergleich
    public function getProductStream($startDate, $endDate, $salesChannelId, $context, $adminLocalLanguage, $format, $labels, $productStreamId, $sorting)
    {
        $getStatisticsData = new ProductStream($parameters['config'], $this->base, $this->productRepository, $this->connection, $this->productStreamRepository, $this->productStreamBuilder);

        $data = $getStatisticsData->getProductStream($startDate, $endDate, $salesChannelId, $context, $adminLocalLanguage, $format, $labels, $productStreamId, $sorting);

        return $data;
    }
    */

    // Produkte mit Streamfilter
    public function getSalesByProductsFilter($parameters, $context)
    {
        $getStatisticsData = new SalesByProductsFilter($parameters['config'], $this->base, $this->productRepository, $this->connection, $this->productStreamRepository, $this->productStreamBuilder);

        $data = $getStatisticsData->getSalesByProductsFilter($parameters, $context);

        return $data;
    }

    // Suche Begriffe
    public function getSearchTerms($parameters, $context)
    {
        $getStatisticsData = new SearchTerms($parameters['config'], $this->base, $this->searchRepository);

        $data = $getStatisticsData->getSearchTerms($parameters, $context);

        return $data;
    }

    // Suche Anzahl
    public function getSearchActivity($parameters, $context)
    {
        $getStatisticsData = new SearchActivity($parameters['config'], $this->base, $this->searchRepository);

        $data = $getStatisticsData->getSearchActivity($parameters, $context);

        return $data;
    }

    // ?
    public function getSearchTrends($parameters, $context)
    {
        $getStatisticsData = new SearchTrends($parameters['config'], $this->base, $this->searchRepository);

        $data = $getStatisticsData->getSearchTrends($parameters, $context);

        return $data;
    }

    //Umsatz nach Gerät
    public function getSalesByDevice($parameters, $context)
    {
        $getStatisticsData = new SalesByDevice($parameters['config'], $this->base, $this->connection);

        $data = $getStatisticsData->getSalesByDevice($parameters, $context);

        return $data;
    }

    //Umsatz nach OS
    public function getSalesByOs($parameters, $context)
    {
        $getStatisticsData = new SalesByOs($parameters['config'], $this->base, $this->connection);

        $data = $getStatisticsData->getSalesByOs($parameters, $context);

        return $data;
    }

    //Umsatz nach Browser
    public function getSalesByBrowser($parameters, $context)
    {
        $getStatisticsData = new SalesByBrowser($parameters['config'], $this->base, $this->connection);

        $data = $getStatisticsData->getSalesByBrowser($parameters, $context);

        return $data;
    }

    // Anzahl täglicher Bestellungen
    public function getOrdersCountAll($parameters, $context)
    {
        $getStatisticsData = new OrdersCountAll($parameters['config'], $this->base, $this->orderRepository, $this->connection);
        $data = $getStatisticsData->getOrdersCountAll($parameters, $context);

        return $data;
    }

    // täglicher Umsatz
    public function getSalesAll($parameters, $context)
    {
        $getStatisticsData = new SalesAll($parameters['config'], $this->base, $this->orderRepository);
        $data = $getStatisticsData->getSalesAll($parameters, $context);

        return $data;
    }

    // Umsatz monatlich
    public function getSalesByMonth($parameters, $context)
    {
        $getStatisticsData = new SalesByMonth($parameters['config'], $this->base, $this->orderRepository);

        $data = $getStatisticsData->getSalesByMonth($parameters, $context);

        return $data;
    }

    // Umsatz nach Zahlungsart
    public function getSalesBySaleschannels($parameters, $context)
    {
        $getStatisticsData = new SalesBySaleschannels($parameters['config'], $this->base, $this->orderRepository);

        $data = $getStatisticsData->getSalesBySaleschannels($parameters, $context);

        return $data;
    }

    // Umsatz nach Sprache
    public function getSalesByLanguage($parameters, $context)
    {
        $getStatisticsData = new SalesByLanguage($parameters['config'], $this->base, $this->orderRepository, $this->languageRepository);

        $data = $getStatisticsData->getSalesByLanguage($parameters, $context);

        return $data;
    }

    // Umsatz nach Partner
    public function getSalesByAffiliates($parameters, $context)
    {
        $getStatisticsData = new SalesByAffiliates($parameters['config'], $this->base, $this->orderRepository);

        $data = $getStatisticsData->getSalesByAffiliates($parameters, $context);

        return $data;
    }

    // Umsatz nach Kampagne
    public function getSalesByCampaign($parameters, $context)
    {
        $getStatisticsData = new SalesByCampaign($parameters['config'], $this->base, $this->orderRepository);

        $data = $getStatisticsData->getSalesByCampaign($parameters, $context);

        return $data;
    }

    // Umsatz nach Zahlungsart
    public function getSalesByPayment($parameters, $context)
    {
        $getStatisticsData = new SalesByPayment($parameters['config'], $this->base, $this->orderRepository);

        $data = $getStatisticsData->getSalesByPayment($parameters, $context);

        return $data;
    }

    // Umsatz nach Zahlungsart
    public function getSalesByShipping($parameters, $context)
    {
        $getStatisticsData = new SalesByShipping($parameters['config'], $this->base, $this->orderRepository);

        $data = $getStatisticsData->getSalesByShipping($parameters, $context);

        return $data;
    }

    // Umsatz nach Hersteller
    public function getSalesByManufacturer($parameters, $context)
    {
        $getStatisticsData = new SalesByManufacturer($parameters['config'], $this->base, $this->productManufacturerRepository, $this->connection);

        $data = $getStatisticsData->getSalesByManufacturer($parameters, $context);

        return $data;
    }

    // Umsatz nach Produkt
    public function getSalesByProducts($parameters, $context)
    {
        $getStatisticsData = new SalesByProducts($parameters['config'], $this->base, $this->productRepository, $this->connection);

        $data = $getStatisticsData->getSalesByProducts($parameters, $context);

        return $data;
    }

    // Anzahl Verkäufe nach Produkt
    public function getCountByProducts($parameters, $context)
    {
        $getStatisticsData = new CountByProducts($parameters['config'], $this->base, $this->orderRepository, $this->productRepository);

        $data = $getStatisticsData->getCountByProducts($parameters, $context);

        return $data;
    }

    // Umsatz nach Liefer-Land des Kunden
    public function getSalesByCountry($parameters, $context)
    {
        $getStatisticsData = new SalesByCountry($parameters['config'], $this->base, $this->orderRepository);

        $data = $getStatisticsData->getSalesByCountry($parameters, $context);

        return $data;
    }

    // Umsatz nach Rechnungs-Land des Kunden
    public function getSalesByBillingCountry($parameters, $context)
    {
        $getStatisticsData = new SalesByBillingCountry($parameters['config'], $this->base, $this->orderRepository);

        $data = $getStatisticsData->getSalesByBillingCountry($parameters, $context);

        return $data;
    }

    // Umsatz nach Kundengruppen
    public function getSalesByCustomergroups($parameters, $context)
    {
        $getStatisticsData = new SalesByCustomergroups($parameters['config'], $this->base, $this->orderRepository);

        $data = $getStatisticsData->getSalesByCustomergroups($parameters, $context);

        return $data;
    }

    // Umsatz nach Wochentag
    public function getSalesByWeekdays($parameters, $context)
    {
        $getStatisticsData = new SalesByWeekdays($parameters['config'], $this->base, $this->orderRepository);

        $data = $getStatisticsData->getSalesByWeekdays($parameters, $context);

        return $data;
    }

    // Umsatz nach Stunde
    public function getSalesByTime($parameters, $context)
    {
        $getStatisticsData = new SalesByTime($parameters['config'], $this->base, $this->orderRepository);

        $data = $getStatisticsData->getSalesByTime($parameters, $context);

        return $data;
    }

    // Orders nach Bestellstatus
    public function getOrdersByStatus($parameters, $context)
    {
        $getStatisticsData = new OrdersByStatus($parameters['config'], $this->base, $this->orderRepository);

        $data = $getStatisticsData->getOrdersByStatus($parameters, $context);

        return $data;
    }

    // Produkte low instock
    public function getProductLowInstock($parameters, $context)
    {
        $getStatisticsData = new ProductLowInstock($parameters['config'], $this->base, $this->productRepository);

        $data = $getStatisticsData->getProductLowInstock($parameters, $context);

        return $data;
    }

    // Produkte high instock
    public function getProductHighInstock($parameters, $context)
    {
        $getStatisticsData = new ProductHighInstock($parameters['config'], $this->base);

        $data = $getStatisticsData->getProductHighInstock($parameters, $context);

        return $data;
    }

    // Umsatz nach Gutschein
    public function getSalesByPromotion($parameters, $context)
    {
        $getStatisticsData = new SalesByPromotion($parameters['config'], $this->base, $this->connection);

        $data = $getStatisticsData->getSalesByPromotion($parameters, $context);

        return $data;
    }

    // Umsatz nach Rabatt
    public function getSalesByPromotionOthers($parameters, $context)
    {
        $getStatisticsData = new SalesByPromotionOthers($parameters['config'], $this->base, $this->connection);

        $data = $getStatisticsData->getSalesByPromotionOthers($parameters, $context);

        return $data;
    }

    // nicht aktive Produkte mit Lagerbestand
    public function getProductInactiveWithInstock($parameters, $context)
    {
        $getStatisticsData = new ProductInactiveWithInstock($parameters['config'], $this->base);

        $data = $getStatisticsData->getProductInactiveWithInstock($parameters, $context);

        return $data;
    }

    // Anzahl Orders mit Produkt
    public function getProductByOrders($parameters, $context)
    {
        $getStatisticsData = new ProductByOrders($parameters['config'], $this->base, $this->orderRepository);

        $data = $getStatisticsData->getProductByOrders($parameters, $context);

        return $data;
    }

    // Umsatz nach Kunden
    public function getSalesByCustomer($parameters, $context)
    {
        $getStatisticsData = new SalesByCustomer($parameters['config'], $this->base, $this->orderRepository);

        $data = $getStatisticsData->getSalesByCustomer($parameters, $context);

        return $data;
    }

    // Neukundenanmeldungen
    public function getNewCustomersByTime($parameters, $context)
    {
        $getStatisticsData = new NewCustomersByTime($parameters['config'], $this->base, $this->customerRepository);

        $data = $getStatisticsData->getNewCustomersByTime($parameters, $context);

        return $data;
    }

    // Kunden Altersverteilung
    public function getCustomerAge($parameters, $context)
    {
        $getStatisticsData = new CustomerAge($parameters['config'], $this->base, $this->customerRepository);

        $data = $getStatisticsData->getCustomerAge($parameters, $context);

        return $data;
    }

    // Kunden online
    public function getCustomerOnline($parameters, $context)
    {
        $getStatisticsData = new CustomerOnline($this->base, $this->customerPoolRepository, $this->customerRepository);

        $data = $getStatisticsData->getCustomerOnline($parameters, $context);

        return $data;
    }

    // Produkte die voraussetzlich bals ausverkauft sein werden
    public function getProductSoonOutstock($parameters, $context)
    {
        $getStatisticsData = new ProductSoonOutstock($parameters['config'], $this->base, $this->orderRepository, $this->propertyGroupOptionRepository);

        $data = $getStatisticsData->getProductSoonOutstock($parameters, $context);

        return $data;
    }

    // Orders nach Zahlungsstatus
    public function getOrdersByTransactionStatus($parameters, $context)
    {
        $getStatisticsData = new OrdersByTransactionStatus($parameters['config'], $this->base, $this->orderRepository);

        $data = $getStatisticsData->getOrdersByTransactionStatus($parameters, $context);

        return $data;
    }

    // Orders nach Lieferstatus
    public function getOrdersByDeliveryStatus($parameters, $context)
    {
        $getStatisticsData = new OrdersByDeliveryStatus($parameters['config'], $this->base, $this->orderRepository);

        $data = $getStatisticsData->getOrdersByDeliveryStatus($parameters, $context);

        return $data;
    }

    // Orders nach Lieferstatus
    public function getQuickOverview($parameters, $context)
    {
        $getStatisticsData = new QuickOverview($parameters['config'], $this->base, $this->orderRepository, $this->customerRepository, $this->connection);

        $data = $getStatisticsData->getQuickOverview($parameters, $context);

        return $data;
    }

    // abgebrochene Orders
    public function getUnfinishedOrders($parameters, $context)
    {
        $getStatisticsData = new UnfinishedOrders($parameters['config'], $this->base, $this->connection);

        $data = $getStatisticsData->getUnfinishedOrders($parameters, $context);

        return $data;
    }

    // abgebrochene Orders nach Zahlart
    public function getUnfinishedOrdersByPayment($parameters, $context)
    {
        $getStatisticsData = new UnfinishedOrdersByPayment($parameters['config'], $this->base, $this->connection);

        $data = $getStatisticsData->getUnfinishedOrdersByPayment($parameters, $context);

        return $data;
    }

    // abgebrochene Orders nach Cart
    public function getUnfinishedOrdersByCart($parameters, $context)
    {
        $getStatisticsData = new UnfinishedOrdersByCart($parameters['config'], $this->base, $this->connection);

        $data = $getStatisticsData->getUnfinishedOrdersByCart($parameters, $context);

        return $data;
    }

    // stornierte Orders monatlich
    public function getCanceledOrdersByMonth($parameters, $context)
    {
        $getStatisticsData = new CanceledOrdersByMonth($parameters['config'], $this->base, $this->orderRepository);

        $data = $getStatisticsData->getCanceledOrdersByMonth($parameters, $context);

        return $data;
    }

    // geklickte Produkte
    public function getProductImpressions($parameters, $context)
    {
        $getStatisticsData = new ProductImpressions($parameters['config'], $this->base, $this->productImpressionRepository, $this->productRepository);

        $data = $getStatisticsData->getProductImpressions($parameters, $context);

        return $data;
    }

    // Besucher pro Tag
    public function getVisitors($parameters, $context)
    {
        $getStatisticsData = new Visitors($parameters['config'], $this->base, $this->visitorsRepository);

        $data = $getStatisticsData->getVisitors($parameters, $context);

        return $data;
    }

    // geklickte Seiten
    public function getVisitorImpressions($parameters, $context)
    {
        $getStatisticsData = new VisitorImpressions($parameters['config'], $this->base, $this->visitorsRepository);

        $data = $getStatisticsData->getVisitorImpressions($parameters, $context);

        return $data;
    }

    // Besucher nach Zugriffsquellen
    public function getReferer($parameters, $context)
    {
        $getStatisticsData = new Referer($parameters['config'], $this->base, $this->refererRepository);

        $data = $getStatisticsData->getReferer($parameters, $context);

        return $data;
    }

    // geklickte Kategorien
    public function getCategoryImpressions($parameters, $context)
    {
        $getStatisticsData = new CategoryImpressions($parameters['config'], $this->base, $this->categoryImpressionsRepository, $this->categoryRepository);

        $data = $getStatisticsData->getCategoryImpressions($parameters, $context);

        return $data;
    }

    // geklickte Hersteller
    public function getManufacturerImpressions($parameters, $context)
    {
        $getStatisticsData = new ManufacturerImpressions($parameters['config'], $this->base, $this->manufacturerImpressionsRepository, $this->productManufacturerRepository);

        $data = $getStatisticsData->getManufacturerImpressions($parameters, $context);

        return $data;
    }

    // geklickte Lexikon Links
    public function getLexiconImpressions($parameters, $context)
    {
        if (Database::tableExist('cbax_lexicon_entry', $this->connection))
        {
            try {
                $getStatisticsData = new LexiconImpressions($parameters['config'], $this->base, $this->connection);

                $data = $getStatisticsData->getLexiconImpressions($parameters, $context);

                return $data;

            } catch (\Exception $e) {
                return false;
            }

        } else {

            return false;
        }
    }

    // Statistics for a single product
    public function getSingleProduct($parameters, $context)
    {
        $getStatisticsData = new SingleProduct($this->base, $this->productImpressionRepository, $this->orderRepository);

        $data = $getStatisticsData->getSingleProduct($parameters, $context);

        return $data;
    }

    // Cross-Selling Statistics for a single product
    public function getCrossSelling($parameters, Context $context)
    {
        if (Database::tableExist('cbax_cross_selling_also_bought', $this->connection) && Database::tableExist('cbax_cross_selling_also_viewed', $this->connection))
        {
            try {
                $getStatisticsData = new CrossSelling($this->base, $this->productRepository, $this->crossSellingRepository, $this->connection, $this->productStreamBuilder);

                $data = $getStatisticsData->getCrossSelling($parameters, $context);

                return $data;

            } catch (\Exception $e) {
                return false;
            }

        } else {

            return false;
        }

    }

    //Umsatz nach Steuerrate
    public function getSalesByTaxrate($parameters, $context)
    {
        $getStatisticsData = new SalesByTaxrate($this->base, $this->connection);

        $data = $getStatisticsData->getSalesByTaxrate($parameters, $context);

        return $data;
    }

    //Umsatz nach Anrede
    public function getSalesBySalutation($parameters, $context)
    {
        $getStatisticsData = new SalesBySalutation($parameters['config'], $this->base, $this->orderRepository);

        $data = $getStatisticsData->getSalesBySalutation($parameters, $context);

        return $data;
    }

    //Kunden nach Anrede
    public function getCustomerBySalutation($parameters, $context)
    {
        $getStatisticsData = new CustomerBySalutation($this->base, $this->customerRepository);

        $data = $getStatisticsData->getCustomerBySalutation($parameters, $context);

        return $data;
    }

    // Umsatz nach Zahlungsart
    public function getSalesByCurrency($parameters, $context)
    {
        $getStatisticsData = new SalesByCurrency($parameters['config'], $this->base, $this->orderRepository);

        $data = $getStatisticsData->getSalesByCurrency($parameters, $context);

        return $data;
    }

}

