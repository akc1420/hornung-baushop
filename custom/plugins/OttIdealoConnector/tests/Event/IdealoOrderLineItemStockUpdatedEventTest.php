<?php

namespace Ott\IdealoConnector\Test\Event;

require_once 'EventTestBehaviour.php';

use Ott\IdealoConnector\Event\IdealoOrderLineItemStockUpdatedEvent;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;

class IdealoOrderLineItemStockUpdatedEventTest extends TestCase
{
    use IntegrationTestBehaviour;
    use EventTestBehaviour;

    public function testFireEvent()
    {
        $eventName = IdealoOrderLineItemStockUpdatedEvent::class;

        $salesChannelEntity = new SalesChannelEntity();
        $productId = 'foobar';
        $updateQuantity = 10;

        $this->catchEvent($eventName, static function ($event) use (&$eventResult) {});
        $event = $this->fireEvent($eventName, $productId, $updateQuantity, $salesChannelEntity);

        $this->assertInstanceOf(IdealoOrderLineItemStockUpdatedEvent::class, $event);
        $this->assertInstanceOf(SalesChannelEntity::class, $event->getSalesChannelEntity());
        $this->assertEquals($productId, $event->getProductId());
        $this->assertEquals($updateQuantity, $event->getUpdateQuantity());
    }
}
