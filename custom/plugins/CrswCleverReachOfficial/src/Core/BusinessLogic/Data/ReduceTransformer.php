<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Data;

use Crsw\CleverReachOfficial\Core\Infrastructure\Data\DataTransferObject;
use Crsw\CleverReachOfficial\Core\Infrastructure\Data\Transformer as BaseTransformer;

class ReduceTransformer extends BaseTransformer
{
    /**
     * Transforms Field to a format suitable for API submit.
     *
     * @param \Crsw\CleverReachOfficial\Core\Infrastructure\Data\DataTransferObject $transformable Field instance.
     *
     * @return array Submittable format.
     */
    public static function transform(DataTransferObject $transformable)
    {
        $result = $transformable->toArray();

        return array_intersect_key($result, array_flip(static::getAllowedKeys()));
    }

    /**
     * Retrieves a list of submittable keys for Field DTO.
     *
     * @return array List of submittable keys.
     */
    protected static function getAllowedKeys()
    {
        return array();
    }
}