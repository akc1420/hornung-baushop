<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\Events;

use Crsw\CleverReachOfficial\Core\BusinessLogic\WebHookEvent\DTO\WebHook;
use Crsw\CleverReachOfficial\Core\Infrastructure\Utility\Events\Event;

class AutomationActivatedEvent extends Event
{
    /**
     * Fully qualified name of this class.
     */
    const CLASS_NAME = __CLASS__;
    /**
     * Webhook that resulted in firing of this event.
     *
     * @var \Crsw\CleverReachOfficial\Core\BusinessLogic\WebHookEvent\DTO\WebHook $hook
     */
    protected $hook;

    /**
     * AutomationActivatedEvent constructor.
     *
     * @param \Crsw\CleverReachOfficial\Core\BusinessLogic\WebHookEvent\DTO\WebHook $hook
     */
    public function __construct(WebHook $hook)
    {
        $this->hook = $hook;
    }

    /**
     * Retrieves webhook.
     *
     * @return \Crsw\CleverReachOfficial\Core\BusinessLogic\WebHookEvent\DTO\WebHook
     */
    public function getHook()
    {
        return $this->hook;
    }
}