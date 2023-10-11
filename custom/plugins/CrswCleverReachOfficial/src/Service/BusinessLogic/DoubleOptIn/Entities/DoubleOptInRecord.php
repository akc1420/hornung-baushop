<?php

namespace Crsw\CleverReachOfficial\Service\BusinessLogic\DoubleOptIn\Entities;

use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Configuration\EntityConfiguration;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Configuration\IndexMap;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Entity;

/**
 * Class DoubleOptInRecord
 *
 * @package Crsw\CleverReachOfficial\Service\BusinessLogic\DoubleOptIn\Entities
 */
class DoubleOptInRecord extends Entity
{
    /**
     * Fully qualified name of this class.
     */
    const CLASS_NAME = __CLASS__;

    /**
     * @var string
     */
    protected $salesChannelId;
    /**
     * @var bool
     */
    protected $status;
    /**
     * @var int
     */
    protected $formId;

    /**
     * @var string[]
     */
    protected $fields = [
        'id',
        'salesChannelId',
        'status',
        'formId'
    ];

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id): void
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getSalesChannelId(): string
    {
        return $this->salesChannelId;
    }

    /**
     * @param string $salesChannelId
     */
    public function setSalesChannelId(string $salesChannelId): void
    {
        $this->salesChannelId = $salesChannelId;
    }

    /**
     * @return bool
     */
    public function isStatus(): bool
    {
        return $this->status;
    }

    /**
     * @param bool $status
     */
    public function setStatus(bool $status): void
    {
        $this->status = $status;
    }

    /**
     * @return int|null
     */
    public function getFormId(): ?int
    {
        return $this->formId;
    }

    /**
     * @param int $formId
     */
    public function setFormId(int $formId): void
    {
        $this->formId = $formId;
    }

    /**
     * @return EntityConfiguration
     */
    public function getConfig(): EntityConfiguration
    {
        $map = new IndexMap();
        $map->addStringIndex('salesChannelId');

        return new EntityConfiguration($map, 'DoubleOptInRecord');
    }
}
