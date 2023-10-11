<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\Events;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\DTO\AbandonedCartTrigger;
use Crsw\CleverReachOfficial\Core\Infrastructure\Utility\Events\Event;

class AbandonedCartEvent extends Event
{
    const CLASS_NAME = __CLASS__;
    /**
     * @var \Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\DTO\AbandonedCartTrigger
     */
    private $trigger;

    /**
     * AbandonedCartEvent constructor.
     *
     * @param \Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\DTO\AbandonedCartTrigger $trigger
     */
    public function __construct(AbandonedCartTrigger $trigger)
    {
        $this->trigger = $trigger;
    }

    /**
     * @return \Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\DTO\AbandonedCartTrigger
     */
    public function getTrigger()
    {
        return $this->trigger;
    }
}