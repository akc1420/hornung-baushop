<?php

/** @noinspection DuplicatedCode */

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\DTO;

use Crsw\CleverReachOfficial\Core\Infrastructure\Data\DataTransferObject;

/**
 * Class AutomationDetails
 *
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\DTO
 */
class AutomationDetails extends DataTransferObject
{
    /**
     * @var string
     */
    protected $id;
    /**
     * @var string
     */
    protected $name;
    /**
     * @var string
     */
    protected $description;
    /**
     * @var string
     */
    protected $type;
    /**
     * @var int
     */
    protected $lastExecuted;
    /**
     * @var bool
     */
    protected $active;

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return int
     */
    public function getLastExecuted()
    {
        return $this->lastExecuted;
    }

    /**
     * @param int $lastExecuted
     */
    public function setLastExecuted($lastExecuted)
    {
        $this->lastExecuted = $lastExecuted;
    }

    /**
     * @return bool
     */
    public function isActive()
    {
        return $this->active;
    }

    /**
     * @param bool $active
     */
    public function setActive($active)
    {
        $this->active = $active;
    }

    /**
     * Returns array representation.
     *
     * @return array
     */
    public function toArray()
    {
        return array(
            'id' => $this->getId(),
            'name' => $this->getName(),
            'description' => $this->getDescription(),
            'type' => $this->getType(),
            'lastExecuted' => $this->getLastExecuted(),
            'active' => $this->isActive(),
        );
    }

    /**
     * Creates instance from an array.
     *
     * @param array $data
     *
     * @return \Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\DTO\AutomationDetails
     */
    public static function fromArray(array $data)
    {
        $entity = new static();
        $entity->setId(static::getDataValue($data, 'id'));
        $entity->setName(static::getDataValue($data, 'name'));
        $entity->setDescription(static::getDataValue($data, 'description'));
        $entity->setType(static::getDataValue($data, 'type'));
        $entity->setLastExecuted(static::getDataValue($data, 'lastExecuted', 0));
        $entity->setActive(static::getDataValue($data, 'active', false));

        return $entity;
    }
}