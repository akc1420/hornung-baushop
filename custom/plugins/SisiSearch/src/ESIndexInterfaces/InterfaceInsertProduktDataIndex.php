<?php

namespace Sisi\Search\ESIndexInterfaces;

use Doctrine\DBAL\Driver\Connection;
use Shopware\Core\Content\Media\Pathname\UrlGeneratorInterface;
use Sisi\Search\ESindexing\InsertQuery;
use Symfony\Bridge\Monolog\Logger;
use Elasticsearch\Client;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

interface InterfaceInsertProduktDataIndex
{
    /**
     * @param EntitySearchResult $entities
     * @param EntitySearchResult $mappingValues
     * @param Client $client
     * @param string $lanugageId
     * @param Logger $loggingService
     * @param OutputInterface|null $output
     * @param array $parameters
     * @param ContainerInterface $container
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
    ): void;
}
