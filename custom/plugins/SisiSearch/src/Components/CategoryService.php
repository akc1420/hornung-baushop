<?php

namespace Sisi\Search\Components;

use Doctrine\DBAL\Statement;
use Sisi\Search\Service\ContextService;
use Doctrine\DBAL\Connection;

class CategoryService
{
    /**
     * @param Connection $connection
     * @param string $catergorieId
     * @return string|null
     */
    public function getCategorieNameById(Connection $connection, $catergorieId)
    {
        $contextService = new ContextService();
        $query = $connection->createQueryBuilder()
            ->select(['translation.name'])
            ->from('category')
            ->innerJoin('category', 'category_translation', 'translation', 'translation.category_id =category.id')
            ->where('category.id =:id')
            ->setParameter(':id', $contextService->getFromHexToBytes($catergorieId));

        /** @var Statement $result */
        $result = $query->execute();
        return $result->fetchColumn();
    }
}
