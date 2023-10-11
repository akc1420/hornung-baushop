<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\Tasks\Composite\Components;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Group\Contracts\GroupService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\Contracts\ExecutionContextAware;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\Contracts\SyncConfigService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\Http\Proxy;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\Merger\MergerRegistry;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\Tasks\Composite\Context\ExecutionContext;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\Tasks\Composite\Context\SubscribtionStateChangedExecutionContext;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\Task;

abstract class ReceiverSyncSubTask extends Task implements ExecutionContextAware
{
    /**
     * @var callable
     */
    private $executionContextProvider;

    /**
     * @inheritDoc
     */
    public function setExecutionContextProvider($provider)
    {
        $this->executionContextProvider = $provider;
    }

    /**
     * Retrieves current execution context.
     *
     * @return ExecutionContext | SubscribtionStateChangedExecutionContext
     */
    protected function getExecutionContext()
    {
        return call_user_func($this->executionContextProvider);
    }

    /**
     * Retrieves sync config service.
     *
     * @return \Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\Contracts\SyncConfigService
     */
    protected function getSyncConfigService()
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return ServiceRegister::getService(SyncConfigService::CLASS_NAME);
    }

    /**
     * Retrieves merger.
     *
     * @param string $merger
     *
     * @return \Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\Merger\Merger
     */
    protected function getMerger($merger)
    {
        return MergerRegistry::get($merger);
    }

    /**
     * Retrieves group service instance.
     *
     * @return GroupService
     */
    protected function getGroupService()
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return ServiceRegister::getService(GroupService::CLASS_NAME);
    }

    /**
     * Returns receiver proxy.
     *
     * @return Proxy
     */
    protected function getReceiverProxy()
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return ServiceRegister::getService(Proxy::CLASS_NAME);
    }

    /**
     * Retrieves specific receiver service.
     *
     * @param string $service
     *
     * @return \Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\Contracts\ReceiverService
     */
    protected function getReceiverService($service)
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return ServiceRegister::getService($service);
    }
}