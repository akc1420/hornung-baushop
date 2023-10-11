<?php

namespace Sisi\Search\Service;

use Doctrine\DBAL\Connection;
use Elasticsearch\ClientBuilder;
use Elasticsearch\Client;
use Exception;
use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Sisi\Search\ServicesInterfaces\InterfaceFrontendService;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\DependencyInjection\ContainerInterface;

class FrontendService implements InterfaceFrontendService
{
    /**
     * @param array|string $result
     * @return array
     */
    public function ownFilter($result): array
    {
        return $result;
    }

    public function delete(
        SystemConfigService $systemConfigService,
        EntitySearchResult $entities,
        Connection $connection,
        string $channelId,
        string $languageId,
        Logger $logger
    ): void {
        $config = $systemConfigService->get("SisiSearch.config", $channelId);
        $listingSettings = $systemConfigService->get("core.listing");
        $heandlerClient = new ClientService();
        $client = $heandlerClient->createClient($config);
        $heandler = new SearchHelpService();
        $lastindexMerker = $heandler->findLast($connection, $channelId, $languageId, $config);
        /** @var SalesChannelProductEntity $entity */
        foreach ($entities as $entity) {
            if (array_key_exists('hideCloseoutProductsWhenOutOfStock', $listingSettings)) {
                $available = $entity->getAvailable();
                $isCloseout = $entity->getIsCloseout();
                if ((!$available) && $isCloseout) {
                    $params = [
                        'index' => $lastindexMerker['index'],
                        'id' => $entity->getId()
                    ];
                    try {
                        $client->delete($params);
                    } catch (Exception $e) {
                        $logger->log(100, $e->getMessage());
                    }
                }
            }
        }
    }

    /**
     * @param Client $client
     * @param array $params
     * @param SalesChannelContext $saleschannelContext
     * @param ContainerInterface $container
     * @return array
     * @inheritDoc
     */
    public function search(client $client, array $params, SalesChannelContext $saleschannelContext, ContainerInterface $container): array
    {
        $values = $client->search($params);
        $heandlervarainten = new VariantenService();
        $heandlervarainten->setCoverAndUrl($values);
        return $values;
    }
}
