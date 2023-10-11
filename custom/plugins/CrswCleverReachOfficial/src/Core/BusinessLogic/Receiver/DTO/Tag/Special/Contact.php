<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\DTO\Tag\Special;

/**
 * Class Contact
 *
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\DTO\Tag\Special
 */
class Contact extends SpecialTag
{
    /**
     * Contact constructor.
     *
     * @param string $source
     */
    public function __construct($source)
    {
        parent::__construct($source, 'Contact');
    }
}