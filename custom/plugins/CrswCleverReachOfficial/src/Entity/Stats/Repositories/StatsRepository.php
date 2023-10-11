<?php


namespace Crsw\CleverReachOfficial\Entity\Stats\Repositories;

use Crsw\CleverReachOfficial\Core\BusinessLogic\ORM\Interfaces\ConditionallyDeletes;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Stats\Entity\Stats;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Entity;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\QueryFilter\QueryFilter;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Utility\IndexHelper;
use Crsw\CleverReachOfficial\Entity\Base\Repositories\BaseRepository;
use Exception;

/**
 * Class StatsRepository
 *
 * @package Crsw\CleverReachOfficial\Entity\Stats\Repositories
 */
class StatsRepository extends BaseRepository implements ConditionallyDeletes
{
    /**
     * Delete with condition.
     *
     * @param QueryFilter|null $queryFilter
     *
     * @return bool
     */
    public function deleteWhere(QueryFilter $queryFilter = null): bool
    {
        $result = true;

        try {
            /** @var Entity $entity */
            $entity = new $this->entityClass;
            $type = $entity->getConfig()->getType();
            $indexMap = IndexHelper::mapFieldsToIndexes($entity);

            $query = $this->getConnection()->createQueryBuilder();
            $alias = 'p';
            $query->delete(Stats::class)
                ->from(static::$doctrineModel, $alias)
                ->where("$alias.type = '$type");

            $groups = $queryFilter ? $this->buildConditionGroups($queryFilter, $indexMap) : [];
            $queryParts = $this->getQueryParts($groups, $indexMap, $alias);

            $where = $this->generateWhereStatement($queryParts);

            if (!empty($where)) {
                $query->andWhere($where);
            }

            $query->execute();
        } catch (Exception $e) {
            $result = false;
        }

        return $result;
    }
}