<?php


namespace Ott\IdealoConnector\Test\Idealo;

use Composer\InstalledVersions;
use Ott\IdealoConnector\Dbal\DataProvider;
use Ott\IdealoConnector\Idealo\Client;
use PackageVersions\Versions;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Test\TestCaseHelper\ReflectionHelper;

class ClientTest extends TestCase
{
    use IntegrationTestBehaviour;

    private Client $client;
    private \ReflectionMethod $getHeaderMethod;
    private ?DataProvider $dataProvider;
    private string $shopwareVersion;
    private string $interfaceVersion;

    public function setUp(): void
    {
        parent::setUp();
        $this->dataProvider = $this->getContainer()->get(DataProvider::class);
        $this->client = new Client($this->dataProvider);
        $this->getHeaderMethod  = ReflectionHelper::getMethod(Client::class, 'getHeader');
        $shopwareVersion  = class_exists(InstalledVersions::class)
            ? InstalledVersions::getVersion('shopware/core')
            : Versions::getVersion('shopware/core');
        $this->shopwareVersion  = explode('@', $shopwareVersion)[0];
        $this->interfaceVersion = $this->dataProvider->getPluginVersion();
    }

    public function testGetHeaderGetType()
    {
        $header = $this->getHeaderMethod->invokeArgs($this->client, ['GET', '', []]);
        $this->assertEquals([
            'ERP-Shop-System: Shopware',
            'ERP-Shop-System-Version: ' . $this->shopwareVersion,
            'Integration-Partner: Ottscho IT',
            'Interface-Version: ' . $this->interfaceVersion
        ], $header);
    }

    public function testGetHeaderPostType()
    {
        $header = $this->getHeaderMethod->invokeArgs($this->client, ['POST', json_encode(['test' => 'test']), []]);
        $this->assertEquals([
            'ERP-Shop-System: Shopware',
            'ERP-Shop-System-Version: ' . $this->shopwareVersion,
            'Integration-Partner: Ottscho IT',
            'Interface-Version: ' . $this->interfaceVersion,
            'Content-Type: application/json',
            'Content-Length: 15'
        ], $header);
    }

    public function testGetHeaderPutType()
    {
        $header = $this->getHeaderMethod->invokeArgs($this->client, ['PUT', json_encode(['test' => 'test']), []]);
        $this->assertEquals([
            'ERP-Shop-System: Shopware',
            'ERP-Shop-System-Version: ' . $this->shopwareVersion,
            'Integration-Partner: Ottscho IT',
            'Interface-Version: ' . $this->interfaceVersion,
            'Content-Type: application/json',
            'Content-Length: 15'
        ], $header);
    }

    public function testGetHeaderGetTypeAlternativeHeader()
    {
        $header = $this->getHeaderMethod->invokeArgs($this->client, ['GET', '', ['foo: bar']]);
        $this->assertEquals([
            'ERP-Shop-System: Shopware',
            'ERP-Shop-System-Version: ' . $this->shopwareVersion,
            'Integration-Partner: Ottscho IT',
            'Interface-Version: ' . $this->interfaceVersion,
            'foo: bar'
        ], $header);
    }

    public function testGetHeaderPostTypeAlternativeHeader()
    {
        $header = $this->getHeaderMethod->invokeArgs($this->client, ['POST', json_encode(['test' => 'test']), ['foo: bar']]);
        $this->assertEquals([
            'ERP-Shop-System: Shopware',
            'ERP-Shop-System-Version: ' . $this->shopwareVersion,
            'Integration-Partner: Ottscho IT',
            'Interface-Version: ' . $this->interfaceVersion,
            'Content-Type: application/json',
            'Content-Length: 15',
            'foo: bar'
        ], $header);
    }

    public function testClient()
    {
        $result = $this->client->get('www.google.de');
        $this->assertTrue(is_array($result));
        $this->assertEquals(200, $result['status']);
    }
}
