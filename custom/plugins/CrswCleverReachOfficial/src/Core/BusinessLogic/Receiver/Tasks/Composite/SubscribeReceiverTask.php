<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\Tasks\Composite;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\Contracts\ExecutionContextAware;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\Tasks\Composite\Components\ReceiverGroupResolver;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\Tasks\Composite\Components\RemoveReceiverFromBlacklist;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\Tasks\Composite\Components\ResolveReceiver;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\Tasks\Composite\Components\SubscribeReceiver;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\Tasks\Composite\Components\SyncServicesResolver;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\Tasks\Composite\Components\UpsertReceiver;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\Tasks\Composite\Context\SubscribtionStateChangedExecutionContext;
use Crsw\CleverReachOfficial\Core\Infrastructure\Serializer\Serializer;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\CompositeTask;

class SubscribeReceiverTask extends CompositeTask
{
    const CLASS_NAME = __CLASS__;
    /**
     * @var \Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\Tasks\Composite\Context\SubscribtionStateChangedExecutionContext
     */
    public $executionContext;

    public function __construct($email = '')
    {
        parent::__construct($this->getSubTasks());

        $this->executionContext = new SubscribtionStateChangedExecutionContext($email);
    }

    public function serialize()
    {
        return Serializer::serialize(
            array(
                'parent' => parent::serialize(),
                'executionContext' => Serializer::serialize($this->executionContext),
            )
        );
    }

    public function unserialize($serialized)
    {
        $unserialized = Serializer::unserialize($serialized);
        parent::unserialize($unserialized['parent']);
        $this->executionContext = Serializer::unserialize($unserialized['executionContext']);
    }

    public function toArray()
    {
        $result = parent::toArray();
        $result['executionContext'] = $this->executionContext->toArray();

        return $result;
    }

    public static function fromArray(array $serializedData)
    {
        $entity = parent::fromArray($serializedData);

        $entity->executionContext = SubscribtionStateChangedExecutionContext::fromArray(
            $serializedData['executionContext']
        );

        return $entity;
    }

    protected static function createTask(array $tasks, $initialProgress)
    {
        return new static();
    }

    /**
     * Registers execution context for subtasks when deserialization is complete.
     */
    public function onUnserialized()
    {
        parent::onUnserialized();

        /** @var ExecutionContextAware $task */
        foreach ($this->tasks as $task) {
            $task->setExecutionContextProvider(array($this, 'getExecutionContext'));
        }
    }

    /**
     * Retrieves execution context.
     *
     * @return SubscribtionStateChangedExecutionContext
     */
    public function getExecutionContext()
    {
        return $this->executionContext;
    }

    /**
     * @inheritDoc
     */
    protected function createSubTask($taskKey)
    {
        $this->reportAlive();

        /** @var ExecutionContextAware $task */
        $task = new $taskKey;
        $task->setExecutionContextProvider(array($this, 'getExecutionContext'));

        return $task;
    }

    /**
     * Retrieves list of sub tasks.
     *
     * @return array
     */
    protected function getSubTasks()
    {
        return array(
            ReceiverGroupResolver::CLASS_NAME => 5,
            SyncServicesResolver::CLASS_NAME => 10,
            ResolveReceiver::CLASS_NAME => 5,
            SubscribeReceiver::CLASS_NAME => 45,
            RemoveReceiverFromBlacklist::CLASS_NAME => 5,
            UpsertReceiver::CLASS_NAME => 30,
        );
    }
}