<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Field\DTO\Field\Transformers;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Data\ReduceTransformer;

/**
 * Class SubmitTransformer
 *
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic\Field\DTO\Field\Transformers
 */
class SubmitTransformer extends ReduceTransformer
{
    /**
     * Retrieves a list of submittable keys for Field DTO.
     *
     * @return array List of submittable keys.
     */
    protected static function getAllowedKeys()
    {
        return array(
            'name',
            'type',
            'group_id',
            'description',
            'preview_value',
            'default_value',
        );
    }
}