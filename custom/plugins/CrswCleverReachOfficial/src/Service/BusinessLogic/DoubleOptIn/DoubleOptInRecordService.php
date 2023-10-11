<?php

namespace Crsw\CleverReachOfficial\Service\BusinessLogic\DoubleOptIn;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Form\Entities\Form;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Interfaces\RepositoryInterface;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\QueryFilter\Operators;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\QueryFilter\QueryFilter;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\RepositoryRegistry;
use Crsw\CleverReachOfficial\Entity\Form\Repositories\FormRepository;
use Crsw\CleverReachOfficial\Service\BusinessLogic\DoubleOptIn\Entities\DoubleOptInRecord;

/**
 * Class DoubleOptInRecordService
 *
 * @package Crsw\CleverReachOfficial\Service\BusinessLogic\DoubleOptIn
 */
class DoubleOptInRecordService
{
    /**
     * Persist double opt in record.
     *
     * @param DoubleOptInRecord $record
     *
     * @return DoubleOptInRecord
     *
     * @throws RepositoryNotRegisteredException
     * @throws QueryFilterInvalidParamException
     */
    public function createOrUpdate(DoubleOptInRecord $record): DoubleOptInRecord
    {
        $doiRecord = $this->findBySalesChannelId($record->getSalesChannelId());

        if ($doiRecord === null) {
            $this->getRepository()->save($record);
            return $record;
        }

        if (!$defaultId = $record->getFormId()) {
            $form = $this->getFormRepository()->getDefaultFormEntity();
            $defaultId = $form ? $form->getApiId() : 0;
        }

        $doiRecord->setStatus($record->isStatus());
        $doiRecord->setFormId($defaultId);

        $this->getRepository()->save($doiRecord);

        return $doiRecord;
    }

    /**
     * Finds DoubleOptInRecord by sales channel id.
     *
     * @param string $salesChannelId
     *
     * @return DoubleOptInRecord|null
     *
     * @throws QueryFilterInvalidParamException
     * @throws RepositoryNotRegisteredException
     */
    public function findBySalesChannelId(string $salesChannelId): ?DoubleOptInRecord
    {
        $records = $this->find(['salesChannelId' => $salesChannelId]);

        return !empty($records[0]) ? $records[0] : null;
    }

    /**
     * Provides DoubleOptIn Record identified by condition.
     *
     * @param array $cond
     *
     * @return DoubleOptInRecord[]
     *
     * @throws RepositoryNotRegisteredException
     * @throws QueryFilterInvalidParamException
     */
    private function find(array $cond): array
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
     * @return RepositoryInterface
     *
     * @throws RepositoryNotRegisteredException
     */
    private function getRepository(): RepositoryInterface
    {
        return RepositoryRegistry::getRepository(DoubleOptInRecord::class);
    }

    /**
     * @return FormRepository
     *
     * @throws RepositoryNotRegisteredException
     */
    private function getFormRepository(): RepositoryInterface
    {
        return RepositoryRegistry::getRepository(Form::class);
    }
}
