<?php

namespace Crsw\CleverReachOfficial\Core\Infrastructure\Serializer\Concrete;

use Crsw\CleverReachOfficial\Core\Infrastructure\Serializer\Serializer;
use stdClass;

/**
 * Class JsonSerializer
 *
 * @package Crsw\CleverReachOfficial\Core\Infrastructure\Serializer\Concrete
 */
class JsonSerializer extends Serializer
{
    /**
     * Serializes data.
     *
     * @param mixed $data Data to be serialized.
     *
     * @return string String representation of the serialized data.
     */
    protected function doSerialize($data)
    {
        if (!method_exists($data, 'toArray')) {
            if ($data instanceof stdClass) {
                $data->className = get_class($data);
            }

            return json_encode($data, true);
        }

        $preparedArray = $data->toArray();
        $preparedArray['class_name'] = get_class($data);

        return json_encode($preparedArray);
    }

    /**
     * Unserializes data.
     *
     * @param string $serialized Serialized data.
     *
     * @return mixed Unserialized data.
     */
    protected function doUnserialize($serialized)
    {
        $unserialized = json_decode($serialized, true);

        if (!is_array($unserialized) || !array_key_exists('class_name', $unserialized)) {
            return $unserialized;
        }

        $class = $unserialized['class_name'];
        unset($unserialized['class_name']);

        return $class::fromArray($unserialized);
    }
}