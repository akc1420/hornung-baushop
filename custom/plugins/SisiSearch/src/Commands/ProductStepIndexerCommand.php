<?php

namespace Sisi\Search\Commands;

use Shopware\Core\Content\Product\AbstractPropertyGroupSorter;
use Shopware\Core\System\SalesChannel\Context\AbstractSalesChannelContextFactory;
use Sisi\Search\Service\StartService;
use Sisi\Search\ServicesInterfaces\InterfaceSearchCategorieService;
use Symfony\Bridge\Monolog\Logger;
use Sisi\Search\ESindexing\ProduktDataIndexer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Doctrine\DBAL\Connection;
use Shopware\Elasticsearch\Framework\ElasticsearchHelper;
use Shopware\Core\Checkout\Cart\Price\QuantityPriceCalculator;
use Sisi\Search\Service\TextService;
use Symfony\Component\ErrorHandler\Debug;

/**
 * Class ProductStepIndexerCommand
 * @package Sisi\Search\Commands
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ProductStepIndexerCommand extends Command
{
    protected static $defaultName = 'sisi-Produkt-Stepindex:start';

    /**
     * @var Connection
     */
    protected $connection;

    /**
     *
     * @var Context
     */
    protected $context;

    /**
     *
     * @var SystemConfigService
     */
    protected $config;

    /**
     *
     * @var ElasticsearchHelper
     */
    protected $helper;


    /**
     *
     * @var ContainerInterface
     */
    protected $container;


    /**
     *
     * @var produktDataindexer
     */
    protected $produktDataindexer;


    /**
     *
     * @var QuantityPriceCalculator
     */
    private $priceCalculator;


    /**
     *
     * @var  AbstractSalesChannelContextFactory
     */
    private $salesChannelContextFactory;


    /**
     *
     * @var Logger
     */
    private $loggingService;

    /**
     * @var InterfaceSearchCategorieService
     */
    protected $searchCategorieService;


    /**
     * @var AbstractPropertyGroupSorter
     */
    protected $propertyGroupSorter;

    /**
     * @param Connection $connection
     * @param ContainerInterface $container
     * @param SystemConfigService $config
     * @param ElasticsearchHelper $helper
     * @param ProduktDataIndexer $produktDataIndexer
     * @param QuantityPriceCalculator $priceCalculator
     * @param AbstractSalesChannelContextFactory $salesChannelContextFactory
     * @param Logger $loggingService
     * @param InterfaceSearchCategorieService $searchCategorieService
     * @param AbstractPropertyGroupSorter $propertyGroupSorter
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        Connection $connection,
        ContainerInterface $container,
        SystemConfigService $config,
        ElasticsearchHelper $helper,
        ProduktDataIndexer $produktDataIndexer,
        QuantityPriceCalculator $priceCalculator,
        AbstractSalesChannelContextFactory $salesChannelContextFactory,
        Logger $loggingService,
        InterfaceSearchCategorieService $searchCategorieService,
        AbstractPropertyGroupSorter $propertyGroupSorter
    ) {
        parent::__construct();
        $this->connection = $connection;
        $this->container = $container;
        $this->config = $config;
        $this->helper = $helper;
        $this->produktDataindexer = $produktDataIndexer;
        $this->priceCalculator = $priceCalculator;
        $this->salesChannelContextFactory = $salesChannelContextFactory;
        $this->loggingService = $loggingService;
        $this->searchCategorieService = $searchCategorieService;
        $this->propertyGroupSorter = $propertyGroupSorter;
    }

    protected function configure(): void
    {
        $this->addArgument('shop', InputArgument::OPTIONAL, 'Shop Channel');
        $this->addArgument('shopID', InputArgument::OPTIONAL, 'Shop Channel Id');
        $this->addArgument('limit', InputArgument::OPTIONAL, 'You can add the limit');
        $this->addArgument('main', InputArgument::OPTIONAL, 'Only add main Products in the Index');


        $this->addArgument(
            'time',
            InputArgument::OPTIONAL,
            'Enter the days which will be used for the update index process'
        );
        $this->addArgument(
            'update',
            InputArgument::OPTIONAL,
            'Update the index with update="1"'
        );

        $this->addArgument(
            'language',
            InputArgument::OPTIONAL,
            'This parameter is necessary when you want use not the default language'
        );
        $this->addArgument(
            'languageID',
            InputArgument::OPTIONAL,
            'This parameter is necessary when you want use not the default language and you know the language id'
        );
        $this->addArgument(
            'offset',
            InputArgument::OPTIONAL
        );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Step indexer started now please wait..');
        Debug::enable();
        $startHeandler = new StartService();
        $arguments = $input->getArguments();
        $texthaendler = new TextService();
        $parameter = $texthaendler->stripOption($arguments);
        if (!array_key_exists('limit', $arguments)) {
            $parameter['limit'] = 1000;
        }
        if (empty($parameter['limit'])) {
            $parameter['limit'] = 1000;
        }
        $parameter['shop'] = $input->getArgument('shop');
        $parameter['propertyGroupSorter'] = $this->propertyGroupSorter;
        $startHeandler->startTheIndexing(
            $this->config,
            $this->produktDataindexer,
            $this->connection,
            $this->container,
            $this->priceCalculator,
            $this->salesChannelContextFactory,
            $this->loggingService,
            $parameter,
            $output,
            $this->searchCategorieService
        );

        return 0;
    }
}
