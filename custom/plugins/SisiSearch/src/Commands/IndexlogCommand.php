<?php

namespace Sisi\Search\Commands;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Sisi\Search\Service\ClientService;
use Sisi\Search\Service\ContextService;
use Sisi\Search\Service\CriteriaService;
use Sisi\Search\Service\IndexService;
use Sisi\Search\Service\QuerylogService;
use Sisi\Search\Service\TextService;
use Sisi\Search\Service\TranslationService;
use Sisi\Search\ServicesInterfaces\InterfaceQuerylogService;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\Console\Input\InputArgument;
use Sisi\Search\Service\DeleteService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\ErrorHandler\Debug;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects )
 **/

class IndexlogCommand extends Command
{
    protected static $defaultName = 'sisi-log-index:mapping';


    /**
     *
     * @var SystemConfigService
     */
    protected $config;


    /**
     * @var Connection
     */
    protected $connection;


    /**
     *
     * @var Logger
     */
    private $loggingService;


    /**
     *
     * @var ContainerInterface
     */
    protected $container;

    /**
     *
     * @var InterfaceQuerylogService;
     */

    protected $querylogSearchService;


    public function __construct(SystemConfigService $config, Connection $connection, Logger $loggingService, ContainerInterface $container, InterfaceQuerylogService $querylogSearchService)
    {
        parent::__construct();
        $this->config = $config;
        $this->connection = $connection;
        $this->loggingService = $loggingService;
        $this->container = $container;
        $this->querylogSearchService = $querylogSearchService;
    }


    protected function configure(): void
    {
        $this->addArgument('shop', InputArgument::OPTIONAL, 'shop Channel');
        $this->addArgument('shopID', InputArgument::OPTIONAL, 'shop Channel id');
        $this->addArgument(
            'all',
            InputArgument::OPTIONAL,
            'Delete all Indexes without the last Indexes. Add the nummber what no want to delete'
        );
        $this->addArgument(
            'language',
            InputArgument::OPTIONAL,
            'With this parameters you only delete indexing from this language'
        );
        $this->addArgument(
            'languageID',
            InputArgument::OPTIONAL,
            'This parameter is necessary when you want use not the default language and you know the language id'
        );
        $this->addArgument(
            'delete',
            InputArgument::OPTIONAL,
            'With these parameters, the log query will be deleted or disable
            delete = "1" vor disable delete="2" for delete this channel and delete="3" for delete all Channels'
        );
        $this->setDescription('Create the mapping for the query log');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null
     *
     * @SuppressWarnings(PHPMD)
     **/

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        Debug::enable();
        $heandlerQuerylog = new QuerylogService();
        $heandlerClient = new ClientService();
        $texthaendler = new TextService();
        $transHaendler = new TranslationService();
        $criteriaHandler = new CriteriaService();
        $contextService = new ContextService();
        $context = $contextService->getContext();
        $options = $input->getArguments();
        $config = $this->config->get("SisiSearch.config");
        $saleschannel = $this->container->get('sales_channel.repository');
        $client = $heandlerClient->createClient($config);
        $parameters = $texthaendler->stripOption2($options);
        $criteriaChannel = new Criteria();
        $shop = "";
        if (array_key_exists('shop', $parameters)) {
            $shop = $parameters['shop'];
            // string manipulation extract channel
            $shop = str_replace("shop=", "", $shop);
        }
        if (array_key_exists('shopID', $parameters)) {
            $shop = "shopID=" . $parameters['shopID'];
        }
        $criteriaHandler->getMergeCriteriaForSalesChannel($criteriaChannel, $shop);
        $salechannelItem = $saleschannel->search($criteriaChannel, $context)->getEntities()->first();
        $parameters['channelId'] = $salechannelItem->getId();
        if (array_key_exists('delete', $parameters)) {
            if ($parameters['delete'] === '2') {
                $heandlerQuerylog->clearAllByChannel($this->connection, $config, $salechannelItem->getId());
                return 0;
            }
            if ($parameters['delete'] === '3') {
                $heandlerQuerylog->clearAll($this->connection, $config);
                return 0;
            }
        }
        $lanugageValues = $transHaendler->getLanguageId($parameters, $this->connection, $output, $this->loggingService);
        $parameters['lanuageId'] = $transHaendler->chechIsSetLanuageId($lanugageValues, $salechannelItem, $parameters);
        $parameters['lanuageName'] = $parameters['lanuageId'];
        $heandlerQuerylog->queryLogStart($client, $parameters, $output, $this->querylogSearchService, $this->connection);
        $heandlerQuerylog->insert($this->connection, $parameters);
        $str = 'Done';
        echo "\033[32m $str \033[0m\n";
        return 0;
    }
}
