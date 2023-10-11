<?php

namespace Recommendy\Components\Struct;

use Exception;

abstract class BaseStruct
{
    /**
     * @var array
     */
    protected $attributes = [];

    /**
     * @var array
     */
    protected $nullableProperties = [];

    /**
     * @param array $data
     * @throws Exception
     */
    public function __construct(array $data)
    {
        $properties = get_object_vars($this);
        foreach ($properties as $property => $value) {
            if ($property === 'attributes') {
                continue;
            }
            $funcName = 'set' . ucfirst($property);
            if (!method_exists($this, $funcName)) {
                continue;
            }
            if (!array_key_exists($property, $data)) {
                if (in_array($property, $this->nullableProperties)) {
                    continue;
                }
                throw new Exception('Missing required property "' . $property . '".');
            }
            $this->{$funcName}($data[$property]);
        }
        if (!empty($data['attributes'])) {
            $this->setAttributes($data['attributes']);
        }
    }

    /**
     * @return array
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * @param array $attributes
     */
    public function setAttributes(array $attributes): void
    {
        $this->attributes = $attributes;
    }

    /**
     * @return array
     * @throws Exception
     */
    public function toArray(): array
    {
        $array = json_decode(json_encode($this), true);
        if (!is_array($array)) {
            throw new Exception('Unable to convert the instance of the "' . get_class($this) . '" class into an array.');
        }
        return $array;
    }
}
