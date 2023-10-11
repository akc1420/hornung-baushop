<?php

namespace Crsw\CleverReachOfficial\Components\Tasks;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\Contracts\ExecutionContextAware;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\Tasks\Composite\Configuration\SyncConfiguration;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\Task;
use Crsw\CleverReachOfficial\Service\BusinessLogic\Customer\BuyerService;

/**
 * Class BuyersEmailsResolverTask
 *
 * @package Crsw\CleverReachOfficial\Components\Tasks
 */
class BuyersEmailsResolverTask extends Task implements ExecutionContextAware
{
    /**
     * @var callable
     */
    private $executionContextProvider;

    /**
     * @inheritDoc
     */
    public function setExecutionContextProvider($provider): void
    {
        $this->executionContextProvider = $provider;
    }

    /**
     * @inheritDoc
     */
    public function execute(): void
    {
        $configuration = new SyncConfiguration($this->getBuyerService()->getReceiverEmails(), [], true);
        $this->getExecutionContext()->syncConfiguration = $configuration;
        $this->reportProgress(100);
    }

    /**
     * @return BuyerService
     */
    protected function getBuyerService(): BuyerService
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return ServiceRegister::getService(BuyerService::class);
    }

    /**
     * @return mixed
     */
    protected function getExecutionContext()
    {
        return call_user_func($this->executionContextProvider);
    }
}
