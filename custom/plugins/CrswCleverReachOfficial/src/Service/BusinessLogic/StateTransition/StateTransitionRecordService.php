<?php


namespace Crsw\CleverReachOfficial\Service\BusinessLogic\StateTransition;


use Crsw\CleverReachOfficial\Components\Entities\StateTransitionRecord;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Entity;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\QueryFilter\Operators;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\QueryFilter\QueryFilter;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\RepositoryRegistry;
use Crsw\CleverReachOfficial\Entity\Base\Repositories\BaseRepository;
use JsonException;

/**
 * Class StateTransitionRecordService
 *
 * @package Crsw\CleverReachOfficial\Service\BusinessLogic\StateTransition
 */
class StateTransitionRecordService
{
    /**
     * @var BaseRepository
     */
    protected $repository;

    /**
     * StateTransitionRecordService constructor.
     *
     * @throws RepositoryNotRegisteredException
     */
    public function __construct()
    {
        $this->repository = RepositoryRegistry::getRepository(StateTransitionRecord::class);
    }

    /**
     * Saves StateTransitionRecord.
     *
     * @param StateTransitionRecord $record
     */
    public function create(StateTransitionRecord $record): void
    {
        $this->repository->save($record);
    }

    /**
     * Updates StateTransitionRecord.
     *
     * @param StateTransitionRecord $record
     */
    public function update(StateTransitionRecord $record): void
    {
        $this->repository->update($record);
    }

    /**
     * Resolves StateTransitionRecord.
     *
     * @param StateTransitionRecord $record
     */
    public function resolve(StateTransitionRecord $record): void
    {
        $record->setResolved(true);
        $this->repository->update($record);
    }

    /**
     * Finds StateTransitionRecord by id.
     *
     * @param int $id
     *
     * @return Entity|null
     *
     * @throws QueryFilterInvalidParamException
     * @throws JsonException
     */
    public function find(int $id)
    {
        $filter = new QueryFilter();
        $filter->where('id', Operators::EQUALS, $id);

        /** @var StateTransitionRecord $entity */
        $entity = $this->repository->selectOne($filter);

        if (!$entity) {
            return null;
        }

        return $entity;
    }

    /**
     * Deletes StateTransitionRecord by id.
     *
     * @param int $id
     *
     * @throws QueryFilterInvalidParamException
     * @throws JsonException
     */
    public function delete(int $id): void
    {
        $filter = new QueryFilter();
        $filter->where('id', Operators::EQUALS, $id);

        $entity = $this->repository->selectOne($filter);

        if (!$entity) {
            return;
        }

        $this->repository->delete($entity);
    }

    /**
     * Finds StateTransitionRecords by query filter.
     *
     * @param QueryFilter $queryFilter
     *
     * @return Entity[]
     *
     * @throws QueryFilterInvalidParamException
     * @throws JsonException
     */
    public function findBy(QueryFilter $queryFilter): array
    {
        return $this->repository->select($queryFilter);
    }

    /**
     * Finds StateTransitionRecord by query filter.
     *
     * @param QueryFilter $queryFilter
     *
     * @return Entity|null
     *
     * @throws QueryFilterInvalidParamException
     * @throws JsonException
     */
    public function findOneBy(QueryFilter $queryFilter): ?Entity
    {
        return $this->repository->selectOne($queryFilter);
    }
}