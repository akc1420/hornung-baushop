<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\DynamicContent\DTO\Response\SearchResult\Transformer;

use Crsw\CleverReachOfficial\Core\Infrastructure\Data\DataTransferObject;
use Crsw\CleverReachOfficial\Core\Infrastructure\Data\Transformer as BaseTransformer;

/**
 * Class Transformer
 *
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic\DynamicContent\DTO\Response\Filter\SearchResult\Transformer
 */
class Transformer extends BaseTransformer
{
    public static function transform(DataTransferObject $transformable)
    {
        $transformed = parent::transform($transformable);
        static::trim($transformed);

        return $transformed;
    }
}
