<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\WebHookEvent\DTO;

use Crsw\CleverReachOfficial\Core\Infrastructure\Data\DataTransferObject;

/**
 * Class Event
 *
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic\WebHookEvent\DTO
 */
class Event extends DataTransferObject
{
    /**
     * Url that is used for event handling.
     *
     * @var string
     */
    private $url;
    /**
     * This parameter is exported to the api as `condition` field.
     *
     * @var string
     */
    private $groupId;
    /**
     * Event type [receiver | form].
     *
     * @var string
     */
    private $event;
    /**
     * Token used during event registration for verification of the event handling endpoint.
     *
     * @var string
     */
    private $verificationToken;

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @param string $url
     */
    public function setUrl($url)
    {
        $this->url = $url;
    }

    /**
     * @return string
     */
    public function getGroupId()
    {
        return $this->groupId;
    }

    /**
     * @param string $groupId
     */
    public function setGroupId($groupId)
    {
        $this->groupId = $groupId;
    }

    /**
     * @return string
     */
    public function getEvent()
    {
        return $this->event;
    }

    /**
     * @param string $event
     */
    public function setEvent($event)
    {
        $this->event = $event;
    }

    /**
     * @return string
     */
    public function getVerificationToken()
    {
        return $this->verificationToken;
    }

    /**
     * @param string $verificationToken
     */
    public function setVerificationToken($verificationToken)
    {
        $this->verificationToken = $verificationToken;
    }

    /**
     * @inheritDoc
     */
    public function toArray()
    {
        return array(
            'url' => $this->getUrl(),
            'event' => $this->getEvent(),
            'condition' => $this->getGroupId(),
            'verify' => $this->getVerificationToken(),
        );
    }

    /**
     * Creates self instance from a raw data.
     *
     * @param array $data
     *
     * @return \Crsw\CleverReachOfficial\Core\BusinessLogic\WebHookEvent\DTO\Event
     */
    public static function fromArray(array $data)
    {
        $entity = new static();
        $entity->setUrl(static::getDataValue($data, 'url'));
        $entity->setEvent(static::getDataValue($data, 'event'));
        $entity->setGroupId(static::getDataValue($data, 'condition'));
        $entity->setVerificationToken(static::getDataValue($data, 'verify'));

        return $entity;
    }
}