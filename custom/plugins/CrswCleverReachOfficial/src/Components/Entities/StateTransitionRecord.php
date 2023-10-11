<?php


namespace Crsw\CleverReachOfficial\Components\Entities;

use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Configuration\EntityConfiguration;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Configuration\IndexMap;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Entity;
use Crsw\CleverReachOfficial\Core\Infrastructure\Serializer\Serializer;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\QueueItem;

/**
 * Class StateTransitionRecord
 *
 * @package Crsw\CleverReachOfficial\Components\Entities
 */
class StateTransitionRecord extends Entity
{
    public const CLASS_NAME = __CLASS__;

    /**
     * @var string
     */
    protected $status;
    /**
     * @var string
     */
    protected $taskType;
    /**
     * @var string
     */
    protected $description;
    /**
     * @var QueueItem
     */
    protected $queueItem;
    /**
     * @var boolean
     */
    protected $resolved;

    /**
     * @var string[]
     */
    protected $fields = array('id', 'status', 'taskType', 'description', 'queueItem', 'resolved');

    /**
     * Returns entity configuration object.
     *
     * @return EntityConfiguration
     */
    public function getConfig(): EntityConfiguration
    {
        $map = new IndexMap();
        $map->addStringIndex('status')
            ->addStringIndex('taskType')
            ->addBooleanIndex('resolved');

        return new EntityConfiguration($map, 'StateTransitionRecord');
    }

    /**
     * Transforms entity to its array format representation.
     *
     * @return array Entity in array format.
     */
    public function toArray(): array
    {
        $data = parent::toArray();
        $data['queueItem'] = Serializer::serialize($this->getQueueItem());

        return $data;
    }

    /**
     * Transforms raw array data to this entity instance.
     *
     * @param array $data
     *
     * @return static.
     */
    public static function fromArray(array $data): StateTransitionRecord
    {
        $instance = new static();
        $instance->inflate($data);

        return $instance;
    }

    /**
     * Sets raw array data to this entity instance properties.
     *
     * @param array $data
     */
    public function inflate(array $data): void
    {
        $data['queueItem'] = Serializer::unserialize($data['queueItem']);
        parent::inflate($data);
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @param string $status
     */
    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    /**
     * @return string
     */
    public function getTaskType(): string
    {
        return $this->taskType;
    }

    /**
     * @param string $taskType
     */
    public function setTaskType(string $taskType): void
    {
        $this->taskType = $taskType;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    /**
     * @return QueueItem
     */
    public function getQueueItem(): QueueItem
    {
        return $this->queueItem;
    }

    /**
     * @param QueueItem $queueItem
     */
    public function setQueueItem(QueueItem $queueItem): void
    {
        $this->queueItem = $queueItem;
    }

    /**
     * @return bool
     */
    public function isResolved(): bool
    {
        return $this->resolved;
    }

    /**
     * @param bool $resolved
     */
    public function setResolved(bool $resolved): void
    {
        $this->resolved = $resolved;
    }
}