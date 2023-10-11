<?php


namespace Crsw\CleverReachOfficial\Subscriber\SalesChannels;


use Crsw\CleverReachOfficial\Components\EventHandlers\TagHandler;
use Crsw\CleverReachOfficial\Components\Utility\Bootstrap;
use Crsw\CleverReachOfficial\Components\Utility\Initializer;
use Crsw\CleverReachOfficial\Core\Infrastructure\Logger\Logger;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\RepositoryClassException;
use Crsw\CleverReachOfficial\Entity\SalesChannel\Repositories\SalesChannelRepository;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityDeletedEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenEvent;
use Shopware\Core\PlatformRequest;
use Shopware\Core\System\SalesChannel\SalesChannelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class SalesChannelSubscriber
 *
 * @package Crsw\CleverReachOfficial\Subscriber\SalesChannels
 */
class SalesChannelSubscriber implements EventSubscriberInterface
{
    /**
     * @var array
     */
    private static $salesChannelForDelete = [];
    /**
     * @var TagHandler
     */
    private $tagHandler;
    /**
     * @var SalesChannelRepository
     */
    private $salesChannelRepository;

    /**
     * SalesChannelSubscriber constructor.
     *
     * @param TagHandler $tagHandler
     * @param SalesChannelRepository $salesChannelRepository
     * @param Initializer $initializer
     */
    public function __construct(
        TagHandler $tagHandler,
        SalesChannelRepository $salesChannelRepository,
        Initializer $initializer
    ) {
        Bootstrap::register();
        $initializer->registerServices();
        $this->tagHandler = $tagHandler;
        $this->salesChannelRepository = $salesChannelRepository;
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents(): array
    {
        return [
            SalesChannelEvents::SALES_CHANNEL_DELETED => 'onSalesChannelDelete',
            SalesChannelEvents::SALES_CHANNEL_WRITTEN => 'onSalesChannelSave',
            KernelEvents::CONTROLLER => 'saveDataForDelete',
            KernelEvents::RESPONSE => ['onIframeResponse', -256],
        ];
    }

    /**
     * @param ResponseEvent $event
     */
    public function onIframeResponse(ResponseEvent $event): void
    {
        if (str_contains($event->getRequest()->getPathInfo(), '/cleverreach/auth')) {
            $event->getResponse()->headers->remove('Content-Security-Policy');
        }

        $event->getResponse()->headers->set(PlatformRequest::HEADER_FRAME_OPTIONS, 'SAMEORIGIN');
    }

    /**
     * Sales channel deleted.
     *
     * @param EntityDeletedEvent $event
     */
    public function onSalesChannelDelete(EntityDeletedEvent $event): void
    {
        if (!$this->tagHandler->canHandle()) {
            return;
        }

        $ids = $event->getIds();

        foreach ($ids as $id) {
            $salesChannel = $this->salesChannelRepository->getSalesChannelById($id, $event->getContext());

            if (!$salesChannel) {
                continue;
            }

            $this->tagHandler->salesChannelDeleted($salesChannel->getName());
        }
    }

    /**
     * Sales channel created or modified.
     *
     * @param EntityWrittenEvent $event
     */
    public function onSalesChannelSave(EntityWrittenEvent $event): void
    {
        if (!$this->tagHandler->canHandle()) {
            return;
        }

        $this->tagHandler->tagCreated();

        foreach ($event->getIds() as $id) {
            if (!empty(static::$salesChannelForDelete[$id])) {
                $this->tagHandler->salesChannelDeleted(static::$salesChannelForDelete[$id]);
                unset(static::$salesChannelForDelete[$id]);
            }
        }
    }

    /**
     * @param ControllerEvent $controllerEvent
     */
    public function saveDataForDelete(ControllerEvent $controllerEvent): void
    {
        $request = $controllerEvent->getRequest();
        $routeName = $request->get('_route');

        if (in_array($routeName, ['api.sales_channel.update', 'api.sales_channel.delete'], true)) {
            $salesChannelId = $request->get('path');
            // check if route contains subpaths
            if (!strpos($salesChannelId, '/')) {
                $this->saveSalesChannelName($request);
            }
        }
    }

    private function saveSalesChannelName(Request $request): void
    {
        $salesChannel = $this->salesChannelRepository->getSalesChannelById(
            $request->get('path'),
            $request->get('sw-context') ?: Context::createDefaultContext()
        );

        if ($salesChannel) {
            static::$salesChannelForDelete[$salesChannel->getId()] = $salesChannel->getName();
        }
    }
}