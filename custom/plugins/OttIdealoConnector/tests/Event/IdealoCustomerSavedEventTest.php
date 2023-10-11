<?php

namespace Ott\IdealoConnector\Test\Event;

require_once 'EventTestBehaviour.php';

use Ott\IdealoConnector\Event\IdealoCustomerSavedEvent;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;

class IdealoCustomerSavedEventTest extends TestCase
{
    use IntegrationTestBehaviour;
    use EventTestBehaviour;

    public function testFireEvent()
    {
        $eventName = IdealoCustomerSavedEvent::class;

        $salesChannelEntity = new SalesChannelEntity();
        $customer   = ['id' => 2];
        $customerId = '2';

        $this->catchEvent($eventName, static function ($event) use (&$eventResult) {});
        $event = $this->fireEvent($eventName, $customerId, $customer, $salesChannelEntity);

        $this->assertInstanceOf(IdealoCustomerSavedEvent::class, $event);
        $this->assertInstanceOf(SalesChannelEntity::class, $event->getSalesChannelEntity());
        $this->assertEquals($customerId, $event->getCustomerId());
        $this->assertEquals($customer, $event->getCustomer());
    }
}
