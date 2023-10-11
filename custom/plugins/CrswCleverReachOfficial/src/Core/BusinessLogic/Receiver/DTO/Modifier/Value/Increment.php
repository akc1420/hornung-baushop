<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\DTO\Modifier\Value;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\DTO\Modifier\Modifier;

/**
 * Class Increment
 *
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\DTO\Modifier\Value
 */
class Increment extends Modifier
{
    /**
     * @inheritDoc
     */
    public function getType()
    {
        return '+';
    }
}