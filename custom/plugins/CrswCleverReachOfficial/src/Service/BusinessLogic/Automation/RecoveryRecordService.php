<?php


namespace Crsw\CleverReachOfficial\Service\BusinessLogic\Automation;

use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Interfaces\RepositoryInterface;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\QueryFilter\Operators;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\QueryFilter\QueryFilter;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\RepositoryRegistry;
use Crsw\CleverReachOfficial\Service\BusinessLogic\Automation\Entities\RecoveryRecord;

/**
 * Class RecoveryRecordService
 *
 * @package Crsw\CleverReachOfficial\Service\BusinessLogic\Automation
 */
class RecoveryRecordService
{
    /**
     * Persists recovery record.
     *
     * @param RecoveryRecord $record
     *
     * @return RecoveryRecord
     *
     * @throws RepositoryNotRegisteredException
     */
    public function create(RecoveryRecord $record): RecoveryRecord
    {
        $this->getRepository()->save($record);

        return $record;
    }

    /**
     * Provides Recovery Records identified by condition.
     *
     * @param array $cond
     *
     * @return RecoveryRecord[]
     *
     * @throws RepositoryNotRegisteredException
     * @throws QueryFilterInvalidParamException
     */
    public function find(array $cond): array
    {
        if (empty($cond)) {
            return [];
        }

        $query = new QueryFilter();

        foreach ($cond as $field => $value) {
            $query->where($field, Operators::EQUALS, $value);
        }

        return $this->getRepository()->select($query);
    }

    /**
     * Finds RecoveryRecord by token.
     *
     * @param string $token
     *
     * @return RecoveryRecord|null
     *
     * @throws QueryFilterInvalidParamException
     * @throws RepositoryNotRegisteredException
     */
    public function findByToken(string $token): ?RecoveryRecord
    {
        $records = $this->find(['token' => $token]);

        return !empty($records[0]) ? $records[0] : null;
    }

    /**
     * Deletes record.
     *
     * @param RecoveryRecord $record
     *
     * @throws RepositoryNotRegisteredException
     */
    public function delete(RecoveryRecord $record): void
    {
        $this->getRepository()->delete($record);
    }

    /**
     * @return RepositoryInterface
     *
     * @throws RepositoryNotRegisteredException
     */
    private function getRepository(): RepositoryInterface
    {
        return RepositoryRegistry::getRepository(RecoveryRecord::class);
    }
}
