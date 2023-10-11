<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\DTO\Tag\Special;

class Subscriber extends SpecialTag
{
    /**
     * Subscriber constructor.
     *
     * @param string $source
     */
    public function __construct($source)
    {
        parent::__construct($source, 'Subscriber');
    }
}