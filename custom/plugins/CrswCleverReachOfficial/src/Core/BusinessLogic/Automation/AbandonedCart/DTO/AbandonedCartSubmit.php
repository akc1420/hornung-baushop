<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\DTO;

use Crsw\CleverReachOfficial\Core\Infrastructure\Data\DataTransferObject;

class AbandonedCartSubmit extends DataTransferObject
{
    /**
     * @var string
     */
    protected $name;
    /**
     * @var string
     */
    protected $storeId;
    /**
     * @var string
     */
    protected $description;
    /**
     * @var string
     */
    protected $source;

    /**
     * AbandonedCartSubmit constructor.
     *
     * @param string $name
     * @param string $storeId
     */
    public function __construct($name, $storeId)
    {
        $this->name = $name;
        $this->storeId = $storeId;
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
    public function getStoreId()
    {
        return $this->storeId;
    }

    /**
     * @param string $storeId
     */
    public function setStoreId($storeId)
    {
        $this->storeId = $storeId;
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
    public function getSource()
    {
        return $this->source;
    }

    /**
     * @param string $source
     */
    public function setSource($source)
    {
        $this->source = $source;
    }

    /**
     * Retrieves array representation of an object.
     *
     * @return array
     */
    public function toArray()
    {
        return array(
            'name' => $this->getName(),
            'storeid' => $this->getStoreId(),
            'description' => $this->getDescription(),
            'source' => $this->getSource(),
        );
    }

    /**
     * Transforms array to entity.
     *
     * @param array $data
     *
     * @return \Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\DTO\AbandonedCartSubmit
     */
    public static function fromArray(array $data)
    {
        $entity = new static(self::getDataValue($data, 'name'), self::getDataValue($data, 'storeid'));
        $entity->setDescription(self::getDataValue($data, 'description'));
        $entity->setSource(self::getDataValue($data, 'source'));

        return $entity;
    }
}