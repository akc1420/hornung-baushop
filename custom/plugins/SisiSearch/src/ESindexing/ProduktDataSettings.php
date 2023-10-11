<?php

namespace Sisi\Search\ESindexing;

use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Elasticsearch\Framework\ElasticsearchHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Sisi\Search\Service\ProductService;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Context;
use Doctrine\DBAL\Connection;
use Elasticsearch\ClientBuilder;
use Shopware\Core\Framework\Uuid\Uuid;
use Sisi\Search\Service\SearchService;
use Sisi\Search\Commands\ProductIndexerCommand;
use Sisi\Search\ESIndexInterfaces\InterfaceProduktDataSettings;

class ProduktDataSettings implements InterfaceProduktDataSettings
{
    public function getSettings(array $settings): array
    {
        return $settings;
    }
}
