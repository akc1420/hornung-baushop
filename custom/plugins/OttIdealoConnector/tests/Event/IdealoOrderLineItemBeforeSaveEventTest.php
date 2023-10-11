<?php

namespace Ott\IdealoConnector\Test\Event;

require_once 'EventTestBehaviour.php';

use Ott\IdealoConnector\Event\IdealoOrderLineItemBeforeSaveEvent;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;

class IdealoOrderLineItemBeforeSaveEventTest extends TestCase
{
    const EVENT_NAME = IdealoOrderLineItemBeforeSaveEvent::class;

    use IntegrationTestBehaviour;
    use EventTestBehaviour;

    public function tearDown(): void
    {
        parent::tearDown();

        $this->clearEvent(self::EVENT_NAME);
    }

    public function testFireEvent()
    {
        $salesChannelEntity = new SalesChannelEntity();
        $orderLineItem = ['id' => 2];

        $this->catchEvent(self::EVENT_NAME, static function ($event) use (&$eventResult) {});
        $event = $this->fireEvent(self::EVENT_NAME, $orderLineItem, $salesChannelEntity);

        $this->assertInstanceOf(self::EVENT_NAME, $event);
        $this->assertInstanceOf(SalesChannelEntity::class, $event->getSalesChannelEntity());
        $this->assertEquals($orderLineItem, $event->getOrderLineItem());
    }

    public function testModifyCustomer()
    {
        $salesChannelEntity = new SalesChannelEntity();
        $orderLineItem = ['id' => 2];

        $this->catchEvent(self::EVENT_NAME, static function ($event) use (&$eventResult) {
            $orderLineItem = $event->getOrderLineItem();
            $orderLineItem['id'] = 3;
            $event->setOrderLineItem($orderLineItem);
        });

        $event = $this->fireEvent(self::EVENT_NAME, $orderLineItem, $salesChannelEntity);

        $this->assertInstanceOf(self::EVENT_NAME, $event);
        $this->assertInstanceOf(SalesChannelEntity::class, $event->getSalesChannelEntity());
        $this->assertEquals(['id' => 3], $event->getOrderLineItem());
    }

    public function testNestedModification()
    {
        $salesChannelEntity = new SalesChannelEntity();
        $orderLineItem = ['id' => 2];

        $this->catchEvent(self::EVENT_NAME, static function ($event) use (&$eventResult) {
            $orderLineItem = $event->getOrderLineItem();
            $orderLineItem['id'] = 3;
            $event->setOrderLineItem($orderLineItem);
        });

        $this->catchEvent(self::EVENT_NAME, static function ($event) use (&$eventResult) {
            $orderLineItem = $event->getOrderLineItem();
            $orderLineItem['id'] = $orderLineItem['id'] + 2;
            $event->setOrderLineItem($orderLineItem);
        });

        $event = $this->fireEvent(self::EVENT_NAME, $orderLineItem, $salesChannelEntity);

        $this->assertInstanceOf(self::EVENT_NAME, $event);
        $this->assertInstanceOf(SalesChannelEntity::class, $event->getSalesChannelEntity());
        $this->assertEquals(['id' => 5], $event->getOrderLineItem());
    }
}
