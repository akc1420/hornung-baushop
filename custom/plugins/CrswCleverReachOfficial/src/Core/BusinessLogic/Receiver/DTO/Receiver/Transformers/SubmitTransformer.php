<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\DTO\Receiver\Transformers;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\DTO\Modifier\Modifier;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\DTO\Receiver;
use Crsw\CleverReachOfficial\Core\Infrastructure\Data\DataTransferObject;
use Crsw\CleverReachOfficial\Core\Infrastructure\Data\Transformer;

/**
 * Class SubmitTransformer
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\DTO\Receiver\Transformers
 */
class SubmitTransformer extends Transformer
{
    /**
     * Transforms receiver to a format suitable for submitting on CleverReach API.
     *
     * @param DataTransferObject | Receiver $transformable
     *
     * @return array
     */
    public static function transform(DataTransferObject $transformable)
    {
        $result = $transformable->toArray();
        static::applyModifiers($result);
        $result = array_intersect_key($result, array_flip(static::getAllowedFields()));
        static::trim($result);

        return $result;
    }

    /**
     * Apply defined modifiers to receiver fields.
     *
     * @param array $result
     */
    protected static function applyModifiers(array &$result)
    {
        if (!empty($result['modifiers'])) {
            foreach ($result['modifiers'] as $modifier) {
                if (in_array($modifier['field'], static::getPrimaryFields(), true)) {
                    static::modifyData($result, Modifier::fromArray($modifier));
                } else {
                    static::modifyData($result['global_attributes'], Modifier::fromArray($modifier));
                }
            }
        }
    }

    /**
     * Retrieves list of primary (non-global) attributes.
     *
     * @return array
     */
    protected static function getPrimaryFields()
    {
        return array('tags');
    }

    /**
     * Creates modified value.
     *
     * @param array $data Reference to a modifiable data.
     * @param \Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\DTO\Modifier\Modifier $modifier Modifier details.
     */
    protected static function modifyData(array &$data, Modifier $modifier)
    {
        $value = $modifier->getType() . $modifier->getValue();

        $field = $modifier->getField();
        if (is_array($data[$field])) {
            $data[$field][] = $value;
        } else {
            $data[$field] = $value;
        }
    }

    /**
     * Retrieves list of submittable keys.
     *
     * @return array
     */
    protected static function getAllowedFields()
    {
        return array(
            'email',
            'source',
            'activated',
            'deactivated',
            'registered',
            'global_attributes',
            'tags',
            'orders',
        );
    }
}