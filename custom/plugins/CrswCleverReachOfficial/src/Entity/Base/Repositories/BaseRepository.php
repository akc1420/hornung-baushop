<?php

namespace Crsw\CleverReachOfficial\Entity\Base\Repositories;

use Crsw\CleverReachOfficial\Core\Infrastructure\Logger\Logger;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Entity;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Interfaces\RepositoryInterface;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\QueryFilter\Operators;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\QueryFilter\QueryCondition;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\QueryFilter\QueryFilter;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Utility\IndexHelper;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;
use Crsw\CleverReachOfficial\Entity\Base\BaseEntity;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Query\QueryBuilder;
use JsonException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;

/**
 * Class BaseRepository
 * @package Crsw\CleverReachOfficial\Entity\Base\Repositories
 */
class BaseRepository implements RepositoryInterface
{
    /**
     * @var string
     */
    protected static $doctrineModel = 'cleverreach_entity';
    /**
     * @var string
     */
    protected $entityClass;

    /**
     * Returns full class name.
     *
     * @return string Full class name.
     */
    public static function getClassName(): string
    {
        return static::class;
    }

    /**
     * Sets repository entity.
     *
     * @param string $entityClass Repository entity class.
     */
    public function setEntityClass($entityClass): void
    {
        $this->entityClass = $entityClass;
    }

    /**
     * Executes select query.
     *
     * @param QueryFilter|null $filter Filter for query.
     *
     * @return Entity[] A list of found entities ot empty array.
     *
     * @throws QueryFilterInvalidParamException
     * @throws JsonException
     */
    public function select(QueryFilter $filter = null): array
    {
        $query = $this->getBaseDoctrineQuery($filter);

        return $this->getResult($query);
    }

    /**
     * Executes select query and returns first result.
     *
     * @param QueryFilter|null $filter Filter for query.
     *
     * @return Entity|null First found entity or NULL.
     *
     * @throws QueryFilterInvalidParamException
     * @throws JsonException
     */
    public function selectOne(QueryFilter $filter = null): ?Entity
    {
        $query = $this->getBaseDoctrineQuery($filter);
        $query->setMaxResults(1);

        $result = $this->getResult($query);

        return !empty($result[0]) ? $result[0] : null;
    }

    /**
     * Executes insert query and returns ID of created entity. Entity will be updated with new ID.
     *
     * @param Entity $entity Entity to be saved.
     *
     * @return int|string|null Identifier of saved entity.
     */
    public function save(Entity $entity)
    {
        $data = $this->transformEntityToArray($entity);

        if ($entity->getId()) {
            /** @var BaseEntity $baseEntity */
            $baseEntity = $this->getEntityRepository()->search(
                (new Criteria())->addFilter(new EqualsFilter('id', $entity->getId())),
                Context::createDefaultContext()
            )->first();

            if ($baseEntity) {
                $data['shopware_id'] = $baseEntity->getShopware_id();
                $this->getEntityRepository()->update([$data], Context::createDefaultContext());

                return $baseEntity->getId();
            }
        }

        $event = $this->getEntityRepository()->create([$data], Context::createDefaultContext())
            ->getEventByEntityName(static::$doctrineModel);

        if (!$event) {
            return null;
        }

        $baseEntity = $this->getEntityRepository()->search(
            new Criteria([$event->getWriteResults()[0]->getPrimaryKey()]),
            Context::createDefaultContext()
        )->first();
        $entity->setId($baseEntity->getId());

        return $entity->getId();
    }

    /**
     * Executes update query and returns success flag.
     *
     * @param Entity $entity Entity to be updated.
     *
     * @return bool TRUE if operation succeeded; otherwise, FALSE.
     */
    public function update(Entity $entity): bool
    {
        $result = true;

        /** @var BaseEntity $doctrineEntity */
        $doctrineEntity = $this->getEntityRepository()
            ->search(
                (new Criteria())->addFilter(new EqualsFilter('id', $entity->getId())),
                Context::createDefaultContext()
            )->first();

        if ($doctrineEntity) {
            $data = $this->transformEntityToArray($entity);
            $data['shopware_id'] = $doctrineEntity->getShopware_id();
            $this->getEntityRepository()->update([$data], Context::createDefaultContext());
        } else {
            $result = false;
        }

        return $result;
    }

    /**
     * Executes delete query and returns success flag.
     *
     * @param Entity $entity Entity to be deleted.
     *
     * @return bool TRUE if operation succeeded; otherwise, FALSE.
     */
    public function delete(Entity $entity): bool
    {
        $sql = "DELETE FROM cleverreach_entity WHERE id=:id";
        try {
            $this->getConnection()->executeUpdate($sql, ['id' => $entity->getId()]);
            return true;
        } catch (DBALException $e) {
            Logger::logError($e->getMessage());
            return false;
        }
    }

    /**
     * Counts records that match filter criteria.
     *
     * @param QueryFilter|null $filter Filter for query.
     *
     * @return int Number of records that match filter criteria.
     * @throws QueryFilterInvalidParamException
     */
    public function count(QueryFilter $filter = null): int
    {
        $query = $this->getBaseDoctrineQuery($filter, true);

        $result = $query->execute()->fetch();

        return (int)$result['num'];
    }

    /**
     * @param QueryFilter|null $filter
     * @param bool $isCount
     *
     * @return QueryBuilder
     * @throws QueryFilterInvalidParamException
     */
    protected function getBaseDoctrineQuery(QueryFilter $filter = null, $isCount = false): QueryBuilder
    {
        /** @var Entity $entity */
        $entity = new $this->entityClass;
        $type = $entity->getConfig()->getType();
        $indexMap = IndexHelper::mapFieldsToIndexes($entity);

        $query = $this->getConnection()->createQueryBuilder();
        $alias = 'p';
        $baseSelect = $isCount ? "count($alias.id) as num" : $alias . '.*';
        $query->select($baseSelect)
            ->from(static::$doctrineModel, $alias)
            ->where("$alias.type = '$type'");

        $groups = $filter ? $this->buildConditionGroups($filter, $indexMap) : [];
        $queryParts = $this->getQueryParts($groups, $indexMap, $alias);

        $where = $this->generateWhereStatement($queryParts);
        if (!empty($where)) {
            $query->andWhere($where);
        }

        if ($filter) {
            $this->setLimit($filter, $query);
            $this->setOffset($filter, $query);
            $this->setOrderBy($filter, $indexMap, $alias, $query);
        }

        return $query;
    }

    /**
     * Builds condition groups (each group is chained with OR internally, and with AND externally) based on query
     * filter.
     *
     * @param QueryFilter $filter Query filter object.
     * @param array $fieldIndexMap Map of property indexes.
     *
     * @return array Array of condition groups..
     *
     * @throws QueryFilterInvalidParamException
     */
    protected function buildConditionGroups(QueryFilter $filter, array $fieldIndexMap): array
    {
        $groups = [];
        $counter = 0;
        $fieldIndexMap['id'] = 0;
        foreach ($filter->getConditions() as $condition) {
            if (!empty($groups[$counter]) && $condition->getChainOperator() === 'OR') {
                $counter++;
            }

            // Only index columns can be filtered.
            if (!array_key_exists($condition->getColumn(), $fieldIndexMap)) {
                throw new QueryFilterInvalidParamException("Field [{$condition->getColumn()}] is not indexed.");
            }

            $groups[$counter][] = $condition;
        }

        return $groups;
    }

    /**
     * Retrieves group query parts.
     *
     * @param array $conditionGroups
     * @param array $indexMap
     * @param string $alias
     *
     * @return array
     */
    protected function getQueryParts(array $conditionGroups, array $indexMap, string $alias): array
    {
        $parts = [];

        foreach ($conditionGroups as $group) {
            $subPart = [];

            foreach ($group as $condition) {
                if ($condition->getValue() === '') {
                    continue;
                }

                $subPart[] = $this->getQueryPart($condition, $indexMap, $alias);
            }

            if (!empty($subPart)) {
                $parts[] = $subPart;
            }
        }

        return $parts;
    }

    /**
     * Retrieves query part.
     *
     * @param QueryCondition $condition
     * @param array $indexMap
     * @param string $alias
     *
     * @return string
     */
    protected function getQueryPart(QueryCondition $condition, array $indexMap, string $alias): string
    {
        $column = $condition->getColumn();

        if ($column === 'id') {
            return "$alias.id=" . $condition->getValue();
        }

        $part = "$alias.index_" . $indexMap[$column] . ' ' . $condition->getOperator();
        if (!in_array($condition->getOperator(), array(Operators::NULL, Operators::NOT_NULL), true)) {
            if (in_array($condition->getOperator(), array(Operators::NOT_IN, Operators::IN), true)) {
                $part .= $this->getInOperatorValues($condition);
            } else {
                $part .= " '" . IndexHelper::castFieldValue($condition->getValue(), $condition->getValueType()) . "'";
            }
        }

        return $part;
    }

    /**
     * Handles values for the IN and NOT IN operators.
     *
     * @param QueryCondition $condition
     *
     * @return string
     */
    protected function getInOperatorValues(QueryCondition $condition): string
    {
        $values = array_map(
            function ($item) {
                if (is_string($item)) {
                    return "'$item'";
                }

                return "'" . IndexHelper::castFieldValue($item, is_int($item) ? 'integer' : 'double') . "'";
            },
            $condition->getValue()
        );

        return '(' . implode(',', $values) . ')';
    }

    /**
     * Generates where statement.
     *
     * @param array $queryParts
     *
     * @return string
     */
    protected function generateWhereStatement(array $queryParts): string
    {
        $where = '';

        foreach ($queryParts as $index => $part) {
            $subWhere = '';

            if ($index > 0) {
                $subWhere .= ' OR ';
            }

            $subWhere .= $part[0];
            $count = count($part);
            for ($i = 1; $i < $count; $i++) {
                $subWhere .= ' AND ' . $part[$i];
            }

            $where .= $subWhere;
        }

        return $where;
    }

    /**
     * Sets limit.
     *
     * @param QueryFilter $filter
     * @param $query
     */
    protected function setLimit(QueryFilter $filter, QueryBuilder $query): void
    {
        if ($filter->getLimit()) {
            $query->setMaxResults($filter->getLimit());
        }
    }


    /**
     * @param QueryFilter $filter
     * @param QueryBuilder $query
     */
    protected function setOffset(QueryFilter $filter, QueryBuilder $query): void
    {
        if ($filter->getOffset()) {
            $query->setFirstResult($filter->getOffset());
        }
    }

    /**
     * Sets order by.
     *
     * @param QueryFilter $filter
     * @param array $indexMap
     * @param $alias
     * @param QueryBuilder $query
     */
    protected function setOrderBy(QueryFilter $filter, array $indexMap, $alias, QueryBuilder $query): void
    {
        if ($filter->getOrderByColumn()) {
            $orderByColumn = $filter->getOrderByColumn();

            if ($orderByColumn === 'id' || !empty($indexMap[$orderByColumn])) {
                $columnName = $orderByColumn === 'id'
                    ? "$alias.id" : "$alias.index_" . $indexMap[$orderByColumn];
                $query->orderBy($columnName, $filter->getOrderDirection());
            }
        }
    }

    /**
     * Retrieves query result.
     *
     * @param $builder
     *
     * @return Entity[]
     *
     */
    protected function getResult(QueryBuilder $builder): array
    {
        $doctrineEntities = $builder->execute()->fetchAll();

        $result = [];

        foreach ($doctrineEntities as $doctrineEntity) {
            if (!$doctrineEntity) {
                continue;
            }

            $entity = $this->unserializeEntity($doctrineEntity['data']);
            if ($entity) {
                $entity->setId($doctrineEntity['id']);
                $result[] = $entity;
            }
        }

        return $result;
    }

    /**
     * Unserializes ORM entity.
     *
     * @param string $data
     *
     * @return Entity
     */
    protected function unserializeEntity($data): Entity
    {
        $jsonEntity = json_decode($data, true);
        if (array_key_exists('class_name', $jsonEntity)) {
            $entity = new $jsonEntity['class_name'];
        } else {
            $entity = new $this->entityClass;
        }

        /** @var Entity $entity */
        $entity->inflate($jsonEntity);

        return $entity;
    }

    /**
     * Transforms entity to array.
     *
     * @param Entity $entity
     *
     * @return array
     */
    protected function transformEntityToArray($entity): array
    {
        $data['type'] = $entity->getConfig()->getType();
        $values = IndexHelper::transformFieldsToIndexes($entity);

        foreach ($values as $key => $value) {
            $data["index_{$key}"] = $value;
        }

        $entityValues = $entity->toArray();

        if ($data['type'] === 'Form') {
            $entityValues['content'] = htmlentities($entityValues['content']);
        }

        $data['data'] = json_encode($entityValues);

        return $data;
    }

    /**
     * @return Connection
     */
    protected function getConnection(): Connection
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return ServiceRegister::getService(Connection::class);
    }
    /**
     * @return EntityRepositoryInterface
     */
    protected function getEntityRepository(): EntityRepositoryInterface
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return ServiceRegister::getService(EntityRepositoryInterface::class);
    }
}
