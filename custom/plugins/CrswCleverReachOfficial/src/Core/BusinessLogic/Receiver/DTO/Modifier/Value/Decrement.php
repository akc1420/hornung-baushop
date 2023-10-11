<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\DTO\Modifier\Value;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\DTO\Modifier\Modifier;

/**
 * Class Decrement
 *
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\DTO\Modifier\Value
 */
class Decrement extends Modifier
{
    public function getType()
    {
        return '-';
    }
}