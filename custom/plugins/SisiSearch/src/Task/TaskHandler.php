<?php

declare(strict_types=1);

namespace Sisi\Search\Task;

use Doctrine\DBAL\Connection;
use Exception;
use Shopware\Core\Checkout\Cart\Price\QuantityPriceCalculator;
use Shopware\Core\Content\Product\AbstractPropertyGroupSorter;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskHandler;
use Shopware\Core\System\SalesChannel\Context\CachedSalesChannelContextFactory;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Elasticsearch\Framework\ElasticsearchHelper;
use Sisi\Search\Core\Content\Task\Bundle\DBSchedularEntity;
use Sisi\Search\ESindexing\ProduktDataIndexer;
use Sisi\Search\Service\ContextService;
use Sisi\Search\Service\DeleteService;
use Sisi\Search\Service\StartService;
use Sisi\Search\Service\TaskService;
use Sisi\Search\ServicesInterfaces\InterfaceSearchCategorieService;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\System\SalesChannel\Context\AbstractSalesChannelContextFactory;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class TaskHandler extends ScheduledTaskHandler
{
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
     * @var AbstractSalesChannelContextFactory
     */
    private $salesChannelContextFactory;


    /**
     *
     * @var Logger
     */
    private $loggingService;


    /**
     *
     * @var EntityRepositoryInterface
     */
    protected $scheduledTaskRepository;


    /**
     * @var InterfaceSearchCategorieService
     */
    protected $searchCategorieService;

    /**
     * @var AbstractPropertyGroupSorter
     */
    protected $propertyGroupSorter;

    /**
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
        EntityRepositoryInterface $scheduledTaskRepository,
        InterfaceSearchCategorieService $searchCategorieService,
        AbstractPropertyGroupSorter $propertyGroupSorter
    ) {
        $this->connection = $connection;
        $this->container = $container;
        $this->config = $config;
        $this->helper = $helper;
        $this->produktDataindexer = $produktDataIndexer;
        $this->priceCalculator = $priceCalculator;
        $this->salesChannelContextFactory = $salesChannelContextFactory;
        $this->loggingService = $loggingService;
        $this->scheduledTaskRepository = $scheduledTaskRepository;
        $this->searchCategorieService = $searchCategorieService;
        $this->propertyGroupSorter = $propertyGroupSorter;
        parent::__construct($scheduledTaskRepository);
    }

    public static function getHandledMessages(): iterable
    {
        return [Task::class];
    }

    /**
     *
     */
    public function run(): void
    {
        try {
            $contextHaendler = new ContextService();
            $taskheandler = new TaskService();
            $context = $contextHaendler->getContext();
            $repository = $this->container->get('sisi_search_es_scheduledtask.repository');
            $results = $taskheandler->addAllSisiTask($repository, $context);
            foreach ($results as $result) {
                echo "Task is running \n";
                if ($taskheandler->ifLogik($result, $repository, $context)) {
                    echo "Insert \n";
                    $paramter = [];
                    $paramter["limit"] = $result->getLimit();
                    $paramter["shopID"] = $result->getShop();
                    $paramter["shop"] = '';
                    $paramter['propertyGroupSorter'] = $this->propertyGroupSorter;
                    $this->checkUpdate($paramter, $result);
                    $this->checkLanguage($paramter, $result);
                    $kind = $result->getKind();
                    if ($kind === 'index' || $kind === 'update' || $kind === 'updateG') {
                        $this->checkIsUpadate($kind, $paramter);
                        $startHeandler = new StartService();
                        $startHeandler->startTheIndexing(
                            $this->config,
                            $this->produktDataindexer,
                            $this->connection,
                            $this->container,
                            $this->priceCalculator,
                            $this->salesChannelContextFactory,
                            $this->loggingService,
                            $paramter,
                            null,
                            $this->searchCategorieService
                        );
                        echo "nach insert";
                    }
                    if ($result->getKind() === 'delete') {
                        $options[] = "all=" . $result->getAll();
                        echo "Delete \n";
                        $options[] = 'shop=' . $result->getShop();
                        $delteheandler = new DeleteService();
                        $delteheandler->deleteIndex(
                            $options,
                            null,
                            $this->connection,
                            $this->config,
                            $this->loggingService,
                            $this->container
                        );
                        echo "Delete Finish läuft \n";
                    }
                    $message = "SisiSearch scheduled task are now finish \n";
                    $this->loggingService->log(100, $message);
                    echo $message;
                }
            }
        } catch (Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    private function checkIsUpadate(string $kind, array &$paramter): void
    {
        if ($kind === 'update') {
            $paramter["update"] = '1';
        }
        if ($kind === 'updateG') {
            $paramter["update"] = '2';
        }
    }

    private function checkLanguage(&$paramter, DBSchedularEntity $result): void
    {
        $language = $result->getLanguage();
        if (!empty($language)) {
            $paramter["languageID"] = $language;
        }
    }

    private function checkUpdate(&$paramter, DBSchedularEntity $result): void
    {
        $days = $result->getDays();
        if (!empty($days)) {
            $paramter["time"] = $days;
        }
    }
}
