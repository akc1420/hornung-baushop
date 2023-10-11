<?php

namespace Sisi\Search\Commands;

use Doctrine\DBAL\Connection;
use Sisi\Search\Service\CategorieIndexService;
use Sisi\Search\Service\TextService;
use Sisi\Search\ServicesInterfaces\InterfaceSearchCategorieService;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\Console\Input\InputArgument;
use Sisi\Search\Service\DeleteService;
use Symfony\Component\DependencyInjection\ContainerInterface;

class CategorieIndexCommand extends Command
{
    protected static $defaultName = 'sisi-Produkt-index:categorie';


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
     * @var InterfaceSearchCategorieService
     */
    protected $searchCategorieService;


    public function __construct(
        SystemConfigService $config,
        Connection $connection,
        Logger $loggingService,
        ContainerInterface $container,
        InterfaceSearchCategorieService $searchCategorieService
    ) {
        parent::__construct();
        $this->config = $config;
        $this->connection = $connection;
        $this->loggingService = $loggingService;
        $this->container = $container;
        $this->searchCategorieService = $searchCategorieService;
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
        $this->setDescription('Delete full Elastcsearch indexes for keep space on the ES server');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     **/

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $heandler = new  CategorieIndexService();
        $options = $input->getArguments();
        $texthaendler = new TextService();
        $paramters = $texthaendler->stripOption2($options);
        $paramters['shop'] = $input->getArgument('shop');
        $heandler->startIndex($this->container, $paramters, $this->connection, $this->config, $output, $this->loggingService, $this->searchCategorieService);
        return 0;
    }
}
