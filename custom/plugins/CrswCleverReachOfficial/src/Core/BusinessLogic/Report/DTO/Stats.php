<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Report\DTO;

use Crsw\CleverReachOfficial\Core\Infrastructure\Data\DataTransferObject;

/**
 * Class Stats
 *
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic\Report\DTO
 */
class Stats extends DataTransferObject
{
    /**
     * @var int
     */
    protected $delivered;
    /**
     * @var int
     */
    protected $unsubscribed;
    /**
     * @var
     */
    protected $spam;
    /**
     * @var int
     */
    protected $uniqueOpened;
    /**
     * @var int
     */
    protected $uniqueClicked;
    /**
     * @var int
     */
    protected $bounced;

    /**
     * @return int
     */
    public function getDelivered()
    {
        return $this->delivered;
    }

    /**
     * @param int $delivered
     */
    public function setDelivered($delivered)
    {
        $this->delivered = $delivered;
    }

    /**
     * @return int
     */
    public function getUnsubscribed()
    {
        return $this->unsubscribed;
    }

    /**
     * @param int $unsubscribed
     */
    public function setUnsubscribed($unsubscribed)
    {
        $this->unsubscribed = $unsubscribed;
    }

    /**
     * @return mixed
     */
    public function getSpam()
    {
        return $this->spam;
    }

    /**
     * @param mixed $spam
     */
    public function setSpam($spam)
    {
        $this->spam = $spam;
    }

    /**
     * @return int
     */
    public function getUniqueOpened()
    {
        return $this->uniqueOpened;
    }

    /**
     * @param int $uniqueOpened
     */
    public function setUniqueOpened($uniqueOpened)
    {
        $this->uniqueOpened = $uniqueOpened;
    }

    /**
     * @return int
     */
    public function getUniqueClicked()
    {
        return $this->uniqueClicked;
    }

    /**
     * @param int $uniqueClicked
     */
    public function setUniqueClicked($uniqueClicked)
    {
        $this->uniqueClicked = $uniqueClicked;
    }

    /**
     * @return int
     */
    public function getBounced()
    {
        return $this->bounced;
    }

    /**
     * @param int $bounced
     */
    public function setBounced($bounced)
    {
        $this->bounced = $bounced;
    }

    /**
     * @param array $data
     *
     * @return \Crsw\CleverReachOfficial\Core\BusinessLogic\Report\DTO\Stats
     */
    public static function fromArray(array $data)
    {
        $stats = new static();
        $stats->delivered = static::getDataValue($data, 'delivered', 0);
        $stats->unsubscribed = static::getDataValue($data, 'unsubscribed', 0);
        $stats->spam = static::getDataValue($data, 'spam', 0);
        $stats->uniqueOpened = static::getDataValue($data, 'unique_opened', 0);
        $stats->uniqueClicked = static::getDataValue($data, 'unique_clicks', 0);
        $stats->bounced = static::getDataValue($data, 'bounced', 0);

        return $stats;
    }

    /**
     * @inheritDoc
     */
    public function toArray()
    {
        return array(
            'delivered' => $this->delivered,
            'unsubscribed' => $this->unsubscribed,
            'spam' => $this->spam,
            'unique_opened' => $this->uniqueOpened,
            'unique_clicks' => $this->uniqueClicked,
            'bounced' => $this->bounced,
        );
    }
}
