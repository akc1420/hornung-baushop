<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\DynamicContent\DTO\Transformers;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Data\ReduceTransformer;

/**
 * Class SubmitTransformer
 *
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic\DynamicContent\DTO\Transformers
 */
class SubmitTransformer extends ReduceTransformer
{
    /**
     * @return array
     */
    protected static function getAllowedKeys()
    {
        return array(
            'name',
            'url',
            'password',
            'type',
            'cors',
            'icon',
        );
    }
}
