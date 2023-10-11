<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Field\DTO;

use Crsw\CleverReachOfficial\Core\Infrastructure\Data\DataTransferObject;

/**
 * Class Field
 *
 * Synonymous with attribute on CleverReach's API.
 *
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic\Field\DTO
 */
class Field extends DataTransferObject
{
    /**
     * Field's id.
     *
     * @var string
     */
    protected $id;
    /**
     * Field's group id.
     * Empty if field is global.
     *
     * @var string
     */
    protected $groupId;
    /**
     * Field's name.
     *
     * @REQUIRED
     *
     * @var string
     */
    protected $name;
    /**
     * Field's description.
     *
     * @var string
     */
    protected $description;
    /**
     * Field's preview value.
     *
     * @var string
     */
    protected $previewValue;
    /**
     * Field's default value.
     *
     * @var string
     */
    protected $defaultValue;
    /**
     * Field's type.
     *
     * One of text|number|gender|date.
     *
     * @REQUIRED
     *
     * @var string
     */
    protected $type;
    /**
     * Field's tag.
     *
     * @var string
     */
    protected $tag;

    /**
     * Field constructor.
     *
     * @param string $name
     * @param string $type
     * @param string $id
     * @param string $groupId
     * @param string $description
     * @param string $previewValue
     * @param string $defaultValue
     * @param string $tag
     */
    public function __construct(
        $name,
        $type,
        $id = '',
        $groupId = '',
        $description = '',
        $previewValue = '',
        $defaultValue = '',
        $tag = ''
    ) {
        $this->id = $id;
        $this->groupId = $groupId;
        $this->name = $name;
        $this->description = $description;
        $this->previewValue = $previewValue;
        $this->defaultValue = $defaultValue;
        $this->type = $type;
        $this->tag = $tag;
    }

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
    public function getGroupId()
    {
        return $this->groupId;
    }

    /**
     * @param string $groupId
     */
    public function setGroupId($groupId)
    {
        $this->groupId = $groupId;
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
    public function getPreviewValue()
    {
        return $this->previewValue;
    }

    /**
     * @param string $previewValue
     */
    public function setPreviewValue($previewValue)
    {
        $this->previewValue = $previewValue;
    }

    /**
     * @return string
     */
    public function getDefaultValue()
    {
        return $this->defaultValue;
    }

    /**
     * @param string $defaultValue
     */
    public function setDefaultValue($defaultValue)
    {
        $this->defaultValue = $defaultValue;
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
     * @return string
     */
    public function getTag()
    {
        return $this->tag;
    }

    /**
     * @param string $tag
     */
    public function setTag($tag)
    {
        $this->tag = $tag;
    }

    /**
     * Creates Field instance from raw array of data.
     *
     * @param array $data
     *
     * @return \Crsw\CleverReachOfficial\Core\BusinessLogic\Field\DTO\Field
     */
    public static function fromArray(array $data)
    {
        return new self(
            static::getDataValue($data, 'name'),
            static::getDataValue($data, 'type'),
            static::getDataValue($data, 'id'),
            static::getDataValue($data, 'group_id'),
            static::getDataValue($data, 'description'),
            static::getDataValue($data, 'preview_value'),
            static::getDataValue($data, 'default_value'),
            static::getDataValue($data, 'tag')
        );
    }

    /**
     * Retrieves array representation of the field DTO.
     *
     * @return array
     */
    public function toArray()
    {
        return array(
            'name' => $this->getName(),
            'type' => $this->getType(),
            'id' => $this->getId(),
            'group_id' => $this->getGroupId(),
            'description' => $this->getDescription(),
            'preview_value' => $this->getPreviewValue(),
            'default_value' => $this->getDefaultValue(),
            'tag' => $this->getTag(),
        );
    }
}