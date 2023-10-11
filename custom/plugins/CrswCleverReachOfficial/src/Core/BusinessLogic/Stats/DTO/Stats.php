<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Stats\DTO;

use Crsw\CleverReachOfficial\Core\Infrastructure\Data\DataTransferObject;

/**
 * Class Stats
 *
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic\Stats\DTO
 */
class Stats extends DataTransferObject
{
    /**
     * @var int
     */
    protected $totalReceiverCount = 0;
    /**
     * @var int
     */
    protected $inactiveReceiverCount = 0;
    /**
     * @var int
     */
    protected $activeCount = 0;
    /**
     * @var int
     */
    protected $bounceCount = 0;
    /**
     * @var int
     */
    protected $averagePoints = 0;
    /**
     * @var int
     */
    protected $quality = 0;
    /**
     * @var int
     */
    protected $orderCount = 0;
    /**
     * @var int
     */
    protected $time;

    /**
     * @return int
     */
    public function getTotalReceiverCount()
    {
        return $this->totalReceiverCount;
    }

    /**
     * @param int $totalReceiverCount
     */
    public function setTotalReceiverCount($totalReceiverCount)
    {
        $this->totalReceiverCount = $totalReceiverCount;
    }

    /**
     * @return int
     */
    public function getInactiveReceiverCount()
    {
        return $this->inactiveReceiverCount;
    }

    /**
     * @param int $inactiveReceiverCount
     */
    public function setInactiveReceiverCount($inactiveReceiverCount)
    {
        $this->inactiveReceiverCount = $inactiveReceiverCount;
    }

    /**
     * @return int
     */
    public function getActiveCount()
    {
        return $this->activeCount;
    }

    /**
     * @param int $activeCount
     */
    public function setActiveCount($activeCount)
    {
        $this->activeCount = $activeCount;
    }

    /**
     * @return int
     */
    public function getBounceCount()
    {
        return $this->bounceCount;
    }

    /**
     * @param int $bounceCount
     */
    public function setBounceCount($bounceCount)
    {
        $this->bounceCount = $bounceCount;
    }

    /**
     * @return int
     */
    public function getAveragePoints()
    {
        return $this->averagePoints;
    }

    /**
     * @param int $averagePoints
     */
    public function setAveragePoints($averagePoints)
    {
        $this->averagePoints = $averagePoints;
    }

    /**
     * @return int
     */
    public function getQuality()
    {
        return $this->quality;
    }

    /**
     * @param int $quality
     */
    public function setQuality($quality)
    {
        $this->quality = $quality;
    }

    /**
     * @return int
     */
    public function getOrderCount()
    {
        return $this->orderCount;
    }

    /**
     * @param int $orderCount
     */
    public function setOrderCount($orderCount)
    {
        $this->orderCount = $orderCount;
    }

    /**
     * @return int
     */
    public function getTime()
    {
        return $this->time;
    }

    /**
     * @param int $time
     */
    public function setTime($time)
    {
        $this->time = $time;
    }

    /**
     * @inheritDoc
     */
    public function toArray()
    {
        return array(
            'total_count' => $this->totalReceiverCount,
            'inactive_count' => $this->inactiveReceiverCount,
            'active_count' => $this->activeCount,
            'bounce_count' => $this->bounceCount,
            'avg_points' => $this->averagePoints,
            'quality' => $this->quality,
            'time' => $this->time,
            'order_count' => $this->orderCount,
        );
    }

    /**
     * @param array $data
     *
     * @return \Crsw\CleverReachOfficial\Core\BusinessLogic\Stats\DTO\Stats
     */
    public static function fromArray(array $data)
    {
        $stats = new static();
        $stats->totalReceiverCount = static::getDataValue($data, 'total_count', 0);
        $stats->inactiveReceiverCount = static::getDataValue($data, 'inactive_count', 0);
        $stats->bounceCount = static::getDataValue($data, 'bounce_count', 0);
        $stats->averagePoints = static::getDataValue($data, 'avg_points', 0);
        $stats->quality = static::getDataValue($data, 'quality', 0);
        $stats->time = static::getDataValue($data, 'time', 0);
        $stats->orderCount = static::getDataValue($data, 'order_count', 0);

        return $stats;
    }
}
