<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\Contracts\ReceiverService as BaseService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\DTO\Receiver;
use Crsw\CleverReachOfficial\Core\Infrastructure\Singleton;

abstract class ReceiverService extends Singleton implements BaseService
{
    /**
     * Singleton instance of this class.
     *
     * @var static
     */
    protected static $instance;

    /**
     * Performs subscribe specific actions.
     *
     * @param \Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\DTO\Receiver $receiver
     *
     * @return void
     */
    public function subscribe(Receiver $receiver)
    {

    }

    /**
     * Performs unsubscribe specific actions.
     *
     * @param \Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\DTO\Receiver $receiver
     *
     * @return void
     */
    public function unsubscribe(Receiver $receiver)
    {

    }
}