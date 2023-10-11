<?php declare(strict_types=1);

namespace Ott\IdealoConnector\Test\Service;

use Ott\IdealoConnector\Idealo\Client;
use Ott\IdealoConnector\Service\ClientService;
use Ott\IdealoConnector\Service\ConfigProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\Test\TestLogger;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Test\TestCaseHelper\ReflectionHelper;

class ClientServiceTest extends TestCase
{
    use IntegrationTestBehaviour;

    private const GENERATED_TOKEN_FILE = __DIR__ . '/../../src/Resources/v2%s';

    private ConfigProvider $configProvider;
    private ClientService $clientService;

    public function setUp(): void
    {
        parent::setUp();
        $this->configProvider = $this->getContainer()->get(ConfigProvider::class);
        $this->configProvider
            ->setClientId('02eaa015-39bf-47c9-882a-9ca1e6a9da56')
            ->setClientSecret('7D+rTDq0,b0^d=kP87B_')
            ->setIsSandboxMode(true);
        $this->clientService = new ClientService(
            $this->getContainer()->get(Client::class),
            $this->configProvider,
            new TestLogger()
        );
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $tokenFile = sprintf(self::GENERATED_TOKEN_FILE, $this->configProvider->getClientId());
        if (file_exists($tokenFile)) {
            unlink($tokenFile);
        }
    }

    public function testGetAccessToken()
    {
        $tokenFile = sprintf(self::GENERATED_TOKEN_FILE, $this->configProvider->getClientId());
        $this->assertTrue($this->clientService->getAccessToken());
        $this->assertTrue(file_exists($tokenFile));
        $tokenContents = file_get_contents($tokenFile);
        $tokenData = explode('#', $tokenContents);
        $this->assertEquals('302846', $tokenData[2]);
    }

    public function testGetMultipleAccessToken()
    {
        $tokenFile = sprintf(self::GENERATED_TOKEN_FILE, $this->configProvider->getClientId());
        $this->assertTrue($this->clientService->getAccessToken());
        $this->assertTrue(file_exists($tokenFile));
        $tokenContents = file_get_contents($tokenFile);
        $tokenData = explode('#', $tokenContents);
        $this->assertEquals('302846', $tokenData[2]);
        $this->configProvider
            ->setClientId('no-valid-id')
            ->setClientSecret('7D+rTDq0,b0^d=kP87B_');
        $this->assertFalse($this->clientService->getAccessToken());
        $tokenFile = sprintf(self::GENERATED_TOKEN_FILE, $this->configProvider->getClientId());
        $this->assertFalse(file_exists($tokenFile));
    }

    public function testCheckAccessToken()
    {
        $reflectionMethod = ReflectionHelper::getMethod(ClientService::class, 'checkAccessToken');
        $this->assertTrue($reflectionMethod->invokeArgs($this->clientService, []));
        $this->configProvider
            ->setClientId('no-valid-id')
            ->setClientSecret('7D+rTDq0,b0^d=kP87B_');
        $this->assertFalse($reflectionMethod->invokeArgs($this->clientService, []));
    }

    public function testInvalidateAccessToken()
    {
        $propertyShopIds = ReflectionHelper::getProperty(ClientService::class, 'shopIds');
        $propertyAccessTokens = ReflectionHelper::getProperty(ClientService::class, 'accessTokens');
        $propertyAccessExpires = ReflectionHelper::getProperty(ClientService::class, 'accessTokenExpires');

        $this->assertEmpty($propertyShopIds->getValue($this->clientService));
        $this->assertEmpty($propertyAccessTokens->getValue($this->clientService));
        $this->assertEmpty($propertyAccessExpires->getValue($this->clientService));
        $this->assertTrue($this->clientService->getAccessToken());
        $this->assertEquals(302846, $propertyShopIds->getValue($this->clientService)[$this->configProvider->getClientId()]);
        $this->assertEquals(302846, $this->clientService->getClientShopId());
        $this->assertNotNull($propertyAccessTokens->getValue($this->clientService)[$this->configProvider->getClientId()]);
        $this->assertNotNull($this->clientService->getClientAccessToken());

        $this->clientService->invalidateAccessToken();

        $this->assertEmpty($propertyShopIds->getValue($this->clientService));
        $this->assertEmpty($propertyAccessTokens->getValue($this->clientService));
        $this->assertEmpty($propertyAccessExpires->getValue($this->clientService));
    }
}
