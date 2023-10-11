<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\Tasks\Composite\Configuration;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\DTO\Tag\Tag;
use Crsw\CleverReachOfficial\Core\Infrastructure\Data\Transformer;
use Crsw\CleverReachOfficial\Core\Infrastructure\Serializer\Interfaces\Serializable;
use Crsw\CleverReachOfficial\Core\Infrastructure\Serializer\Serializer;

/**
 * Class SyncConfiguration
 *
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\Tasks\Composite\Configuration
 */
class SyncConfiguration implements Serializable
{
    /**
     * @var string[]
     */
    private $specificReceivers;
    /**
     * @var \Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\DTO\Tag\Tag[]
     */
    private $tagsToRemove;
    /**
     * @var bool
     */
    private $isClassSpecificDataRequired;

    /**
     * SyncConfiguration constructor.
     *
     * @param string[] $specificReceivers
     * @param \Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\DTO\Tag\Tag[] $tagsToRemove
     * @param bool $isClassSpecificDataRequired
     */
    public function __construct(
        array $specificReceivers = array(),
        array $tagsToRemove = array(),
        $isClassSpecificDataRequired = true
    ) {
        $this->specificReceivers = $specificReceivers;
        $this->tagsToRemove = $tagsToRemove;
        $this->isClassSpecificDataRequired = $isClassSpecificDataRequired;
    }

    /**
     * @inheritDoc
     */
    public function serialize()
    {
        return Serializer::serialize(
            array(
                $this->specificReceivers,
                $this->tagsToRemove,
                $this->isClassSpecificDataRequired,
            )
        );
    }

    /**
     * @inheritDoc
     */
    public function unserialize($serialized)
    {
        list(
            $this->specificReceivers,
            $this->tagsToRemove,
            $this->isClassSpecificDataRequired
            ) = Serializer::unserialize($serialized);
    }

    /**
     * @inheritDoc
     */
    public function toArray()
    {
        return array(
            'specificReceivers' => $this->specificReceivers,
            'tagsToRemove' => Transformer::batchTransform($this->tagsToRemove),
            'isClassSpecificDataRequired' => $this->isClassSpecificDataRequired,
        );
    }

    /**
     * @inheritDoc
     */
    public static function fromArray(array $serializedData)
    {
        $self = new static();

        $self->specificReceivers = $serializedData['specificReceivers'];
        $self->tagsToRemove = Tag::fromBatch($serializedData['tagsToRemove']);
        $self->isClassSpecificDataRequired = $serializedData['isClassSpecificDataRequired'];

        return $self;
    }

    /**
     * @return string[]
     */
    public function getSpecificReceivers()
    {
        return $this->specificReceivers;
    }

    /**
     * @return \Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\DTO\Tag\Tag[]
     */
    public function getTagsToRemove()
    {
        return $this->tagsToRemove;
    }

    /**
     * @return bool
     */
    public function isClassSpecificDataRequired()
    {
        return $this->isClassSpecificDataRequired;
    }
}