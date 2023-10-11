<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Form\Tasks;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Form\Tasks\Cache\Subtasks\CacheCreatedForms;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Form\Tasks\Cache\Subtasks\DeleteRemovedForms;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Form\Tasks\Cache\Subtasks\FormCacheUpdater;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Form\Tasks\Cache\Subtasks\RetrieveForms;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Form\Tasks\Cache\Subtasks\UpdateUpdatedForms;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\CompositeTask;

/**
 * Class CacheFormsTask
 *
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic\Form\Tasks
 */
class CacheFormsTask extends CompositeTask
{
    const CLASS_NAME = __CLASS__;

    /**
     * CacheFormsTask constructor.
     */
    public function __construct()
    {
        parent::__construct(array(
            RetrieveForms::CLASS_NAME => 10,
            DeleteRemovedForms::CLASS_NAME => 30,
            UpdateUpdatedForms::CLASS_NAME => 30,
            CacheCreatedForms::CLASS_NAME => 30,
        ));
    }

    /**
     * Provides forms.
     *
     * @return \Crsw\CleverReachOfficial\Core\BusinessLogic\Form\DTO\Form[]
     */
    public function getForms()
    {
        /** @var RetrieveForms $task */
        $task = $this->tasks[RetrieveForms::CLASS_NAME];

        return $task->getForms();
    }

    /**
     * @inheritDoc
     */
    protected static function createTask(array $tasks, $initialProgress)
    {
        return new static();
    }

    /**
     * @inheritDoc
     */
    public function onUnserialized()
    {
        parent::onUnserialized();

        foreach ($this->tasks as $task) {
            if (!($task instanceof FormCacheUpdater)) {
                continue;
            }

            $task->setFormsProvider(array($this, 'getForms'));
        }
    }

    /**
     * @inheritDoc
     */
    protected function createSubTask($taskKey)
    {
        $task = new $taskKey;

        if ($task instanceof FormCacheUpdater) {
            $task->setFormsProvider(array($this, 'getForms'));
        }

        return $task;
    }
}