<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Form\WebHooks;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Configuration\Configuration;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Form\Tasks\CacheFormsTask;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Group\Contracts\GroupService;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\QueueService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\WebHookEvent\Contracts\WebHookHandler;
use Crsw\CleverReachOfficial\Core\BusinessLogic\WebHookEvent\DTO\WebHook;
use Crsw\CleverReachOfficial\Core\BusinessLogic\WebHookEvent\Exceptions\UnableToHandleWebHookException;
use Crsw\CleverReachOfficial\Core\Infrastructure\Configuration\ConfigurationManager;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException;

/**
 * Class Handler
 *
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic\Form\WebHooks
 */
class Handler implements WebHookHandler
{
    const CLASS_NAME = __CLASS__;

    protected static $supportedEvents = array(
        'form.created',
        'form.updated',
        'form.deleted',
    );

    /**
     * @inheritDoc
     */
    public function handle(WebHook $hook)
    {
        if (!in_array($hook->getEvent(), static::$supportedEvents, true)) {
            throw new UnableToHandleWebHookException('Event [' . $hook->getEvent() . '] not supported.');
        }

        if ($hook->getCondition() !== $this->getGroupService()->getId()) {
            throw new UnableToHandleWebHookException('Invalid group id.');
        }

        $payload = $hook->getPayload();
        if (empty($payload['form_id'])) {
            throw new UnableToHandleWebHookException('Invalid payload.');
        }

        $queueName = $this->getConfigService()->getDefaultQueueName();
        $context = $this->getConfigManager()->getContext();
        $task = new CacheFormsTask();

        try {
            $this->getQueue()->enqueue($queueName, $task, $context);
        } catch (QueueStorageUnavailableException $e) {
            throw new UnableToHandleWebHookException($e->getMessage(), $e->getCode());
        }
    }

    /**
     * @return GroupService | object
     */
    private function getGroupService()
    {
        return ServiceRegister::getService(GroupService::CLASS_NAME);
    }

    /**
     * @return QueueService | object
     */
    private function getQueue()
    {
        return ServiceRegister::getService(QueueService::CLASS_NAME);
    }

    /**
     * @return Configuration | object
     */
    private function getConfigService()
    {
        return ServiceRegister::getService(\Crsw\CleverReachOfficial\Core\Infrastructure\Configuration\Configuration::CLASS_NAME);
    }

    /**
     * @return ConfigurationManager | object
     */
    private function getConfigManager()
    {
        return ServiceRegister::getService(ConfigurationManager::CLASS_NAME);
    }
}