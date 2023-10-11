<?php

namespace Ott\IdealoConnector\Test\Event;

require_once 'EventTestBehaviour.php';

use Ott\IdealoConnector\Event\IdealoOrderItemFetchedEvent;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;

class IdealoOrderItemFetchedEventTest extends TestCase
{
    const EVENT_NAME = IdealoOrderItemFetchedEvent::class;

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
        $orderItem = ['id' => 2];

        $this->catchEvent(self::EVENT_NAME, static function ($event) use (&$eventResult) {});
        $event = $this->fireEvent(self::EVENT_NAME, $orderItem, $salesChannelEntity);

        $this->assertInstanceOf(self::EVENT_NAME, $event);
        $this->assertInstanceOf(SalesChannelEntity::class, $event->getSalesChannelEntity());
        $this->assertEquals($orderItem, $event->getOrderItem());
    }

    public function testModifyCustomer()
    {
        $salesChannelEntity = new SalesChannelEntity();
        $orderItem = ['id' => 2];

        $this->catchEvent(self::EVENT_NAME, static function ($event) use (&$eventResult) {
            $orderItem = $event->getOrderItem();
            $orderItem['id'] = 3;
            $event->setOrderItem($orderItem);
        });

        $event = $this->fireEvent(self::EVENT_NAME, $orderItem, $salesChannelEntity);

        $this->assertInstanceOf(self::EVENT_NAME, $event);
        $this->assertInstanceOf(SalesChannelEntity::class, $event->getSalesChannelEntity());
        $this->assertEquals(['id' => 3], $event->getOrderItem());
    }

    public function testNestedModification()
    {
        $salesChannelEntity = new SalesChannelEntity();
        $orderItem = ['id' => 2];

        $this->catchEvent(self::EVENT_NAME, static function ($event) use (&$eventResult) {
            $orderItem = $event->getOrderItem();
            $orderItem['id'] = 3;
            $event->setOrderItem($orderItem);
        });

        $this->catchEvent(self::EVENT_NAME, static function ($event) use (&$eventResult) {
            $orderItem = $event->getOrderItem();
            $orderItem['id'] = $orderItem['id'] + 2;
            $event->setOrderItem($orderItem);
        });

        $event = $this->fireEvent(self::EVENT_NAME, $orderItem, $salesChannelEntity);

        $this->assertInstanceOf(self::EVENT_NAME, $event);
        $this->assertInstanceOf(SalesChannelEntity::class, $event->getSalesChannelEntity());
        $this->assertEquals(['id' => 5], $event->getOrderItem());
    }
}
