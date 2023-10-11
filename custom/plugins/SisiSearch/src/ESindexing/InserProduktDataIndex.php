<?php

namespace Sisi\Search\ESindexing;

use Doctrine\DBAL\Exception;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Sisi\Search\ESIndexInterfaces\InterfaceInsertProduktDataIndex;
use Sisi\Search\Service\InsertMainMethode;
use Symfony\Bridge\Monolog\Logger;
use Elasticsearch\Client;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Symfony\Component\Console\Output\OutputInterface;
use Sisi\Search\Service\InsertService;
use Symfony\Component\DependencyInjection\ContainerInterface;

class InserProduktDataIndex implements InterfaceInsertProduktDataIndex
{
    /**
     * @var InsertQuery
     */
    protected $insertQuery;


    /**
     * InserProduktDataIndex constructor.
     * @param InsertQuery $insertQuery
     */
    public function __construct($insertQuery)
    {
        $this->insertQuery = $insertQuery;
    }

    /**
     * @param EntitySearchResult $entities
     * @param EntitySearchResult $mappingValues
     * @param Client $client
     * @param string $lanugageId
     * @param Logger $loggingService
     * @param OutputInterface | null $output
     * @param array $parameters
     * @param ContainerInterface $container
     *
     * @throws Exception
     */
    public function setIndex(
        &$entities,
        $mappingValues,
        $client,
        $lanugageId,
        $loggingService,
        $output,
        $parameters,
        $container
    ): void {
        $insertService = new InsertService();
        $heandlerDynamisch = new InsertMainMethode();
        $merkerIdsFordynamicProducts = [];
        $insertService->setIndex(
            $entities,
            $mappingValues,
            $client,
            $lanugageId,
            $loggingService,
            $output,
            $this->insertQuery,
            $parameters,
            $container,
            $merkerIdsFordynamicProducts
        );
        if (count($merkerIdsFordynamicProducts) > 0) {
            $saleschannelContext = $parameters['saleschannelContext'];
            $createCriteria = $parameters['createCriteria'];
            $criteria = new Criteria();
            $createCriteria->getCriteria($criteria);
            $dynamicproducts = $heandlerDynamisch->getDynamicproduct($criteria, $merkerIdsFordynamicProducts, $container, $saleschannelContext);
            $parameters['config']['addVariants'] = 'no';
            $insertService->setIndex(
                $dynamicproducts,
                $mappingValues,
                $client,
                $lanugageId,
                $loggingService,
                $output,
                $this->insertQuery,
                $parameters,
                $container,
                $merkerIdsFordynamicProducts
            );
        }
    }
}
