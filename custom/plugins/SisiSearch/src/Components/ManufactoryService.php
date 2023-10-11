<?php

namespace Sisi\Search\Components;

use Doctrine\DBAL\Statement;
use Sisi\Search\Service\ContextService;
use Doctrine\DBAL\Connection;

class ManufactoryService
{
    /**
     * @param Connection $connection
     * @param string|null $manufactoryId
     * @return string
     */
    public function getManufactoryById(Connection $connection, $manufactoryId): string
    {
        $contextService = new ContextService();
        $query = $connection->createQueryBuilder()
            ->select(['product_manufacturer_translation.name'])
            ->from('product_manufacturer_translation')
            ->where('product_manufacturer_translation.product_manufacturer_id =:id')
            ->setParameter(':id', $contextService->getFromHexToBytes($manufactoryId));
        /** @var Statement $result */
        $result = $query->execute();
        return $result->fetchColumn();
    }
}
