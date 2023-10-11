<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\DTO\Tag\Special;

/**
 * Class Buyer
 *
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\DTO\Tag\Special
 */
class Buyer extends SpecialTag
{
    /**
     * Buyer constructor.
     *
     * @param string $source
     */
    public function __construct($source)
    {
        parent::__construct($source, 'Buyer');
    }
}