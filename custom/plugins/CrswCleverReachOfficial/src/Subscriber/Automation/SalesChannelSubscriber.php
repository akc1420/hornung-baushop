<?php

namespace Crsw\CleverReachOfficial\Subscriber\Automation;

use Crsw\CleverReachOfficial\Components\Utility\Bootstrap;
use Crsw\CleverReachOfficial\Components\Utility\Initializer;
use Crsw\CleverReachOfficial\Core\Infrastructure\Exceptions\BaseException;
use Crsw\CleverReachOfficial\Core\Infrastructure\Logger\Logger;
use Crsw\CleverReachOfficial\Entity\SalesChannel\Repositories\SalesChannelRepository;
use Crsw\CleverReachOfficial\Service\BusinessLogic\Automation\AutomationService;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityDeletedEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenEvent;
use Shopware\Core\System\SalesChannel\SalesChannelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class SalesChannelSubscriber
 *
 * @package Crsw\CleverReachOfficial\Subscriber\Automation
 */
class SalesChannelSubscriber implements EventSubscriberInterface
{
    /**
     * @var AutomationService
     */
    private $automationService;
    /**
     * @var SalesChannelRepository
     */
    private $salesChannelRepository;

    /**
     * SalesChannelSubscriber constructor.
     *
     * @param Initializer $initializer
     * @param AutomationService $automationService
     * @param SalesChannelRepository $salesChannelRepository
     */
    public function __construct(
        Initializer $initializer,
        AutomationService $automationService,
        SalesChannelRepository $salesChannelRepository
    ) {
        Bootstrap::register();
        $initializer->registerServices();
        $this->automationService = $automationService;
        $this->salesChannelRepository = $salesChannelRepository;
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents(): array
    {
        return [
            SalesChannelEvents::SALES_CHANNEL_DELETED => 'onSalesChannelDelete',
            SalesChannelEvents::SALES_CHANNEL_WRITTEN => 'onSalesChannelSave'
        ];
    }

    /**
     * Removes cart automation when store has been deleted.
     *
     * @param EntityDeletedEvent $event
     */
    public function onSalesChannelDelete(EntityDeletedEvent $event): void
    {
        $ids = $event->getIds();

        foreach ($ids as $id) {
            try {
                $this->automationService->delete($id);
            } catch (BaseException $e) {
                Logger::logError('Failed to remove automation for store: ' . $id, 'Integration');
            }
        }
    }

    /**
     * Removes cart records when store has been deactivated.
     *
     * @param EntityWrittenEvent $event
     */
    public function onSalesChannelSave(EntityWrittenEvent $event): void
    {
        $ids = $event->getIds();

        foreach ($ids as $id) {
            $salesChannel = $this->salesChannelRepository->getSalesChannelById($id, $event->getContext());

            if (!$salesChannel) {
                return;
            }

            if (!$salesChannel->getActive()) {
                try {
                    $this->automationService->deleteRecords($id);
                } catch (BaseException $e) {
                    Logger::logError('Failed to remove automation records for store: ' . $id, 'Integration');
                }
            }
        }
    }
}
