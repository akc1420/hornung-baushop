<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\WebHookEvent\Tasks\SubTasks;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Group\Contracts\GroupService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\WebHookEvent\Http\Proxy;
use Crsw\CleverReachOfficial\Core\BusinessLogic\WebHookEvent\Tasks\Contracts\ExecutionContextAware;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\Task;

abstract class SubTask extends Task implements ExecutionContextAware
{
    /**
     * @var callable
     */
    private $contextProvider;

    public function setExecutionContextProvider(callable $provider)
    {
        $this->contextProvider = $provider;
    }

    /**
     * Retrieves execution context.
     *
     * @return \Crsw\CleverReachOfficial\Core\BusinessLogic\WebHookEvent\Tasks\Context\ExecutionContext
     */
    protected function getExecutionContext()
    {
        return call_user_func($this->contextProvider);
    }

    /**
     * Retrieves events service.
     *
     * @return \Crsw\CleverReachOfficial\Core\BusinessLogic\WebHookEvent\Contracts\EventsService | object
     */
    protected function getEventsService()
    {
        return ServiceRegister::getService($this->getExecutionContext()->getEventServiceClass());
    }

    /**
     * Retrieves events proxy.
     *
     * @return Proxy | object
     */
    protected function getEventsProxy()
    {
        return ServiceRegister::getService(Proxy::CLASS_NAME);
    }

    /**
     * Provides group service.
     *
     * @return GroupService | object
     */
    protected function getGroupService()
    {
        return ServiceRegister::getService(GroupService::CLASS_NAME);
    }
}