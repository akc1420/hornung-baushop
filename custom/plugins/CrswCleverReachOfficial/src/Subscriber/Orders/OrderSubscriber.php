<?php


namespace Crsw\CleverReachOfficial\Subscriber\Orders;


use Crsw\CleverReachOfficial\Components\EventHandlers\OrderHandler;
use Crsw\CleverReachOfficial\Components\Utility\Bootstrap;
use Crsw\CleverReachOfficial\Components\Utility\Initializer;
use Crsw\CleverReachOfficial\Entity\Orders\Repositories\OrderItemsRepositoryInterface;
use Shopware\Core\Checkout\Order\OrderEvents;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Class OrderSubscriber
 *
 * @package Crsw\CleverReachOfficial\Subscriber\Orders
 */
class OrderSubscriber implements EventSubscriberInterface
{
    /**
     * @var SessionInterface
     */
    private $session;
    /**
     * @var OrderHandler
     */
    private $orderHandler;
    /**
     * @var OrderItemsRepositoryInterface
     */
    private $orderItemsRepository;

    /**
     * OrderSubscriber constructor.
     *
     * @param SessionInterface $session
     * @param OrderHandler $orderHandler
     * @param OrderItemsRepositoryInterface $orderItemsRepository
     * @param Initializer $initializer
     */
    public function __construct(
        SessionInterface $session,
        OrderHandler $orderHandler,
        OrderItemsRepositoryInterface $orderItemsRepository,
        Initializer $initializer
    ) {
        Bootstrap::init();
        $initializer->registerServices();
        $this->session = $session;
        $this->orderHandler = $orderHandler;
        $this->orderItemsRepository = $orderItemsRepository;
    }

    /**
     * @return string[]
     */
    public static function getSubscribedEvents(): array
    {
        return [
            OrderEvents::ORDER_WRITTEN_EVENT => 'onOrderSave'
        ];
    }

    /**
     * Handles order created event.
     *
     * @param EntityWrittenEvent $event
     */
    public function onOrderSave(EntityWrittenEvent $event): void
    {
        if (!$this->orderHandler->canHandle() || !$this->session->isStarted()) {
            return;
        }

        $ids = $event->getIds();
        $orderId = reset($ids);
        $crMailing = $this->session->get('crMailing');
        $email = $this->orderItemsRepository->getEmailByOrderId($orderId);

        $this->orderHandler->orderCreated($email, $orderId, $crMailing ?: '');
    }
}