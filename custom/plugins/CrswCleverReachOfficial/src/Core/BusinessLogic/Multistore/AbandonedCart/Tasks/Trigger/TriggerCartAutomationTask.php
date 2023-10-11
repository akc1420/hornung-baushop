<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Tasks\Trigger;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Http\Proxy;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Interfaces\AutomationRecordService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Interfaces\AutomationRecordTrigger;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Interfaces\Required\CartAutomationTriggerService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Tasks\Trigger\Filter\FilterChain;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Scheduler\Interfaces\Schedulable;
use Crsw\CleverReachOfficial\Core\Infrastructure\Serializer\Serializer;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\Exceptions\AbortTaskExecutionException;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\Task;

class TriggerCartAutomationTask extends Task implements Schedulable, AutomationRecordTrigger
{
    /**
     * @var int
     */
    protected $recordId;

    /**
     * Transforms serializable object into an array.
     *
     * @return array Array representation of a serializable object.
     */
    public function toArray()
    {
        return array('recordId' => $this->recordId);
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
        return new static($array['recordId']);
    }

    /**
     * @return string
     */
    public function getRecordId()
    {
        return $this->recordId;
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
        return Serializer::serialize($this->recordId);
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
        $this->recordId = Serializer::unserialize($serialized);
    }

    /**
     * TriggerCartAutomationTask constructor.
     *
     * @param int $recordId
     */
    public function __construct($recordId)
    {
        $this->recordId = $recordId;
    }

    /**
     * Triggers abandoned cart automation.
     *
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRefreshAccessToken
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRetrieveAuthInfoException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpRequestException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\Exceptions\AbortTaskExecutionException
     */
    public function execute()
    {
        $record = $this->getRecordService()->find($this->recordId);
        if ($record === null) {
            throw new AbortTaskExecutionException('Abandoned cart record is not found.');
        }

        $this->reportProgress(30);

        $trigger = $this->getCartAutomationTriggerService()->getTrigger($record->getCartId());
        if ($trigger === null) {
            throw new AbortTaskExecutionException("Abandoned cart trigger is not found for the provided Cart ID: {$record->getCartId()}");
        }

        $this->reportProgress(60);

        try {
            FilterChain::execute($record, $trigger);
        } catch (\Exception $e) {
            throw new AbortTaskExecutionException("An abandoned cart email has not been sent. Reason: {$e->getMessage()}.", $e->getCode(), $e);
        }

        $this->reportProgress(90);

        $this->getProxy()->trigger($trigger);

        $this->reportProgress(100);
    }

    /**
     * Provides automation record service.
     *
     * @return AutomationRecordService | object
     */
    private function getRecordService()
    {
        return ServiceRegister::getService(AutomationRecordService::CLASS_NAME);
    }

    /**
     * Provides automation trigger service.
     *
     * @return CartAutomationTriggerService | object
     */
    private function getCartAutomationTriggerService()
    {
        return ServiceRegister::getService(CartAutomationTriggerService::CLASS_NAME);
    }

    /**
     * Provides proxy class.
     *
     * @return Proxy | object
     */
    private function getProxy()
    {
        return ServiceRegister::getService(Proxy::CLASS_NAME);
    }

    /**
     * Defines whether schedulable task can be enqueued for execution if there is already instance with queued status.
     *
     * @return bool False indicates that the schedulable task should not enqueued if there
     *      is already instance in queued status.
     */
    public function canHaveMultipleQueuedInstances()
    {
        return true;
    }
}
