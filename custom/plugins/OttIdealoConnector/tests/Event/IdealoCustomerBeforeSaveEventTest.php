<?php

namespace Ott\IdealoConnector\Test\Event;

require_once 'EventTestBehaviour.php';

use Ott\IdealoConnector\Event\IdealoCustomerBeforeSaveEvent;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;

class IdealoCustomerBeforeSaveEventTest extends TestCase
{
    const EVENT_NAME = IdealoCustomerBeforeSaveEvent::class;
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
        $customer = ['id' => 2];

        $this->catchEvent(self::EVENT_NAME, static function ($event) use (&$eventResult) {});
        $event = $this->fireEvent(self::EVENT_NAME, $customer, $salesChannelEntity);

        $this->assertInstanceOf(self::EVENT_NAME, $event);
        $this->assertInstanceOf(SalesChannelEntity::class, $event->getSalesChannelEntity());
        $this->assertEquals($customer, $event->getCustomer());
    }

    public function testModifyCustomer()
    {
        $salesChannelEntity = new SalesChannelEntity();
        $customer = ['id' => 2];

        $this->catchEvent(self::EVENT_NAME, static function ($event) use (&$eventResult) {
            $customer = $event->getCustomer();
            $customer['id'] = 3;
            $event->setCustomer($customer);
        });

        $event = $this->fireEvent(self::EVENT_NAME, $customer, $salesChannelEntity);

        $this->assertInstanceOf(self::EVENT_NAME, $event);
        $this->assertInstanceOf(SalesChannelEntity::class, $event->getSalesChannelEntity());
        $this->assertEquals(['id' => 3], $event->getCustomer());
    }

    public function testNestedModification()
    {
        $salesChannelEntity = new SalesChannelEntity();
        $customer = ['id' => 2];

        $this->catchEvent(self::EVENT_NAME, static function ($event) use (&$eventResult) {
            $customer = $event->getCustomer();
            $customer['id'] = 3;
            $event->setCustomer($customer);
        });

        $this->catchEvent(self::EVENT_NAME, static function ($event) use (&$eventResult) {
            $customer = $event->getCustomer();
            $customer['id'] = $customer['id'] + 2;
            $event->setCustomer($customer);
        });

        $event = $this->fireEvent(self::EVENT_NAME, $customer, $salesChannelEntity);

        $this->assertInstanceOf(self::EVENT_NAME, $event);
        $this->assertInstanceOf(SalesChannelEntity::class, $event->getSalesChannelEntity());
        $this->assertEquals(['id' => 5], $event->getCustomer());
    }
}
