<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\DTO\Blacklist\Transformers;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Data\ReduceTransformer;
use Crsw\CleverReachOfficial\Core\Infrastructure\Data\DataTransferObject;

/**
 * Class SubmitTransformer
 *
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\DTO\Blacklist\Transformers
 */
class SubmitTransformer extends ReduceTransformer
{
    /**
     * @inheritDoc
     * @param \Crsw\CleverReachOfficial\Core\Infrastructure\Data\DataTransferObject $transformable
     *
     * @return array
     */
    public static function transform(DataTransferObject $transformable)
    {
        $data = parent::transform($transformable);
        static::trim($data);

        return $data;
    }

    /**
     * @inheritDoc
     * @return array|string[]
     */
    protected static function getAllowedKeys()
    {
        return array(
            'email',
            'comment',
        );
    }
}
