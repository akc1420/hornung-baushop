<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\Events;

use Crsw\CleverReachOfficial\Core\Infrastructure\Utility\Events\Event;

class ReceiverUpdatedEvent extends Event
{
    const CLASS_NAME = __CLASS__;
    /**
     * @var string
     */
    protected $receiverId;

    /**
     * ReceiverUpdatedEvent constructor.
     *
     * @param string $receiverId
     */
    public function __construct($receiverId)
    {
        $this->receiverId = $receiverId;
    }

    /**
     * @return string
     */
    public function getReceiverId()
    {
        return $this->receiverId;
    }
}