<?php


namespace Crsw\CleverReachOfficial\Mergers;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\DTO\Receiver;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\Merger\Merger;

/**
 * Class BuyerMerger
 *
 * @package Crsw\CleverReachOfficial\Mergers
 */
class BuyerMerger extends Merger
{
    /**
     * Singleton instance of this class.
     *
     * @var static
     */
    protected static $instance;

    /**
     * Performs merge of base fields.
     *
     * @param Receiver $from
     * @param Receiver $to
     */
    public function merge(Receiver $from, Receiver $to): void
    {
        parent::merge($from, $to);

	    $to->setLastOrderDate($from->getLastOrderDate());
	    $to->setOrderCount($from->getOrderCount());
	    $to->setTotalSpent($from->getTotalSpent());
        $to->setOrderItems($from->getOrderItems());
    }
}