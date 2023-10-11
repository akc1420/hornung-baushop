<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\Tasks;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\Contracts\AbandonedCartRecordService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\DTO\AbandonedCartTrigger;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\DTO\AbandonedCartTriggeredLog;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\Exceptions\FailedToPassFilterException;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\Http\Proxy;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\Pipeline\AbandonedCartTriggerPipeline;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Scheduler\Interfaces\Schedulable;
use Crsw\CleverReachOfficial\Core\Infrastructure\Configuration\ConfigurationManager;
use Crsw\CleverReachOfficial\Core\Infrastructure\Logger\Logger;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\RepositoryRegistry;
use Crsw\CleverReachOfficial\Core\Infrastructure\Serializer\Serializer;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\Exceptions\AbortTaskExecutionException;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\Task;

class AbandonedCartTriggerTask extends Task implements Schedulable
{
    /**
     * @var int
     */
    protected $recordId;

    /**
     * AbandonedCartTriggerTask constructor.
     *
     * @param int $recordId
     */
    public function __construct($recordId)
    {
        $this->recordId = $recordId;
    }

    public function serialize()
    {
        return Serializer::serialize(array($this->recordId));
    }

    public function unserialize($serialized)
    {
        list($this->recordId) = Serializer::unserialize($serialized);
    }

    public function toArray()
    {
        return array('recordId' => $this->recordId);
    }

    public static function fromArray(array $array)
    {
        return new static($array['recordId']);
    }

    public function canHaveMultipleQueuedInstances()
    {
        return true;
    }

    /**
     * Triggers abandoned cart.
     *
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRefreshAccessToken
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRetrieveAuthInfoException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpRequestException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\Exceptions\AbortTaskExecutionException
     */
    public function execute()
    {
        $record = $this->getService()->getById($this->recordId);
        $trigger = $record->getTrigger();

        try {
            AbandonedCartTriggerPipeline::execute($trigger);
        } catch (FailedToPassFilterException $e) {
            Logger::logWarning($e->getMessage(), 'Core', array('trace' => $e->getTraceAsString()));
            $this->getService()->delete($trigger->getGroupId(), $trigger->getPoolId());
            throw new AbortTaskExecutionException($e->getMessage());
        }

        $this->reportProgress(5);
        $this->getProxy()->triggerAbandonedCart($trigger);
        $this->reportProgress(70);
        $this->getService()->deleteRecord($record);
        $this->reportProgress(90);
        $this->createLog($trigger);
        $this->reportProgress(100);
    }

    /**
     * Creates abandoned cart triggered log.
     *
     * @param \Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\DTO\AbandonedCartTrigger $trigger
     *
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    private function createLog(AbandonedCartTrigger $trigger)
    {
        $log = new AbandonedCartTriggeredLog();
        $log->setContext($this->getConfigManager()->getContext());
        $log->setCartId($trigger->getCartId());
        $log->setTriggeredAt(new \DateTime());
        $this->getLogRepository()->save($log);
    }

    /**
     * Retrieves abandoned cart proxy.
     *
     * @return Proxy | object
     */
    private function getProxy()
    {
        return ServiceRegister::getService(Proxy::CLASS_NAME);
    }

    /**
     * Retrieves abandoned cart record service.
     *
     * @return AbandonedCartRecordService | object
     */
    private function getService()
    {
        return ServiceRegister::getService(AbandonedCartRecordService::CLASS_NAME);
    }

    /**
     * Retrieves log repository.
     *
     * @return \Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Interfaces\RepositoryInterface
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    private function getLogRepository()
    {
        return RepositoryRegistry::getRepository(AbandonedCartTriggeredLog::getClassName());
    }

    /**
     * Retrieves configuration manager.
     *
     * @return ConfigurationManager | object
     */
    private function getConfigManager()
    {
        return ServiceRegister::getService(ConfigurationManager::CLASS_NAME);
    }
}