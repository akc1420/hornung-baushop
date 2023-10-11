<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\DTO;

use Crsw\CleverReachOfficial\Core\Infrastructure\Data\DataTransferObject;

class AbandonedCartSettings extends DataTransferObject
{
    /**
     * @var int
     */
    protected $delay;

    /**
     * AbandonedCartSettings constructor.
     *
     * @param int $delay
     */
    public function __construct($delay)
    {
        $this->delay = $delay;
    }

    /**
     * @return int
     */
    public function getDelay()
    {
        return $this->delay;
    }

    /**
     * @param int $delay
     */
    public function setDelay($delay)
    {
        $this->delay = $delay;
    }

    /**
     * Returns array representation of an object.
     *
     * @return array
     */
    public function toArray()
    {
        return array('delay' => $this->getDelay());
    }

    /**
     * Instantiates entity from an array.
     *
     * @param array $data
     *
     * @return \Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\DTO\AbandonedCartSettings
     */
    public static function fromArray(array $data)
    {
        return new static(self::getDataValue($data, 'delay', 0));
    }
}