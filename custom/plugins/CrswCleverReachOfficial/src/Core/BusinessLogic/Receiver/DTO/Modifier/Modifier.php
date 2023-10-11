<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\DTO\Modifier;

use Crsw\CleverReachOfficial\Core\Infrastructure\Data\DataTransferObject;

/**
 * Class Modifier
 *
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\DTO\Modifier
 */
class Modifier extends DataTransferObject
{
    /**
     * Modified field identifier.
     *
     * @var string
     */
    protected $field;
    /**
     * Modification magnitude.
     *
     * @var string
     */
    protected $value;
    /**
     * @var string
     */
    protected $type;

    /**
     * Modifier constructor.
     *
     * @param $field
     * @param $value
     */
    public function __construct($field, $value)
    {
        $this->field = $field;
        $this->value = $value;
    }

    /**
     * @return string
     */
    public function getField()
    {
        return $this->field;
    }

    /**
     * @param string $field
     */
    public function setField($field)
    {
        $this->field = $field;
    }

    /**
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param string $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @inheritDoc
     */
    public static function fromArray(array $data)
    {
        $modifier = new static($data['field'], $data['value']);
        $modifier->type = $data['type'];

        return $modifier;
    }

    /**
     * @inheritDoc
     */
    public function toArray()
    {
        return array(
            'type' => $this->getType(),
            'field' => $this->getField(),
            'value' => $this->getValue(),
        );
    }
}