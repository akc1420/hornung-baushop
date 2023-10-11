<?php

namespace Ott\IdealoConnector\Test\Event;

require_once 'EventTestBehaviour.php';

use Ott\IdealoConnector\Event\IdealoOrderLineItemSavedEvent;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;

class IdealoOrderLineItemSavedEventTest extends TestCase
{
    use IntegrationTestBehaviour;
    use EventTestBehaviour;

    public function testFireEvent()
    {
        $eventName = IdealoOrderLineItemSavedEvent::class;

        $salesChannelEntity = new SalesChannelEntity();
        $orderLineItem   = ['id' => 2];
        $orderLineItemId = '2';

        $this->catchEvent($eventName, static function ($event) use (&$eventResult) {});
        $event = $this->fireEvent($eventName, $orderLineItemId, $orderLineItem, $salesChannelEntity);

        $this->assertInstanceOf(IdealoOrderLineItemSavedEvent::class, $event);
        $this->assertInstanceOf(SalesChannelEntity::class, $event->getSalesChannelEntity());
        $this->assertEquals($orderLineItem, $event->getOrderLineItem());
        $this->assertEquals($orderLineItemId, $event->getOrderLineItemId());
    }
}
