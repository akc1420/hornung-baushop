<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Tasks\Create\Subtasks;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Interfaces\CartAutomationService;
use Crsw\CleverReachOfficial\Core\Infrastructure\Serializer\Serializer;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\Exceptions\AbortTaskExecutionException;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\Task;

/**
 * Class FinalizeAutomationCreation
 *
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Tasks\Create\Subtasks
 */
class FinalizeAutomationCreation extends Task
{
    const CLASS_NAME = __CLASS__;
    /**
     * @var int
     */
    protected $automationId;

    /**
     * Transforms serializable object into an array.
     *
     * @return array Array representation of a serializable object.
     */
    public function toArray()
    {
        return array('automationId' => $this->automationId);
    }

    /**
     * Transforms array into an serializable object,
     *
     * @param array $array Data that is used to instantiate serializable object.
     *
     * @return \Crsw\CleverReachOfficial\Core\Infrastructure\Serializer\Interfaces\Serializable
     *      Instance of serialized object.
     */
    public static function fromArray(array $array)
    {
        return new static($array['automationId']);
    }

    /**
     * String representation of object
     *
     * @link https://php.net/manual/en/serializable.serialize.php
     *
     * @return string the string representation of the object or null
     */
    public function serialize()
    {
        return Serializer::serialize($this->automationId);
    }

    /**
     * Constructs the object
     *
     * @param string $serialized
     *
     * @return void
     */
    public function unserialize($serialized)
    {
        $this->automationId = Serializer::unserialize($serialized);
    }

    /**
     * FinalizeAutomationCreation constructor.
     *
     * @param int $automationId
     */
    public function __construct($automationId)
    {
        $this->automationId = $automationId;
    }

    /**
     * Sets the status that denotes whether the automation is successfully created or not.
     *
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Exceptions\FailedToUpdateCartException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\Exceptions\AbortTaskExecutionException
     */
    public function execute()
    {
        $automation = $this->getCartService()->find($this->automationId);
        if ($automation === null || !$automation->getCondition()) {
            throw new AbortTaskExecutionException("Invalid automation.");
        }

        $this->reportProgress(40);

        $condition = $automation->getCondition();
        $webhookCallToken = $automation->getWebhookCallToken();

        $status = !empty($condition) && !empty($webhookCallToken) ? 'created' : 'incomplete';
        $automation->setStatus($status);
        $this->getCartService()->update($automation);

        $this->reportProgress(100);
    }

    /**
     * Provides cart automation service.
     *
     * @return CartAutomationService | object
     */
    protected function getCartService()
    {
        return ServiceRegister::getService(CartAutomationService::CLASS_NAME);
    }
}