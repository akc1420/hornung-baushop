<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Stats\Entity;

use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Configuration\EntityConfiguration;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Configuration\IndexMap;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Entity;

/**
 * Class Stats
 *
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic\Stats\Entity
 */
class Stats extends Entity
{
    const CLASS_NAME = __CLASS__;

    protected $fields = array('id', 'context', 'createdAt', 'subscribed', 'unsubscribed');

    /**
     * @var string
     */
    protected $context;
    /**
     * @var \DateTime
     */
    protected $createdAt;
    /**
     * @var int
     */
    protected $subscribed;
    /**
     * @var int
     */
    protected $unsubscribed;

    /**
     * @return string
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * @param string $context
     */
    public function setContext($context)
    {
        $this->context = $context;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param $createdAt
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
    }

    /**
     * @return int
     */
    public function getSubscribed()
    {
        return $this->subscribed;
    }

    /**
     * @param int $subscribed
     */
    public function setSubscribed($subscribed)
    {
        $this->subscribed = $subscribed;
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
     * @inheritDoc
     */
    public function getConfig()
    {
        $indexMap = new IndexMap();
        $indexMap->addDateTimeIndex('createdAt')
            ->addIntegerIndex('subscribed')
            ->addIntegerIndex('unsubscribed')
            ->addStringIndex('context');

        return new EntityConfiguration($indexMap, 'Stats');
    }
}
