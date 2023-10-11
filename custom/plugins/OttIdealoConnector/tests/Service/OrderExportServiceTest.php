<?php


namespace Ott\IdealoConnector\Test\Service;

use Ott\IdealoConnector\Dbal\DataPersister;
use Ott\IdealoConnector\Service\OrderExportService;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;

class OrderExportServiceTest extends TestCase
{
    use IntegrationTestBehaviour;

    public function testGetCarrierDefault()
    {
        $service      = new OrderExportService($this->getContainer()->get(DataPersister::class));
        $trackingCode = '';

        $this->assertEquals('DHL', $service->getCarrier($trackingCode, 'DHL', ''));
        $this->assertEquals('DHL', $service->getCarrier($trackingCode, 'D H L', ''));
        $this->assertEquals('Hermes', $service->getCarrier($trackingCode, ' Hermes', ''));
    }

    public function testGetCarrierByRule()
    {
        $service = new OrderExportService($this->getContainer()->get(DataPersister::class));

        $trackingCode = 'D123';
        $this->assertEquals('DPD', $service->getCarrier($trackingCode, 'DHL', 'DPD=/D(.*)/=/D/'));
        $this->assertEquals('123', $trackingCode);

        $trackingCode = 'D123';
        $this->assertEquals('DPD', $service->getCarrier($trackingCode, 'DHL', 'HERMES=/H(.*)/=/H/|DPD=/D(.*)/=/D/'));
        $this->assertEquals('123', $trackingCode);

        $trackingCode = 'D123';
        $this->assertEquals('Hermes', $service->getCarrier($trackingCode, 'Hermes', 'foo=/bla/=/bla/'));
        $this->assertEquals('D123', $trackingCode);
    }
}
