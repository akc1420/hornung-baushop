<?php


namespace Crsw\CleverReachOfficial\Components\EventHandlers;


use Crsw\CleverReachOfficial\Core\BusinessLogic\Order\Tasks\Composite\OrderSyncTask;

/**
 * Class OrderHandler
 *
 * @package Crsw\CleverReachOfficial\Components\EventHandlers
 */
class OrderHandler extends BaseHandler
{
    /**
     * Enqueues OrderSyncTask.
     *
     * @param $email
     * @param $orderId
     * @param string $crMailing
     */
    public function orderCreated(string $email, $orderId, string $crMailing = ''): void
    {
        $this->enqueueTask(new OrderSyncTask($orderId, $email, $crMailing));
    }
}