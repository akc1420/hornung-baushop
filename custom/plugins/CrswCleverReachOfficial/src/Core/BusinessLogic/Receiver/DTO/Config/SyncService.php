<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\DTO\Config;

use Crsw\CleverReachOfficial\Core\Infrastructure\Data\DataTransferObject;

class SyncService extends DataTransferObject
{
    const CLASS_NAME = __CLASS__;
    /**
     * @var string
     */
    protected $uuid;
    /**
     * Receiver provided by the service with higher priority will override the data of the same receiver
     * provided by the service with lesser priority.
     *
     * @see https://logeecom.atlassian.net/wiki/spaces/CR/pages/1424982241/ref+Prepare+services+groupId+and+blacklist
     *
     * @var int
     */
    protected $priority;
    /**
     * Receiver service identifier (most commonly service's class name).
     *
     * @var string
     */
    protected $service;
    /**
     * Merger used to merger receiver with the provided receiver.
     * Default Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\Merger.
     *
     * @var string
     */
    protected $merger;

    /**
     * SyncService constructor.
     *
     * @param string $uuid
     * @param int $priority
     * @param string $service
     * @param string $merger
     */
    public function __construct($uuid, $priority, $service, $merger = '')
    {
        $this->uuid = $uuid;
        $this->priority = $priority;
        $this->service = $service;
        $this->merger = $merger;
    }

    /**
     * @return string
     */
    public function getUuid()
    {
        return $this->uuid;
    }

    /**
     * @return int
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * @return string
     */
    public function getService()
    {
        return $this->service;
    }

    /**
     * @return string
     */
    public function getMerger()
    {
        return $this->merger;
    }

    /**
     * Creates instance of SyncService from array.
     *
     * @param array $data
     *
     * @return \Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\DTO\Config\SyncService
     */
    public static function fromArray(array $data)
    {
        return new self(
            static::getDataValue($data, 'uuid'),
            static::getDataValue($data, 'priority', 1),
            static::getDataValue($data, 'service'),
            static::getDataValue($data, 'merger')
        );
    }

    /**
     * Transforms SyncService instance to array.
     *
     * @return array
     */
    public function toArray()
    {
        return array(
            'uuid' => $this->getUuid(),
            'priority' => $this->getPriority(),
            'service' => $this->getService(),
            'merger' => $this->getMerger(),
        );
    }
}