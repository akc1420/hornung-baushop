<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\SyncSettings\Entities;

use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Configuration\EntityConfiguration;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Configuration\IndexMap;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Entity;

class EnabledServicesChangeLog extends Entity
{
    const CLASS_NAME =  __CLASS__;

    /**
     * @var \DateTime
     */
    public $createdAt;
    /**
     * @var string
     */
    public $context;
    /**
     * @var array
     */
    public $services;

    protected $fields = array('id', 'createdAt', 'services', 'context');

    /**
     * @inheritDoc
     */
    public function getConfig()
    {
        $indexMap = new IndexMap();
        $indexMap->addDateTimeIndex('createdAt');
        $indexMap->addStringIndex('context');

        return new EntityConfiguration($indexMap, 'EnabledServicesChangeLog');
    }
}