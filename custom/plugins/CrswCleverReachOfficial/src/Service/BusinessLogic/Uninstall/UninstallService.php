<?php


namespace Crsw\CleverReachOfficial\Service\BusinessLogic\Uninstall;

use Crsw\CleverReachOfficial\Components\Utility\DatabaseHandler;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Http\TokenProxy;
use Crsw\CleverReachOfficial\Core\BusinessLogic\DynamicContent\Http\Proxy as DynamicContentProxy;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Form\FormEventsService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Group\Contracts\GroupService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\ReceiverEventsService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\WebHookEvent\Http\Proxy;
use Crsw\CleverReachOfficial\Core\Infrastructure\Exceptions\BaseException;
use Crsw\CleverReachOfficial\Core\Infrastructure\Logger\Logger;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;
use Crsw\CleverReachOfficial\Service\BusinessLogic\DynamicContent\DynamicContentService;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;

/**
 * Class UninstallService
 *
 * @package Crsw\CleverReachOfficial\Service\BusinessLogic\Uninstall
 */
class UninstallService
{
    /**
     * @var GroupService
     */
    private $groupService;
    /**
     * @var FormEventsService
     */
    private $formEventsService;
    /**
     * @var ReceiverEventsService
     */
    private $receiverEventsService;
    /**
     * @var Proxy
     */
    private $webhookProxy;
    /**
     * @var DynamicContentService
     */
    private $dynamicContentService;
    /**
     * @var DynamicContentProxy
     */
    private $dynamicContentProxy;
    /**
     * @var TokenProxy
     */
    private $tokenProxy;
    /**
     * @var DatabaseHandler
     */
    private $databaseHandler;

    /**
     * UninstallService constructor.
     *
     * @param GroupService $groupService
     * @param FormEventsService $formEventsService
     * @param ReceiverEventsService $receiverEventsService
     * @param Proxy $webhookProxy
     * @param DynamicContentService $dynamicContentService
     * @param DynamicContentProxy $dynamicContentProxy
     * @param TokenProxy $tokenProxy
     * @param DatabaseHandler $databaseHandler
     */
    public function __construct(
        GroupService $groupService,
        FormEventsService $formEventsService,
        ReceiverEventsService $receiverEventsService,
        Proxy $webhookProxy,
        DynamicContentService $dynamicContentService,
        DynamicContentProxy $dynamicContentProxy,
        TokenProxy $tokenProxy,
        DatabaseHandler $databaseHandler
    ) {
        $this->groupService = $groupService;
        $this->formEventsService = $formEventsService;
        $this->receiverEventsService = $receiverEventsService;
        $this->webhookProxy = $webhookProxy;
        $this->dynamicContentService = $dynamicContentService;
        $this->dynamicContentProxy = $dynamicContentProxy;
        $this->tokenProxy = $tokenProxy;
        $this->databaseHandler = $databaseHandler;
    }

    /**
     * Removes all plugin data.
     */
    public function removeData(): void
    {
        $this->deleteEvents();
        $this->deleteDynamicContent();
        $this->revokeOAuth();
        $this->removeCms();
        $this->removeRecordsFromDatabase();
    }

    private function deleteEvents(): void
    {
        $groupId = $this->groupService->getId();

        if ($groupId === '') {
            return;
        }

        $formType = $this->formEventsService->getType();
        $receiverType = $this->receiverEventsService->getType();

        try {
            $this->webhookProxy->deleteEvent($groupId, $formType);
        } catch (BaseException $e) {
            Logger::logError(
                "Failed to delete form event because: {$e->getMessage()}",
                'Integration'
            );
        }

        try {
            $this->webhookProxy->deleteEvent($groupId, $receiverType);
        } catch (BaseException $e) {
            Logger::logError(
                "Failed to delete receiver event because: {$e->getMessage()}",
                'Integration'
            );
        }
    }

    private function deleteDynamicContent(): void
    {
        $contentIds = [];

        try {
            $contentIds = $this->dynamicContentService->getCreatedDynamicContentIds();
        } catch (QueryFilterInvalidParamException $e) {
            Logger::logError(
                "Failed to delete dynamic content because: {$e->getMessage()}",
                'Integration'
            );
        }

        foreach ($contentIds as $id) {
            try {
                $this->dynamicContentProxy->deleteContent($id);
            } catch (BaseException $e) {
                Logger::logError(
                    "Failed to delete dynamic content because: {$e->getMessage()}",
                    'Integration'
                );
            }
        }
    }

    private function revokeOAuth(): void
    {
        try {
            $this->tokenProxy->revoke();
        } catch (BaseException $e) {
            Logger::logError(
                "Failed to revoke access token because: {$e->getMessage()}",
                'Integration'
            );
        }
    }

    private function removeRecordsFromDatabase(): void
    {
        try {
            $this->databaseHandler->removeData();
        } catch (DBALException $e) {
            Logger::logError(
                "Failed to remove data from table because: {$e->getMessage()}",
                'Integration'
            );
        }
    }

    private function removeCms(): void
    {
        /** @var Connection $connection */
        $connection = ServiceRegister::getService(Connection::class);

        try {
            $sql = "DELETE FROM cms_block WHERE type='cr-form'";
            $connection->executeUpdate($sql);

            $sql = "DELETE FROM cms_slot WHERE type='cr-form'";
            $connection->executeUpdate($sql);
        } catch (DBALException $e) {
            Logger::logError("Failed to remove cms blocks because: {$e->getMessage()}");
        }
    }
}