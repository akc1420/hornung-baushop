<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Order\DTO\Attribute;

use Crsw\CleverReachOfficial\Core\Infrastructure\Data\DataTransferObject;

class Attribute extends DataTransferObject
{
    /** @var string */
    private $key;
    /** @var string */
    private $value;

    /**
     * Attribute constructor.
     *
     * @param string $key
     * @param string $value
     */
    public function __construct($key, $value)
    {
        $this->key = $key;
        $this->value = $value;
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @param string $key
     */
    public function setKey($key)
    {
        $this->key = $key;
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
     * @inheritDoc
     */
    public function toArray()
    {
        return array(
            'key' => $this->getKey(),
            'value' => $this->getValue(),
        );
    }

    /**
     * @inheritDoc
     */
    public static function fromArray(array $data)
    {
        return new static(static::getDataValue($data, 'key'), static::getDataValue($data, 'value'));
    }

    /**
     * Retrieves string representation of attribute.
     */
    public function toString()
    {
        return "{$this->key}:{$this->value}";
    }

    /**
     * Creates instance from string.
     *
     * @param string $raw
     *
     * @return $this
     */
    public static function fromString($raw)
    {
        $parts = explode(':', $raw);
        $key = !empty($parts[0]) ? $parts[0] : '';
        $value = !empty($parts[1]) ? $parts[1] : '';

        return new static($key, $value);
    }

    /**
     * Creates list from batch.
     *
     * @param array $batch
     *
     * @return \Crsw\CleverReachOfficial\Core\BusinessLogic\Order\DTO\Attribute\Attribute[]
     */
    public static function fromBatch(array $batch)
    {
        $attributes = array();

        foreach ($batch as $raw) {
            if (is_array($raw)) {
                $attributes[] = static::fromArray($raw);
            } else {
                $attributes[] = static::fromString($raw);
            }
        }

        return $attributes;
    }
}