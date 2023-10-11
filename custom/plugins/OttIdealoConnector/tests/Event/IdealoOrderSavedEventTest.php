<?php

namespace Ott\IdealoConnector\Test\Event;

require_once 'EventTestBehaviour.php';

use Ott\IdealoConnector\Event\IdealoOrderSavedEvent;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;

class IdealoOrderSavedEventTest extends TestCase
{
    use IntegrationTestBehaviour;
    use EventTestBehaviour;

    public function testFireEvent()
    {
        $eventName = IdealoOrderSavedEvent::class;

        $salesChannelEntity = new SalesChannelEntity();
        $orderItem = ['id' => 2];
        $orderId   = '2';

        $this->catchEvent($eventName, static function ($event) use (&$eventResult) {});
        $event = $this->fireEvent($eventName, $orderId, $orderItem, $salesChannelEntity);

        $this->assertInstanceOf(IdealoOrderSavedEvent::class, $event);
        $this->assertInstanceOf(SalesChannelEntity::class, $event->getSalesChannelEntity());
        $this->assertEquals($orderItem, $event->getOrderItem());
        $this->assertEquals($orderId, $event->getOrderId());
    }
}
