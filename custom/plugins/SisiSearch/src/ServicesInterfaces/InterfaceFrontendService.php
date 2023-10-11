<?php

namespace Sisi\Search\ServicesInterfaces;

use Doctrine\DBAL\Connection;
use Elasticsearch\Client;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\DependencyInjection\ContainerInterface;

interface InterfaceFrontendService
{
    public function ownFilter(array $result): array;

    public function delete(
        SystemConfigService $config,
        EntitySearchResult $entities,
        Connection $connection,
        string $channelId,
        string $languageId,
        Logger $logger
    ): void;

    public function search(client $client, array $params, SalesChannelContext $saleschannelContext, ContainerInterface $container): array;
}
