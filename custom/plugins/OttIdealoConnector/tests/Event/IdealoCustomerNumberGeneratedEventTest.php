<?php


namespace Ott\IdealoConnector\Test\Event;

require_once 'EventTestBehaviour.php';

use Ott\IdealoConnector\Event\IdealoCustomerNumberGeneratedEvent;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;

class IdealoCustomerNumberGeneratedEventTest extends TestCase
{
    const EVENT_NAME = IdealoCustomerNumberGeneratedEvent::class;

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
        $customerNumber = 'test123';

        $this->catchEvent(self::EVENT_NAME, static function ($event) use (&$eventResult) {});
        $event = $this->fireEvent(self::EVENT_NAME, $customerNumber, $salesChannelEntity);

        $this->assertInstanceOf(self::EVENT_NAME, $event);
        $this->assertInstanceOf(SalesChannelEntity::class, $event->getSalesChannelEntity());
        $this->assertEquals($customerNumber, $event->getCustomerNumber());
    }

    public function testModifyCustomerNumber()
    {
        $salesChannelEntity = new SalesChannelEntity();
        $customerNumber = 'test123';

        $this->catchEvent(self::EVENT_NAME, static function ($event) use (&$eventResult) {
            $customerNumber = 'test456';
            $event->setCustomerNumber($customerNumber);
        });
        $event = $this->fireEvent(self::EVENT_NAME, $customerNumber, $salesChannelEntity);

        $this->assertInstanceOf(self::EVENT_NAME, $event);
        $this->assertInstanceOf(SalesChannelEntity::class, $event->getSalesChannelEntity());
        $this->assertEquals('test456', $event->getCustomerNumber());
    }

    public function testNestedModification()
    {
        $salesChannelEntity = new SalesChannelEntity();
        $customerNumber = 'test123';

        $this->catchEvent(self::EVENT_NAME, static function ($event) use (&$eventResult) {
            $customerNumber = 'test456';
            $event->setCustomerNumber($customerNumber);
        });

        $this->catchEvent(self::EVENT_NAME, static function ($event) use (&$eventResult) {
            $customerNumber = $event->getCustomerNumber();
            $customerNumber = 'SW' . $customerNumber;
            $event->setCustomerNumber($customerNumber);
        });
        $event = $this->fireEvent(self::EVENT_NAME, $customerNumber, $salesChannelEntity);

        $this->assertInstanceOf(self::EVENT_NAME, $event);
        $this->assertInstanceOf(SalesChannelEntity::class, $event->getSalesChannelEntity());
        $this->assertEquals('SWtest456', $event->getCustomerNumber());
    }
}
