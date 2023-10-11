<?php

namespace Sisi\Search\ESIndexInterfaces;

use Doctrine\DBAL\Connection;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\DependencyInjection\ContainerInterface;

interface InterSearchAjaxService
{
    /**
     * @param string $term
     * @param array|null $properties
     * @param array|string|null $manufactoryIds
     * @param array $config
     * @param SalesChannelContext $saleschannelContext
     * @param Connection $connection
     * @param array $getParams
     * @param ContainerInterface $container
     *
     * @return array
     * @SuppressWarnings(PHPMD)
     *
     */
    public function searchProducts(
        $term,
        $properties,
        $manufactoryIds,
        $config,
        $saleschannelContext,
        $connection,
        $getParams,
        $container
    ): array;
}
