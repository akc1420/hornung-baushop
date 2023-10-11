<?php

namespace Ott\IdealoConnector\Test\Event;

require_once 'EventTestBehaviour.php';

use Ott\IdealoConnector\Event\IdealoOrderBeforeSaveEvent;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;

class IdealoOrderBeforeSaveEventTest extends TestCase
{
    const EVENT_NAME = IdealoOrderBeforeSaveEvent::class;

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
        $order = ['id' => 2];

        $this->catchEvent(self::EVENT_NAME, static function ($event) use (&$eventResult) {});
        $event = $this->fireEvent(self::EVENT_NAME, $order, $salesChannelEntity);

        $this->assertInstanceOf(self::EVENT_NAME, $event);
        $this->assertInstanceOf(SalesChannelEntity::class, $event->getSalesChannelEntity());
        $this->assertEquals($order, $event->getOrderItem());
    }

    public function testModifyCustomer()
    {
        $salesChannelEntity = new SalesChannelEntity();
        $order = ['id' => 2];

        $this->catchEvent(self::EVENT_NAME, static function ($event) use (&$eventResult) {
            $order = $event->getOrderItem();
            $order['id'] = 3;
            $event->setOrderItem($order);
        });

        $event = $this->fireEvent(self::EVENT_NAME, $order, $salesChannelEntity);

        $this->assertInstanceOf(self::EVENT_NAME, $event);
        $this->assertInstanceOf(SalesChannelEntity::class, $event->getSalesChannelEntity());
        $this->assertEquals(['id' => 3], $event->getOrderItem());
    }

    public function testNestedModification()
    {
        $salesChannelEntity = new SalesChannelEntity();
        $order = ['id' => 2];

        $this->catchEvent(self::EVENT_NAME, static function ($event) use (&$eventResult) {
            $order = $event->getOrderItem();
            $order['id'] = 3;
            $event->setOrderItem($order);
        });

        $this->catchEvent(self::EVENT_NAME, static function ($event) use (&$eventResult) {
            $order = $event->getOrderItem();
            $order['id'] = $order['id'] + 2;
            $event->setOrderItem($order);
        });

        $event = $this->fireEvent(self::EVENT_NAME, $order, $salesChannelEntity);

        $this->assertInstanceOf(self::EVENT_NAME, $event);
        $this->assertInstanceOf(SalesChannelEntity::class, $event->getSalesChannelEntity());
        $this->assertEquals(['id' => 5], $event->getOrderItem());
    }
}
