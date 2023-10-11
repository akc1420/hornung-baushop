<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Segment\DTO\Segment\Transofrmers;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Data\ReduceTransformer;

/**
 * Class SubmitTransformer
 *
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic\Segment\DTO\Segment\Transofrmers
 */
class SubmitTransformer extends ReduceTransformer
{
    protected static function getAllowedKeys()
    {
        return array(
            'name',
            'rules',
        );
    }
}