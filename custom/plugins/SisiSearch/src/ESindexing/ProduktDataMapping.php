<?php

namespace Sisi\Search\ESindexing;

use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Elasticsearch\Framework\ElasticsearchHelper;
use Sisi\Search\ESIndexInterfaces\InterfaceProduktDataMapping;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Sisi\Search\Service\ProductService;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Context;
use Doctrine\DBAL\Connection;
use Elasticsearch\ClientBuilder;
use Shopware\Core\Framework\Uuid\Uuid;
use Sisi\Search\Service\SearchService;
use Sisi\Search\Commands\ProductIndexerCommand;

class ProduktDataMapping implements InterfaceProduktDataMapping
{
    public function getMapping(array $mapping): array
    {
        return $mapping;
    }
}
