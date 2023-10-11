<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\Tasks\Composite;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\Contracts\ExecutionContextAware;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\Events\ReceiverEventBus;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\Events\ReceiverSyncTaskCompletedEvent;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\Events\ReceiverSyncTaskStartedEvent;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\Tasks\Composite\Components\BlacklistedEmailsResolver;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\Tasks\Composite\Components\ReceiverEmailsResolver;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\Tasks\Composite\Components\ReceiverGroupResolver;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\Tasks\Composite\Components\ReceiversExporter;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\Tasks\Composite\Components\SyncServicesResolver;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\Tasks\Composite\Configuration\SyncConfiguration;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\Tasks\Composite\Context\ExecutionContext;
use Crsw\CleverReachOfficial\Core\Infrastructure\Serializer\Serializer;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\CompositeTask;

/**
 * Class ReceiverSyncTask
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\Tasks\Composite
 */
class ReceiverSyncTask extends CompositeTask
{
    const CLASS_NAME = __CLASS__;

    /**
     * @var \Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\Tasks\Composite\Context\ExecutionContext
     */
    private $executionContext;

    /**
     * ReceiverSyncTask constructor.
     *
     * @param \Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\Tasks\Composite\Configuration\SyncConfiguration | null $configuration
     */
    public function __construct(SyncConfiguration $configuration = null)
    {
        parent::__construct($this->getSubTasks());

        $this->executionContext = new ExecutionContext();

        if ($configuration !== null) {
            $this->executionContext->syncConfiguration = $configuration;
        }
    }

    public function execute()
    {
        ReceiverEventBus::getInstance()->fire(new ReceiverSyncTaskStartedEvent());

        parent::execute();

        ReceiverEventBus::getInstance()->fire(new ReceiverSyncTaskCompletedEvent());
    }

    /**
     * @inheritDoc
     */
    public function serialize()
    {
        return Serializer::serialize(
            array(
                'parent' => parent::serialize(),
                'executionContext' => Serializer::serialize($this->executionContext),
            )
        );
    }

    /**
     * @inheritDoc
     */
    public function unserialize($serialized)
    {
        $unserialized = Serializer::unserialize($serialized);
        parent::unserialize($unserialized['parent']);
        $this->executionContext = Serializer::unserialize($unserialized['executionContext']);
    }

    /**
     * @inheritDoc
     */
    public function toArray()
    {
        $result = parent::toArray();
        $result['executionContext'] = $this->executionContext->toArray();

        return $result;
    }

    /**
     * @inheritDoc
     */
    public static function fromArray(array $data)
    {
        $entity = parent::fromArray($data);

        $entity->executionContext = ExecutionContext::fromArray($data['executionContext']);

        return $entity;
    }

    /**
     * @inheritDoc
     */
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
     * @return ExecutionContext
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
     * Retrieves list of sub-tasks with progress percentage share.
     *
     * @return array
     */
    protected function getSubTasks()
    {
        return array(
            ReceiverGroupResolver::CLASS_NAME => 2,
            BlacklistedEmailsResolver::CLASS_NAME => 5,
            SyncServicesResolver::CLASS_NAME => 3,
            ReceiverEmailsResolver::CLASS_NAME => 10,
            ReceiversExporter::CLASS_NAME => 80,
        );
    }
}