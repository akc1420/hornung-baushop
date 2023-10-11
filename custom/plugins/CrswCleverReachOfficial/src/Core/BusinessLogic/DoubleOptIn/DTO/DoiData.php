<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\DoubleOptIn\DTO;

use Crsw\CleverReachOfficial\Core\Infrastructure\Data\DataTransferObject;
use Crsw\CleverReachOfficial\Core\Infrastructure\Serializer\Interfaces\Serializable;
use Crsw\CleverReachOfficial\Core\Infrastructure\Serializer\Serializer;

/**
 * Class DoiData
 *
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic\DoubleOptIn
 */
class DoiData extends DataTransferObject implements Serializable
{
    /**
     * @var string
     */
    protected $userIp;
    /**
     * @var string
     */
    protected $referer;
    /**
     * @var string
     */
    protected $userAgent;

    /**
     * DoiData constructor.
     *
     * @param string $userIp
     * @param string $referer
     * @param string $userAgent
     */
    public function __construct($userIp, $referer, $userAgent)
    {
        $this->userIp = $userIp;
        $this->referer = $referer;
        $this->userAgent = $userAgent;
    }

    /**
     * @inheritDoc
     * @return string
     */
    public function serialize()
    {
        return Serializer::serialize($this->toArray());
    }

    /**
     * @inheritDoc
     * @param string $serialized
     */
    public function unserialize($serialized)
    {
        $unserialized = Serializer::unserialize($serialized);
        $this->userIp = $unserialized['user_ip'];
        $this->referer = $unserialized['referer'];
        $this->userAgent = $unserialized['user_agent'];
    }

    /**
     * @inheritDoc
     */
    public function toArray()
    {
        return array(
            'user_ip' => $this->userIp,
            'referer' => $this->referer,
            'user_agent' => $this->userAgent
        );
    }

    /**
     * @param array $data
     *
     * @return DoiData
     */
    public static function fromArray(array $data)
    {
        return new static(
            static::getDataValue($data, 'user_ip'),
            static::getDataValue($data, 'referer'),
            static::getDataValue($data, 'user_agent')
        );
    }
}