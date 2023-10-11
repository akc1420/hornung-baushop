<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\Tasks\Composite\Context;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\DTO\Config\SyncService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\Tasks\Composite\Configuration\SyncConfiguration;
use Crsw\CleverReachOfficial\Core\Infrastructure\Data\Transformer;
use Crsw\CleverReachOfficial\Core\Infrastructure\Serializer\Interfaces\Serializable;
use Crsw\CleverReachOfficial\Core\Infrastructure\Serializer\Serializer;

class ExecutionContext implements Serializable
{
    /**
     * @var \Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\Tasks\Composite\Configuration\SyncConfiguration
     */
    public $syncConfiguration;
    /**
     * @var string
     */
    public $groupId;
    /**
     * @var \Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\DTO\Config\SyncService[]
     */
    public $services;
    /**
     * @var string[]
     */
    public $blacklistedEmails;
    /**
     * @var array
     */
    public $receiverEmails;

    /**
     * ExecutionContext constructor.
     */
    public function __construct()
    {
        $this->syncConfiguration = new SyncConfiguration();
        $this->groupId = '';
        $this->services = array();
        $this->blacklistedEmails = array();
        $this->receiverEmails = array();
    }

    /**
     * @inheritDoc
     */
    public function serialize()
    {
        return Serializer::serialize(
            array(
                $this->syncConfiguration,
                $this->groupId,
                $this->services,
                $this->blacklistedEmails,
                $this->receiverEmails,
            )
        );
    }

    /**
     * @inheritDoc
     */
    public function unserialize($serialized)
    {
        list(
            $this->syncConfiguration,
            $this->groupId,
            $this->services,
            $this->blacklistedEmails,
            $this->receiverEmails
            ) = Serializer::unserialize($serialized);
    }

    /**
     * @inheritDoc
     */
    public function toArray()
    {
        return array(
            'syncConfiguration' => $this->syncConfiguration->toArray(),
            'groupId' => $this->groupId,
            'services' => Transformer::batchTransform($this->services),
            'blacklistedEmails' => $this->blacklistedEmails,
            'receiverEmails' => $this->receiverEmails,
        );
    }

    /**
     * @inheritDoc
     */
    public static function fromArray(array $data)
    {
        $self = new static();

        $self->syncConfiguration = SyncConfiguration::fromArray($data['syncConfiguration']);
        $self->groupId = $data['groupId'];
        $self->services = SyncService::fromBatch($data['services']);
        $self->blacklistedEmails = $data['blacklistedEmails'];
        $self->receiverEmails = $data['receiverEmails'];

        return $self;
    }
}