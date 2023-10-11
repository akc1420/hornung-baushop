<?php declare(strict_types=1);

namespace Ott\IdealoConnector\Subscriber;

use Ott\IdealoConnector\Dbal\DataProvider;
use Shopware\Core\Checkout\Order\OrderCollection;
use Shopware\Core\Checkout\Order\OrderEvents;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntitySearchResultLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class OrderSearchSubscriber implements EventSubscriberInterface
{
    private DataProvider $dataProvider;

    public function __construct(DataProvider $dataProvider)
    {
        $this->dataProvider = $dataProvider;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            OrderEvents::ORDER_SEARCH_RESULT_LOADED_EVENT => 'onOrderSearchResult',
        ];
    }

    public function onOrderSearchResult(EntitySearchResultLoadedEvent $event): void
    {
        $orders = $event->getResult()->getEntities();

        /**
         * @var OrderCollection $orders
         */
        foreach ($orders as $order) {
            $customFields = $order->getCustomFields();
            $customFields['ott_idealo_id'] = $this->dataProvider->getIdealoTransactionId($order->getId());
            $order->setCustomFields($customFields);
        }
    }
}
