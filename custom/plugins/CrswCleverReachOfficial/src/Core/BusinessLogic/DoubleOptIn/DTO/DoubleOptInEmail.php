<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\DoubleOptIn\DTO;

use Crsw\CleverReachOfficial\Core\Infrastructure\Data\DataTransferObject;
use Crsw\CleverReachOfficial\Core\Infrastructure\Serializer\Interfaces\Serializable;
use Crsw\CleverReachOfficial\Core\Infrastructure\Serializer\Serializer;

/**
 * Class DoubleOptInEmail
 *
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic\DoubleOptIn\DTO
 */
class DoubleOptInEmail extends DataTransferObject implements Serializable
{
    const CLASS_NAME = __CLASS__;

    /**
     * @var int
     */
    protected $formId;
    /**
     * @var string
     */
    protected $type;
    /**
     * @var string
     */
    protected $email;
    /**
     * @var DoiData
     */
    protected $doiData;

    /**
     * DoubleOptInEmail constructor.
     *
     * @param int $formId
     * @param string $type
     * @param string $email
     * @param DoiData $doiData
     */
    public function __construct($formId, $type, $email, DoiData $doiData)
    {
        $this->formId = $formId;
        $this->type = $type;
        $this->email = $email;
        $this->doiData = $doiData;
    }

    /**
     * @return string
     */
    public function serialize()
    {
        return Serializer::serialize(array(
            $this->formId,
            $this->type,
            $this->email,
            $this->doiData,
        ));
    }

    /**
     * 2@inheritDoc
     * @param string $serialized
     */
    public function unserialize($serialized)
    {
        list($this->formId, $this->type, $this->email, $this->doiData) = Serializer::unserialize($serialized);
    }

    /**
     * @inheritDoc
     */
    public function toArray()
    {
        return array(
            'form_id' => $this->formId,
            'type' => $this->type,
            'email' => $this->email,
            'doidata' => $this->doiData->toArray(),
        );
    }

    /**
     * @return int
     */
    public function getFormId()
    {
        return $this->formId;
    }

    /**
     * @param int $formId
     */
    public function setFormId($formId)
    {
        $this->formId = $formId;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param string $email
     */
    public function setEmail($email)
    {
        $this->email = $email;
    }

    /**
     * @return \Crsw\CleverReachOfficial\Core\BusinessLogic\DoubleOptIn\DTO\DoiData
     */
    public function getDoiData()
    {
        return $this->doiData;
    }

    /**
     * @param \Crsw\CleverReachOfficial\Core\BusinessLogic\DoubleOptIn\DTO\DoiData $doiData
     */
    public function setDoiData($doiData)
    {
        $this->doiData = $doiData;
    }

    /**
     * @param array $data
     *
     * @return DoubleOptInEmail
     */
    public static function fromArray(array $data)
    {
        return new static(
            static::getDataValue($data, 'form_id'),
            static::getDataValue($data, 'type'),
            static::getDataValue($data, 'email'),
            DoiData::fromArray(static::getDataValue($data, 'doidata', array()))
        );
    }
}
