<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Group\Transformers;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Data\ReduceTransformer;

class SubmitTransformer extends ReduceTransformer
{
    protected static function getAllowedKeys()
    {
        return array(
            'name',
            'locked',
            'backup',
            'receiver_info'
        );
    }
}