<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\DoubleOptIn\DTO\Transformers;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Data\ReduceTransformer;

/**
 * Class SubmitTransformer
 *
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic\DoubleOptIn\DTO\Transformers
 */
class SubmitTransformer extends ReduceTransformer
{
    protected static function getAllowedKeys()
    {
        return array(
            'email',
            'doidata'
        );
    }
}
