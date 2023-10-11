<?php


namespace Ott\IdealoConnector\Test\Event;

require_once 'EventTestBehaviour.php';

use Ott\IdealoConnector\Event\IdealoOrderNumberGeneratedEvent;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;

class IdealoOrderNumberGeneratedEventTest extends TestCase
{
    const EVENT_NAME = IdealoOrderNumberGeneratedEvent::class;

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
        $orderNumber = 'test123';

        $this->catchEvent(self::EVENT_NAME, static function ($event) use (&$eventResult) {});
        $event = $this->fireEvent(self::EVENT_NAME, $orderNumber, $salesChannelEntity);

        $this->assertInstanceOf(self::EVENT_NAME, $event);
        $this->assertInstanceOf(SalesChannelEntity::class, $event->getSalesChannelEntity());
        $this->assertEquals($orderNumber, $event->getOrderNumber());
    }

    public function testModifyCustomerNumber()
    {
        $salesChannelEntity = new SalesChannelEntity();
        $orderNumber = 'test123';

        $this->catchEvent(self::EVENT_NAME, static function ($event) use (&$eventResult) {
            $orderNumber = 'test456';
            $event->setOrderNumber($orderNumber);
        });
        $event = $this->fireEvent(self::EVENT_NAME, $orderNumber, $salesChannelEntity);

        $this->assertInstanceOf(self::EVENT_NAME, $event);
        $this->assertInstanceOf(SalesChannelEntity::class, $event->getSalesChannelEntity());
        $this->assertEquals('test456', $event->getOrderNumber());
    }

    public function testNestedModification()
    {
        $salesChannelEntity = new SalesChannelEntity();
        $orderNumber = 'test123';

        $this->catchEvent(self::EVENT_NAME, static function ($event) use (&$eventResult) {
            $orderNumber = 'test456';
            $event->setOrderNumber($orderNumber);
        });

        $this->catchEvent(self::EVENT_NAME, static function ($event) use (&$eventResult) {
            $orderNumber = $event->getOrderNumber();
            $orderNumber = 'SW' . $orderNumber;
            $event->setOrderNumber($orderNumber);
        });
        $event = $this->fireEvent(self::EVENT_NAME, $orderNumber, $salesChannelEntity);

        $this->assertInstanceOf(self::EVENT_NAME, $event);
        $this->assertInstanceOf(SalesChannelEntity::class, $event->getSalesChannelEntity());
        $this->assertEquals('SWtest456', $event->getOrderNumber());
    }
}
