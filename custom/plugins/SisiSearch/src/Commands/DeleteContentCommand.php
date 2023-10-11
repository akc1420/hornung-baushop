<?php

namespace Sisi\Search\Commands;

use Doctrine\DBAL\Connection;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\Console\Input\InputArgument;
use Sisi\Search\Service\DeleteService;
use Symfony\Component\DependencyInjection\ContainerInterface;

class DeleteContentCommand extends Command
{
    protected static $defaultName = 'sisi-Produkt-index:delete';

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

    public function __construct(SystemConfigService $config, Connection $connection, Logger $loggingService, ContainerInterface $container)
    {
        parent::__construct();
        $this->config = $config;
        $this->connection = $connection;
        $this->loggingService = $loggingService;
        $this->container = $container;
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
        $delteheandler = new DeleteService();
        $options = $input->getArguments();
        $delteheandler->deleteIndex($options, $output, $this->connection, $this->config, $this->loggingService, $this->container);
        return 0;
    }
}
